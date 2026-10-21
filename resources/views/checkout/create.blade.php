@extends('layouts.app')
@section('title', 'Checkout')
@section('content')
<div class="toolbar">
    <h1>Checkout</h1>
    <a class="btn btn-outline" href="{{ route('cart.index') }}">Kembali ke keranjang</a>
</div>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Produk</th><th>Warna</th><th>Ukuran</th><th>Jumlah</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
        @foreach ($lines as $l)
            <tr>
                <td>{{ $l['variant']->product->name }}</td>
                <td>{{ $l['variant']->color }}</td>
                <td>{{ $l['variant']->size->us_label }}</td>
                <td>{{ $l['qty'] }}</td>
                <td>Rp {{ number_format($l['sub'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="total-box">
    <span>Total</span><b>Rp {{ number_format($total, 0, ',', '.') }}</b>
</div>

<form method="POST" action="{{ route('checkout.store') }}" class="card wide"
      onsubmit="this.querySelector('button[type=submit]').disabled = true">
    @csrf
    <label>Alamat pengiriman
        <textarea name="shipping_address" required placeholder="Nama penerima, alamat lengkap, kota, kode pos, no. HP">{{ old('shipping_address') }}</textarea>
    </label>
    @error('shipping_address')<p class="err">{{ $message }}</p>@enderror

    <label>Catatan (opsional)
        <input type="text" name="notes" value="{{ old('notes') }}" maxlength="300">
    </label>
    @error('notes')<p class="err">{{ $message }}</p>@enderror

    <p class="muted">Harga dan stok dicek ulang saat pesanan dibuat. Stok ditahan {{ \App\Models\Sale::PAY_WINDOW_MINUTES }} menit sambil menunggu pembayaran.</p>
    <button class="btn" type="submit">BUAT PESANAN</button>
</form>
@endsection