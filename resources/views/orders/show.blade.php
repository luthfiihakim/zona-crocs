@extends('layouts.app')
@section('title', $sale->number)
@section('content')
<div class="toolbar">
    <h1>{{ $sale->number }}</h1>
    <a class="btn btn-outline" href="{{ route('orders.index') }}">Kembali</a>
</div>

<div class="meta">
    <span>Tanggal: <b>{{ $sale->created_at->format('d/m/Y H:i') }}</b></span>
    <span>Status: <span class="badge {{ $sale->statusBadge() }}">{{ $sale->statusLabel() }}</span></span>
    @if ($sale->payment_method)
        <span>Pembayaran: <b>{{ strtoupper(str_replace('_', ' ', $sale->payment_method)) }}</b></span>
    @endif
</div>
<p class="muted">Alamat kirim: {{ $sale->shipping_address }}</p>
@if ($sale->notes)
    <p class="muted">Catatan: {{ $sale->notes }}</p>
@endif

@if ($sale->status === 'pending')
    <div class="alert info">
        Stok untuk pesananmu ditahan sampai <b>{{ $sale->expiresAt()->format('d/m/Y H:i') }}</b>.
        Setelah itu pesanan dibatalkan otomatis.
    </div>
@elseif ($sale->status === 'paid')
    <div class="alert ok">Pembayaran diterima. Pesananmu sedang diproses. Terima kasih!</div>
@elseif ($sale->status === 'expired')
    <div class="alert bad">Pesanan ini kedaluwarsa karena tidak dibayar tepat waktu. Stok sudah dikembalikan.</div>
@elseif ($sale->status === 'cancelled')
    <div class="alert bad">Pesanan ini dibatalkan.</div>
@endif

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Produk</th><th>Warna</th><th>Ukuran</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
        @foreach ($sale->items as $item)
            <tr>
                <td>{{ $item->variant->product->name }}</td>
                <td>{{ $item->variant->color }}</td>
                <td>{{ $item->variant->size->us_label }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="total-box">
    <span>Total</span><b>Rp {{ number_format($sale->total, 0, ',', '.') }}</b>
</div>

@if ($sale->status === 'pending')
    <div class="form-actions">
        <button id="pay" class="btn" type="button">BAYAR SEKARANG</button>
    </div>
    <p class="err" id="pay-msg" style="margin:12px 0 0"></p>
@endif

@can('cancel', $sale)
    <form method="POST" action="{{ route('orders.cancel', $sale) }}" style="margin-top:12px"
          onsubmit="return confirm('Batalkan pesanan ini?')">
        @csrf
        <button class="btn btn-danger" type="submit">Batalkan Pesanan</button>
    </form>
@endcan

@if ($sale->status === 'pending')
<script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com' }}/snap/snap.js"
        data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const msg = document.getElementById('pay-msg');
    const payBtn = document.getElementById('pay');

    const post = url => fetch(url, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
    });

    async function syncAndReload() {
        try { await post(@json(route('orders.sync', $sale))); } catch (e) {}
        location.reload();
    }

    payBtn.addEventListener('click', async () => {
        msg.textContent = '';
        payBtn.disabled = true;

        try {
            const res = await post(@json(route('orders.pay', $sale)));
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                msg.textContent = data.message || 'Pesanan ini tidak bisa dibayar lagi. Muat ulang halaman.';
                payBtn.disabled = false;
                return;
            }

            window.snap.pay(data.token, {
                onSuccess: syncAndReload,
                onPending: syncAndReload,
                onError: syncAndReload,
                onClose: () => { payBtn.disabled = false; syncAndReload(); },
            });
        } catch (e) {
            msg.textContent = 'Gagal menghubungi server pembayaran. Coba lagi.';
            payBtn.disabled = false;
        }
    });

    </script>
@endif
@endsection