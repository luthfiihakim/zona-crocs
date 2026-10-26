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

{{-- ===== BLOK FOTO PRODUK DISISIPKAN DI SINI ===== --}}
<h2 class="section-title">Foto produk ({{ $product->images->count() }})</h2>

@if ($errors->any())
    <div class="alert bad">
        <ul class="error-list">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="card">
    @if ($product->images->isEmpty())
        <p class="muted">Belum ada foto. Unggah di bawah.</p>
    @else
        <div class="photo-grid">
            @foreach ($product->images as $img)
                <div class="photo-card">
                    <img src="{{ $img->url }}" alt="Foto {{ $product->name }}" loading="lazy">
                    <form method="POST" action="{{ route('product-images.update', $img) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <label>Untuk warna
                            <select name="color">
                                <option value="">Semua warna (umum)</option>
                                @foreach ($colors as $c)
                                    <option value="{{ $c }}" @selected($img->color === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Urutan
                            <input type="number" name="sort_order" min="0" max="99" value="{{ $img->sort_order }}" required>
                        </label>
                        <label>Ganti foto
                            <input type="file" name="file" accept="image/jpeg,image/png,image/webp">
                        </label>
                        <button class="btn btn-dark btn-sm" type="submit">Simpan</button>
                    </form>
                    <form method="POST" action="{{ route('product-images.destroy', $img) }}"
                          onsubmit="return confirm('Hapus foto ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="card wide" style="margin-top:16px">
    <form method="POST" action="{{ route('products.images.store', $product) }}" enctype="multipart/form-data">
        @csrf
        <label>Pilih foto (boleh banyak, maks. 8)
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
        </label>
        <label>Untuk warna
            <select name="color">
                <option value="">Semua warna (umum)</option>
                @foreach ($colors as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn" type="submit">UNGGAH FOTO</button>
    </form>
</div>

{{-- ===== BAGIAN GENERATOR VARIAN SETELAH FOTO ===== --}}
<h2 class="section-title" style="margin-top:24px">Tambah varian (generator)</h2>
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

<p class="muted" style="margin-top:16px"><a href="{{ route('products.index') }}">Kembali ke daftar produk</a></p>
@endsection