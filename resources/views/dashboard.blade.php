@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="toolbar">
    <h1>Dashboard</h1>
    <div class="actions">
        <a class="btn" href="{{ route('sales.create') }}">+ Penjualan</a>
        <a class="btn btn-outline" href="{{ route('purchases.create') }}">+ Pembelian</a>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <small>Omzet hari ini</small>
        <b>Rp {{ number_format($stats['revenue_today'], 0, ',', '.') }}</b>
    </div>
    <div class="stat">
        <small>Omzet bulan ini</small>
        <b>Rp {{ number_format($stats['revenue_month'], 0, ',', '.') }}</b>
    </div>
    <div class="stat">
        <small>Transaksi lunas bulan ini</small>
        <b>{{ $stats['sales_month'] }}</b>
    </div>
    <div class="stat">
        <small>Menunggu pembayaran</small>
        <b>{{ $stats['pending'] }}</b>
    </div>
    <div class="stat">
        <small>Produk aktif</small>
        <b>{{ $stats['active_products'] }}</b>
    </div>
    <div class="stat {{ $stats['low_stock'] > 0 ? 'warn' : '' }}">
        <small>Varian stok rendah</small>
        <b>{{ $stats['low_stock'] }}</b>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <h2 class="panel-title">Omzet 7 hari terakhir</h2>
        <div class="chart">
            @foreach ($trend as $d)
                <div class="chart-col">
                    <div class="chart-fill" title="Rp {{ number_format($d['total'], 0, ',', '.') }}">
                        <i style="height: {{ round($d['total'] / $trendMax * 100) }}%"></i>
                    </div>
                    <small>{{ $d['label'] }}</small>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card table-wrap">
        <h2 class="panel-title" style="padding:20px 20px 0">Produk terlaris bulan ini</h2>
        <table>
            <thead><tr><th>Produk</th><th>Terjual</th><th>Omzet</th></tr></thead>
            <tbody>
            @forelse ($topProducts as $t)
                <tr>
                    <td>{{ $t->name }}</td>
                    <td>{{ $t->qty }}</td>
                    <td>Rp {{ number_format($t->revenue, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada penjualan bulan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="two-col">
    <div class="card table-wrap">
        <h2 class="panel-title" style="padding:20px 20px 0">
            Stok rendah
            <a href="{{ route('stock.low') }}">Lihat semua</a>
        </h2>
        <table>
            <thead><tr><th>SKU</th><th>Ukuran</th><th>Stok</th><th></th></tr></thead>
            <tbody>
            @forelse ($lowStock as $v)
                <tr>
                    <td>{{ $v->sku }}</td>
                    <td>{{ $v->size->us_label }}</td>
                    <td>
                        {{ $v->stock }}
                        <span class="badge low">{{ $v->stock === 0 ? 'Habis' : 'Rendah' }}</span>
                    </td>
                    <td class="actions">
                        <a class="btn btn-outline btn-sm" href="{{ route('products.show', $v->product_id) }}">Kelola</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Semua stok aman.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card table-wrap">
        <h2 class="panel-title" style="padding:20px 20px 0">
            Transaksi terbaru
            <a href="{{ route('sales.index') }}">Lihat semua</a>
        </h2>
        <table>
            <thead><tr><th>Nomor</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recent as $s)
                <tr>
                    <td><a href="{{ route('sales.show', $s) }}">{{ $s->number }}</a></td>
                    <td>Rp {{ number_format($s->total, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $s->statusBadge() }}">{{ $s->statusLabel() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada transaksi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection