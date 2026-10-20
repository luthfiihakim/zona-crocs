@extends('layouts.app')
@section('title', 'Penjualan')
@section('content')
<div class="toolbar">
    <h1>Penjualan</h1>
    <a class="btn" href="{{ route('sales.create') }}">+ Penjualan Baru</a>
</div>

<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nomor...">
    <select name="status">
        <option value="">Semua status</option>
        @foreach (['pending' => 'Menunggu bayar', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa'] as $k => $label)
            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="channel">
        <option value="">Semua kanal</option>
        <option value="pos" @selected($channel === 'pos')>Kasir</option>
        <option value="online" @selected($channel === 'online')>Online</option>
    </select>
    <input type="date" name="from" value="{{ $from }}" title="Dari tanggal">
    <input type="date" name="to" value="{{ $to }}" title="Sampai tanggal">
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nomor</th><th>Tanggal</th><th>Kanal</th><th>Pembeli</th><th>Kasir</th><th>Total</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($sales as $s)
            <tr>
                <td><a href="{{ route('sales.show', $s) }}">{{ $s->number }}</a></td>
                <td>{{ $s->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $s->channel === 'pos' ? 'Kasir' : 'Online' }}</td>
                <td>{{ $s->customer?->name ?? 'Umum' }}</td>
                <td>{{ $s->cashier?->name ?? '-' }}</td>
                <td>Rp {{ number_format($s->total, 0, ',', '.') }}</td>
                <td><span class="badge {{ $s->statusBadge() }}">{{ $s->statusLabel() }}</span></td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('sales.show', $s) }}">Detail</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">Belum ada penjualan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $sales->links() }}
@endsection