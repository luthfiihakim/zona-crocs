@extends('layouts.app')
@section('title', 'Produk')
@section('content')
<div class="toolbar">
    <h1>Produk</h1>
    <a class="btn" href="{{ route('products.create') }}">+ Tambah Produk</a>
</div>

<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama produk...">
    <select name="category">
        <option value="">Semua kategori</option>
        @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected((string) $categoryId === (string) $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">Semua status</option>
        <option value="active" @selected($status === 'active')>Aktif</option>
        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
    </select>
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nama</th><th>Kategori</th><th>Harga</th><th>Varian</th><th>Stok</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($products as $p)
            <tr>
                <td><a href="{{ route('products.show', $p) }}">{{ $p->name }}</a></td>
                <td>{{ $p->category->name }}</td>
                <td>
                    Rp {{ number_format($p->base_price, 0, ',', '.') }}
                    @if ($p->compare_price && $p->compare_price > $p->base_price)
                        <span class="price-old">Rp {{ number_format($p->compare_price, 0, ',', '.') }}</span>
                    @endif
                </td>
                <td>{{ $p->variants_count }}</td>
                <td>{{ $p->variants_sum_stock ?? 0 }}</td>
                <td><span class="badge {{ $p->is_active ? 'ok' : 'off' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="actions">
                    <a class="btn btn-dark btn-sm" href="{{ route('products.show', $p) }}">Kelola</a>
                    <a class="btn btn-outline btn-sm" href="{{ route('products.edit', $p) }}">Ubah</a>
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('products.destroy', $p) }}"
                              onsubmit="return confirm('Nonaktifkan produk ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">Tidak ada produk.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $products->links() }}
@endsection