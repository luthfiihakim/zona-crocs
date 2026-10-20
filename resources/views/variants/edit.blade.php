@extends('layouts.app')
@section('title', 'Ubah Varian')
@section('content')
<div class="card narrow">
    <h1>Ubah Varian</h1>
    <p class="muted">{{ $variant->product->name }} · Ukuran {{ $variant->size->us_label }} · Stok {{ $variant->stock }}</p>
    <p class="muted">Stok tidak diubah di sini. Gunakan Pembelian untuk menambah dan Penjualan untuk mengurangi.</p>
    <form method="POST" action="{{ route('variants.update', $variant) }}">
        @csrf
        @method('PUT')
        <label>SKU
            <input type="text" name="sku" value="{{ old('sku', $variant->sku) }}" required>
        </label>
        @error('sku')<p class="err">{{ $message }}</p>@enderror

        <label>Warna
            <input type="text" name="color" value="{{ old('color', $variant->color) }}" required>
        </label>
        @error('color')<p class="err">{{ $message }}</p>@enderror

        <label>Stok minimum
            <input type="number" name="min_stock" min="0" value="{{ old('min_stock', $variant->min_stock) }}" required>
        </label>
        @error('min_stock')<p class="err">{{ $message }}</p>@enderror

        <button class="btn" type="submit">SIMPAN</button>
    </form>
    <p class="muted"><a href="{{ route('products.show', $variant->product_id) }}">Kembali</a></p>
</div>
@endsection