<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.client_key' => 'test-client-key',
        ]);
    }

    /** @return array{0: User, 1: \App\Models\ProductVariant, 2: Sale} */
    private function order(int $stock = 5): array
    {
        $customer = $this->makeUser('customer');
        $variant = $this->makeVariant(stock: $stock);
        $sale = $this->placeOnlineOrder($customer, $variant, 1);

        return [$customer, $variant, $sale];
    }

    private function withPayment(Sale $sale): Payment
    {
        return $sale->payments()->create([
            'provider' => 'midtrans',
            'order_id' => $sale->number.'-1',
            'status' => 'pending',
            'amount' => $sale->total,
            'snap_token' => 'tok',
        ]);
    }

    private function notification(Payment $payment, string $status = 'settlement', ?string $amount = null): array
    {
        $body = [
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => $amount ?? number_format($payment->amount, 2, '.', ''),
            'transaction_status' => $status,
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-'.Str::random(6),
            'fraud_status' => 'accept',
        ];

        $body['signature_key'] = hash('sha512',
            $body['order_id'].$body['status_code'].$body['gross_amount'].config('services.midtrans.server_key'));

        return $body;
    }

    public function test_notification_with_invalid_signature_is_rejected(): void
    {
        [, , $sale] = $this->order();
        $payment = $this->withPayment($sale);

        $body = $this->notification($payment);
        $body['signature_key'] = 'salah';

        $this->postJson('/midtrans/notification', $body)->assertForbidden();

        $this->assertSame('pending', $sale->fresh()->status);
    }

    public function test_valid_notification_marks_order_paid_without_touching_stock(): void
    {
        [, $variant, $sale] = $this->order(5);
        $payment = $this->withPayment($sale);
        $this->assertSame(4, $variant->fresh()->stock); // sudah ditahan saat checkout

        $this->postJson('/midtrans/notification', $this->notification($payment))->assertOk();

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame('bank_transfer', $sale->fresh()->payment_method);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(4, $variant->fresh()->stock);
    }

    public function test_repeated_notification_is_idempotent(): void
    {
        [, $variant, $sale] = $this->order(5);
        $payment = $this->withPayment($sale);
        $body = $this->notification($payment);

        $this->postJson('/midtrans/notification', $body)->assertOk();
        $this->postJson('/midtrans/notification', $body)->assertOk();

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_notification_with_wrong_amount_does_not_mark_paid(): void
    {
        [, , $sale] = $this->order();
        $payment = $this->withPayment($sale);

        $this->postJson('/midtrans/notification', $this->notification($payment, 'settlement', '1.00'))->assertOk();

        $this->assertSame('pending', $sale->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_late_payment_revives_expired_order_when_stock_is_available(): void
    {
        [, $variant, $sale] = $this->order(5);
        $payment = $this->withPayment($sale);

        app(SaleService::class)->cancel($sale, 'expired');
        $this->assertSame(5, $variant->fresh()->stock);

        $this->postJson('/midtrans/notification', $this->notification($payment))->assertOk();

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame(4, $variant->fresh()->stock);
    }

    public function test_late_payment_is_flagged_for_refund_when_stock_is_gone(): void
    {
        [, $variant, $sale] = $this->order(5);
        $payment = $this->withPayment($sale);

        app(SaleService::class)->cancel($sale, 'expired');
        $variant->update(['stock' => 0]); // sudah terjual ke orang lain

        $this->postJson('/midtrans/notification', $this->notification($payment))->assertOk();

        $this->assertSame('expired', $sale->fresh()->status);
        $this->assertStringContainsString('PERLU REFUND', (string) $sale->fresh()->notes);
        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_pay_endpoint_creates_snap_token_once_and_reuses_it(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response(['token' => 'snap-token-123', 'redirect_url' => 'https://example.test'], 201),
            'api.sandbox.midtrans.com/*' => Http::response(['status_code' => '404'], 404),
        ]);
        Http::preventStrayRequests();

        [$customer, , $sale] = $this->order();

        $this->actingAs($customer)->postJson(route('orders.pay', $sale))
            ->assertOk()->assertJson(['token' => 'snap-token-123']);
        $this->actingAs($customer)->postJson(route('orders.pay', $sale))
            ->assertOk()->assertJson(['token' => 'snap-token-123']);

        $this->assertDatabaseCount('payments', 1);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'snap/v1/transactions')
            && (int) $request['transaction_details']['gross_amount'] === (int) $sale->total
            && $request['transaction_details']['order_id'] === $sale->number.'-1');
    }

    public function test_cannot_pay_after_payment_window_has_passed(): void
    {
        [$customer, , $sale] = $this->order();
        Sale::whereKey($sale->id)->update(['created_at' => now()->subHours(2)]);

        $this->actingAs($customer)->postJson(route('orders.pay', $sale))->assertForbidden();
    }

    public function test_opening_order_page_confirms_status_with_midtrans_not_the_browser(): void
    {
        [$customer, , $sale] = $this->order();
        $payment = $this->withPayment($sale);

        Http::fake([
            'api.sandbox.midtrans.com/*' => Http::response([
                'order_id' => $payment->order_id,
                'status_code' => '200',
                'gross_amount' => number_format($payment->amount, 2, '.', ''),
                'transaction_status' => 'settlement',
                'payment_type' => 'qris',
                'transaction_id' => 'trx-1',
                'fraud_status' => 'accept',
            ], 200),
        ]);
        Http::preventStrayRequests();

        $this->actingAs($customer)->get(route('orders.show', $sale))
            ->assertOk()
            ->assertSee('Pembayaran diterima');

        $this->assertSame('paid', $sale->fresh()->status);
    }
}