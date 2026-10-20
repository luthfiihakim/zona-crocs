@extends('layouts.app')
@section('title', 'Katalog')
@section('content')
<div class="toolbar">
    <h1>Katalog</h1>
</div>

<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari produk...">
    <select name="category">
        <option value="">Semua kategori</option>
        @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected((string) $categoryId === (string) $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <select name="size">
        <option value="">Semua ukuran</option>
        @foreach ($sizes as $s)
            <option value="{{ $s->id }}" @selected((string) $sizeId === (string) $s->id)>{{ $s->us_label }}</option>
        @endforeach
    </select>
    <select name="sort">
        <option value="">Urutkan: nama</option>
        <option value="price_asc" @selected($sort === 'price_asc')>Harga terendah</option>
        <option value="price_desc" @selected($sort === 'price_desc')>Harga tertinggi</option>
    </select>
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="product-grid">
    @forelse ($products as $p)
        <a class="product-card" href="{{ route('catalog.show', $p->slug) }}">
            <div class="product-img">
                <span>{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span>
                @if (($p->ready_stock ?? 0) <= 0)
                    <em class="sold">Habis</em>
                @endif
            </div>
            <div class="product-info">
                <small>{{ $p->category->name }}</small>
                <h3>{{ $p->name }}</h3>
                <p class="price">
                    Rp {{ number_format($p->base_price, 0, ',', '.') }}
                    @if ($p->compare_price && $p->compare_price > $p->base_price)
                        <span class="price-old">Rp {{ number_format($p->compare_price, 0, ',', '.') }}</span>
                    @endif
                </p>
            </div>
        </a>
    @empty
        <p class="muted">Produk tidak ditemukan.</p>
    @endforelse
</div>

{{ $products->links() }}
@endsection