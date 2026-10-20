@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="toolbar">
    <h1>{{ $product->name }}</h1>
    <a class="btn btn-outline" href="{{ route('products.edit', $product) }}">Ubah Produk</a>
</div>

<div class="meta">
    <span>Kategori: <b>{{ $product->category->name }}</b></span>
    <span>Harga:
        <b>Rp {{ number_format($product->base_price, 0, ',', '.') }}</b>
        @if ($product->compare_price && $product->compare_price > $product->base_price)
            <span class="price-old">Rp {{ number_format($product->compare_price, 0, ',', '.') }}</span>
        @endif
    </span>
    <span>Status: <span class="badge {{ $product->is_active ? 'ok' : 'off' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></span>
</div>

<h2 class="section-title">Varian ({{ $variants->count() }})</h2>
<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>SKU</th><th>Warna</th><th>Ukuran</th><th>Stok</th><th>Min</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($variants as $v)
            <tr>
                <td>{{ $v->sku }}</td>
                <td>{{ $v->color }}</td>
                <td>{{ $v->size->us_label }}</td>
                <td>
                    {{ $v->stock }}
                    @if ($v->size->is_preorder)
                        <span class="badge po">PO</span>
                    @elseif ($v->stock === 0)
                        <span class="badge low">Habis</span>
                    @elseif ($v->stock <= $v->min_stock)
                        <span class="badge low">Rendah</span>
                    @endif
                </td>
                <td>{{ $v->min_stock }}</td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('variants.edit', $v) }}">Ubah</a>
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('variants.destroy', $v) }}"
                              onsubmit="return confirm('Hapus varian ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Belum ada varian. Gunakan generator di bawah.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<h2 class="section-title">Tambah varian (generator)</h2>
<div class="card wide">
    <form method="POST" action="{{ route('products.variants.store', $product) }}">
        @csrf
        <label>Kode SKU (awalan)
            <input type="text" name="sku_prefix" maxlength="10"
                   value="{{ old('sku_prefix', strtoupper(substr($product->category->name, 0, 3))) }}" required>
        </label>
        @error('sku_prefix')<p class="err">{{ $message }}</p>@enderror

        <label>Warna (pisahkan dengan koma)
            <input type="text" name="colors" value="{{ old('colors') }}"
                   placeholder="Contoh: Black, Navy, Pink. Kosongkan jika tidak ada pilihan warna">
        </label>
        @error('colors')<p class="err">{{ $message }}</p>@enderror

        <label>Ukuran</label>
        <div class="chips">
            @foreach ($sizes as $s)
                <label>
                    <input type="checkbox" name="sizes[]" value="{{ $s->id }}"
                        @checked(is_array(old('sizes')) ? in_array($s->id, old('sizes')) : true)>
                    {{ $s->us_label }}{{ $s->is_preorder ? ' (PO)' : '' }}
                </label>
            @endforeach
        </div>
        @error('sizes')<p class="err">{{ $message }}</p>@enderror

        <label>Stok minimum
            <input type="number" name="min_stock" min="0" value="{{ old('min_stock', 2) }}" required>
        </label>
        @error('min_stock')<p class="err">{{ $message }}</p>@enderror

        <p class="muted">Varian baru dimulai dari stok 0. Stok bertambah lewat menu Pembelian.</p>
        <button class="btn" type="submit">BUAT VARIAN</button>
    </form>
</div>

<p class="muted"><a href="{{ route('products.index') }}">Kembali ke daftar produk</a></p>
@endsection