@extends('layouts.app')
@section('title', 'Supplier')
@section('content')
<div class="toolbar">
    <h1>Supplier</h1>
    <a class="btn" href="{{ route('suppliers.create') }}">+ Tambah Supplier</a>
</div>

<form method="GET" class="search">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau kontak...">
    <button class="btn btn-dark" type="submit">Cari</button>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nama</th><th>Kontak</th><th>Telepon</th><th>Pembelian</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($suppliers as $s)
            <tr>
                <td>{{ $s->name }}</td>
                <td>{{ $s->contact_person ?: '-' }}</td>
                <td>{{ $s->phone ?: '-' }}</td>
                <td>{{ $s->purchases_count }}</td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('suppliers.edit', $s) }}">Ubah</a>
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('suppliers.destroy', $s) }}"
                              onsubmit="return confirm('Nonaktifkan supplier ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Belum ada supplier.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $suppliers->links() }}
@endsection