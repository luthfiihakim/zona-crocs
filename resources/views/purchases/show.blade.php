@extends('layouts.app')
@section('title', $purchase->number)
@section('content')
<div class="toolbar">
    <h1>{{ $purchase->number }}</h1>
    <a class="btn btn-outline" href="{{ route('purchases.index') }}">Kembali</a>
</div>

<div class="meta">
    <span>Tanggal: <b>{{ $purchase->purchase_date->format('d/m/Y') }}</b></span>
    <span>Supplier: <b>{{ $purchase->supplier->name }}</b></span>
    <span>Diinput oleh: <b>{{ $purchase->user->name }}</b></span>
    <span>Status:
        <span class="badge {{ $purchase->status === 'received' ? 'ok' : 'off' }}">
            {{ $purchase->status === 'received' ? 'Diterima' : 'Dibatalkan' }}
        </span>
    </span>
</div>
@if ($purchase->notes)
    <p class="muted">Catatan: {{ $purchase->notes }}</p>
@endif

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>SKU</th><th>Produk</th><th>Warna</th><th>Ukuran</th><th>Jumlah</th><th>Harga beli</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
        @foreach ($purchase->items as $item)
            <tr>
                <td>{{ $item->variant->sku }}</td>
                <td>{{ $item->variant->product->name }}</td>
                <td>{{ $item->variant->color }}</td>
                <td>{{ $item->variant->size->us_label }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->quantity * $item->unit_cost, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="total-box">
    <span>Total</span><b>Rp {{ number_format($purchase->total, 0, ',', '.') }}</b>
</div>

@if (auth()->user()->isAdmin() && $purchase->status === 'received')
    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}"
          onsubmit="return confirm('Batalkan pembelian ini? Stok akan dikurangi kembali.')">
        @csrf
        <button class="btn btn-danger" type="submit">Batalkan Pembelian</button>
    </form>
@endif
@endsection