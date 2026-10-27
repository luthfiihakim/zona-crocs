@extends('layouts.app')
@section('title', 'Foto Produk')
@section('content')
<div class="toolbar">
    <h1>Foto Produk</h1>
    <a class="btn btn-outline" href="{{ route('products.index') }}">Daftar produk</a>
</div>

<div class="alert {{ $summary['colors_missing'] ? 'info' : 'ok' }}">
    @if ($summary['colors_missing'])
        <b>{{ $summary['products_missing'] }}</b> dari {{ $summary['products'] }} produk masih punya warna tanpa foto
        ({{ $summary['colors_missing'] }} warna). Unggah di tiap baris. Untuk mengganti atau menghapus foto, klik Kelola.
    @else
        Semua produk sudah punya foto.
    @endif
</div>

@if ($errors->any())
    <div class="alert bad">
        <ul class="error-list">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama produk...">
    <select name="filter">
        <option value="missing" @selected($filter === 'missing')>Yang belum ada foto</option>
        <option value="all" @selected($filter === 'all')>Semua produk</option>
    </select>
    <button class="btn btn-dark" type="submit">Tampilkan</button>
</form>

@forelse ($rows as $r)
    @php($p = $r['product'])
    <div class="card" style="margin-bottom:16px">
        <div class="toolbar" style="margin-bottom:8px">
            <div>
                <b>{{ $p->name }}</b>
                <span class="muted">· {{ $p->category->name }}</span>
                @if ($r['missing'])
                    <span class="badge low">{{ $r['missing'] }} warna belum ada foto</span>
                @else
                    <span class="badge ok">Lengkap</span>
                @endif
            </div>
            <a class="btn btn-outline btn-sm" href="{{ route('products.show', $p) }}">Kelola</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Warna</th><th>Foto saat ini</th><th>Unggah foto</th></tr></thead>
                <tbody>
                @foreach ($r['items'] as $item)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td>
                            @if ($item['urls'])
                                <div class="thumbs">
                                    @foreach (array_slice($item['urls'], 0, 4) as $u)
                                        <img src="{{ $u }}" alt="" loading="lazy">
                                    @endforeach
                                </div>
                            @else
                                <span class="badge low">Belum ada</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('products.images.store', $p) }}" enctype="multipart/form-data" class="row-upload">
                                @csrf
                                <input type="hidden" name="color" value="{{ $item['color'] }}">
                                <input type="hidden" name="from" value="photos">
                                <input type="hidden" name="filter" value="{{ $filter }}">
                                <input type="hidden" name="q" value="{{ $q }}">
                                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                                <button class="btn btn-dark btn-sm" type="submit">Unggah</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card"><p class="muted">Tidak ada produk yang cocok.</p></div>
@endforelse
@endsection