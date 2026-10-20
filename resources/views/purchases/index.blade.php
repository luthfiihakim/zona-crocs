@extends('layouts.app')
@section('title', 'Pembelian')
@section('content')
<div class="toolbar">
    <h1>Pembelian</h1>
    <a class="btn" href="{{ route('purchases.create') }}">+ Pembelian Baru</a>
</div>

<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nomor...">
    <select name="supplier">
        <option value="">Semua supplier</option>
        @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" @selected((string) $supplierId === (string) $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">Semua status</option>
        <option value="received" @selected($status === 'received')>Diterima</option>
        <option value="cancelled" @selected($status === 'cancelled')>Dibatalkan</option>
    </select>
    <input type="date" name="from" value="{{ $from }}" title="Dari tanggal">
    <input type="date" name="to" value="{{ $to }}" title="Sampai tanggal">
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nomor</th><th>Tanggal</th><th>Supplier</th><th>Diinput oleh</th><th>Item</th><th>Total</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($purchases as $p)
            <tr>
                <td><a href="{{ route('purchases.show', $p) }}">{{ $p->number }}</a></td>
                <td>{{ $p->purchase_date->format('d/m/Y') }}</td>
                <td>{{ $p->supplier->name }}</td>
                <td>{{ $p->user->name }}</td>
                <td>{{ $p->items_count }}</td>
                <td>Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                <td>
                    <span class="badge {{ $p->status === 'received' ? 'ok' : 'off' }}">
                        {{ $p->status === 'received' ? 'Diterima' : 'Dibatalkan' }}
                    </span>
                </td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('purchases.show', $p) }}">Detail</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">Belum ada pembelian.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $purchases->links() }}
@endsection