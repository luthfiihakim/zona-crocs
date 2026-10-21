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
</div>
<p class="muted">Alamat kirim: {{ $sale->shipping_address }}</p>
@if ($sale->notes)
    <p class="muted">Catatan: {{ $sale->notes }}</p>
@endif

@if ($sale->status === 'pending')
    <div class="alert info">
        Stok untuk pesananmu ditahan sampai <b>{{ $sale->expiresAt()->format('d/m/Y H:i') }}</b>.
        Setelah itu pesanan dibatalkan otomatis. Pembayaran online akan tersedia di langkah berikutnya.
    </div>
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

@can('cancel', $sale)
    <form method="POST" action="{{ route('orders.cancel', $sale) }}"
          onsubmit="return confirm('Batalkan pesanan ini?')">
        @csrf
        <button class="btn btn-danger" type="submit">Batalkan Pesanan</button>
    </form>
@endcan
@endsection