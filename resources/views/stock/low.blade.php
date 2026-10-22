@extends('layouts.app')
@section('title', 'Stok Rendah')
@section('content')
<div class="toolbar">
    <h1>Stok Rendah</h1>
    <a class="btn" href="{{ route('purchases.create') }}">+ Pembelian</a>
</div>

<form method="GET" class="search">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari SKU atau nama produk...">
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>SKU</th><th>Produk</th><th>Warna</th><th>Ukuran</th><th>Stok</th><th>Min</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($variants as $v)
            <tr>
                <td>{{ $v->sku }}</td>
                <td>{{ $v->product->name }}</td>
                <td>{{ $v->color }}</td>
                <td>{{ $v->size->us_label }}</td>
                <td>
                    {{ $v->stock }}
                    <span class="badge low">{{ $v->stock === 0 ? 'Habis' : 'Rendah' }}</span>
                </td>
                <td>{{ $v->min_stock }}</td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('products.show', $v->product_id) }}">Kelola</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">Tidak ada varian dengan stok rendah.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $variants->links() }}
@endsection