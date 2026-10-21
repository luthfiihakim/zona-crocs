@extends('layouts.app')
@section('title', 'Keranjang')
@section('content')
<div class="toolbar">
    <h1>Keranjang</h1>
    <a class="btn btn-outline" href="{{ route('catalog') }}">Lanjut belanja</a>
</div>

@if (count($lines) === 0)
    <div class="card"><p class="muted">Keranjangmu masih kosong.</p></div>
@else
    <div class="card table-wrap">
        <table>
            <thead>
                <tr><th>Produk</th><th>Warna</th><th>Ukuran</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
            @foreach ($lines as $l)
                <tr>
                    <td>{{ $l['variant']->product->name }}</td>
                    <td>{{ $l['variant']->color }}</td>
                    <td>{{ $l['variant']->size->us_label }}</td>
                    <td>Rp {{ number_format($l['price'], 0, ',', '.') }}</td>
                    <td>
                        <form method="POST" action="{{ route('cart.update', $l['variant']) }}" class="actions" style="justify-content:flex-start">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="quantity" min="1" max="20" value="{{ $l['qty'] }}" style="width:80px;margin:0;min-height:36px">
                            <button class="btn btn-outline btn-sm" type="submit">Ubah</button>
                        </form>
                    </td>
                    <td>Rp {{ number_format($l['sub'], 0, ',', '.') }}</td>
                    <td class="actions">
                        <form method="POST" action="{{ route('cart.remove', $l['variant']) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="total-box">
        <span>Total</span><b>Rp {{ number_format($total, 0, ',', '.') }}</b>
    </div>

    <a class="btn" href="{{ route('checkout.create') }}">LANJUT KE CHECKOUT</a>
@endif
@endsection