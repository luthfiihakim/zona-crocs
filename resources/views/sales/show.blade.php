@extends('layouts.app')
@section('title', $sale->number)
@section('content')
<div class="toolbar">
    <h1>{{ $sale->number }}</h1>
    <a class="btn btn-outline" href="{{ route('sales.index') }}">Kembali</a>
</div>

<div class="meta">
    <span>Tanggal: <b>{{ $sale->created_at->format('d/m/Y H:i') }}</b></span>
    <span>Kanal: <b>{{ $sale->channel === 'pos' ? 'Kasir' : 'Online' }}</b></span>
    <span>Pembeli: <b>{{ $sale->customer?->name ?? 'Umum' }}</b></span>
    <span>Kasir: <b>{{ $sale->cashier?->name ?? '-' }}</b></span>
    <span>Pembayaran: <b>{{ $sale->payment_method ? strtoupper($sale->payment_method) : '-' }}</b></span>
    <span>Status: <span class="badge {{ $sale->statusBadge() }}">{{ $sale->statusLabel() }}</span></span>
</div>
@if ($sale->notes)
    <p class="muted">Catatan: {{ $sale->notes }}</p>
@endif

@if ($sale->shipping_address)
    <p class="muted">Alamat kirim: {{ $sale->shipping_address }}</p>
@endif

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>SKU</th><th>Produk</th><th>Warna</th><th>Ukuran</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
        @foreach ($sale->items as $item)
            <tr>
                <td>{{ $item->variant->sku }}</td>
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

@if (auth()->user()->isAdmin() && in_array($sale->status, ['pending', 'paid'], true))
    <form method="POST" action="{{ route('sales.cancel', $sale) }}"
          onsubmit="return confirm('Batalkan penjualan ini? Stok akan dikembalikan.')">
        @csrf
        <button class="btn btn-danger" type="submit">Batalkan Penjualan</button>
    </form>
@endif
@endsection