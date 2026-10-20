@extends('layouts.app')
@section('title', 'Kategori')
@section('content')
<div class="toolbar">
    <h1>Kategori</h1>
    <a class="btn" href="{{ route('categories.create') }}">+ Tambah Kategori</a>
</div>

<form method="GET" class="search">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari kategori...">
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nama</th><th>Slug</th><th class="col-desc">Deskripsi</th><th>Produk</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($categories as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td>{{ $c->slug }}</td>
                <td class="col-desc">{{ $c->description ?: '-' }}</td>
                <td>{{ $c->products_count }}</td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('categories.edit', $c) }}">Ubah</a>
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('categories.destroy', $c) }}"
                              onsubmit="return confirm('Hapus kategori ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Belum ada kategori.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $categories->links() }}
@endsection