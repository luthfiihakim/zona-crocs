@extends('layouts.app')
@section('title', 'Pesanan Saya')
@section('content')
<div class="toolbar">
    <h1>Pesanan Saya</h1>
    <a class="btn btn-outline" href="{{ route('catalog') }}">Belanja lagi</a>
</div>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nomor</th><th>Tanggal</th><th>Item</th><th>Total</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($orders as $o)
            <tr>
                <td><a href="{{ route('orders.show', $o) }}">{{ $o->number }}</a></td>
                <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $o->items_count }}</td>
                <td>Rp {{ number_format($o->total, 0, ',', '.') }}</td>
                <td><span class="badge {{ $o->statusBadge() }}">{{ $o->statusLabel() }}</span></td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('orders.show', $o) }}">Detail</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Belum ada pesanan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}
@endsection