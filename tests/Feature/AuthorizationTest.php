<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    public function test_guest_is_redirected_to_login(): void
    {
        foreach (['/dashboard', '/products', '/sales', '/purchases', '/categories', '/suppliers', '/users', '/katalog', '/pesanan', '/keranjang'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_customer_cannot_open_staff_pages(): void
    {
        $customer = $this->makeUser('customer');

        foreach (['/dashboard', '/products', '/sales', '/purchases', '/categories', '/suppliers', '/users', '/stok-rendah'] as $url) {
            $this->actingAs($customer)->get($url)->assertForbidden();
        }
    }

    public function test_staff_can_open_dashboard_but_not_user_management(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get('/dashboard')->assertOk();
        $this->actingAs($staff)->get('/users')->assertForbidden();
    }

    public function test_staff_cannot_use_cart_or_checkout(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get('/keranjang')->assertForbidden();
        $this->actingAs($staff)->get('/checkout')->assertForbidden();
    }

    public function test_only_admin_can_delete_a_category(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser('staff'))
            ->delete(route('categories.destroy', $category))->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('categories.destroy', $category))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_customer_cannot_view_pay_or_cancel_another_customers_order(): void
    {
        $owner = $this->makeUser('customer');
        $other = $this->makeUser('customer');
        $sale = $this->placeOnlineOrder($owner, $this->makeVariant());

        $this->actingAs($other)->get(route('orders.show', $sale))->assertForbidden();
        $this->actingAs($other)->postJson(route('orders.pay', $sale))->assertForbidden();
        $this->actingAs($other)->post(route('orders.cancel', $sale))->assertForbidden();
    }

    public function test_customer_can_view_own_order(): void
    {
        $owner = $this->makeUser('customer');
        $sale = $this->placeOnlineOrder($owner, $this->makeVariant());

        $this->actingAs($owner)->get(route('orders.show', $sale))
            ->assertOk()
            ->assertSee($sale->number);
    }

    public function test_register_cannot_set_role(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'penyusup@example.com', 'role' => 'customer']);
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = $this->makeUser('customer', ['email' => 'nonaktif@example.com']);
        $user->is_active = false;
        $user->save();

        $this->post('/login', ['email' => 'nonaktif@example.com', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_active_session_ends_when_account_is_deactivated(): void
    {
        $user = $this->makeUser('customer');
        $this->actingAs($user);

        $user->is_active = false;
        $user->save();

        $this->get('/katalog')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}