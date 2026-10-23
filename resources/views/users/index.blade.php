@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
<div class="toolbar">
    <h1>Pengguna</h1>
    <a class="btn" href="{{ route('users.create') }}">+ Tambah Akun</a>
</div>

<form method="GET" class="filters">
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email...">
    <select name="role">
        <option value="">Semua role</option>
        @foreach (['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer'] as $k => $label)
            <option value="{{ $k }}" @selected($role === $k)>{{ $label }}</option>
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
            <tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Dibuat</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($users as $u)
            <tr>
                <td>{{ $u->name }} @if ($u->id === auth()->id())<span class="muted">(kamu)</span>@endif</td>
                <td>{{ $u->email }}</td>
                <td>
                    <span class="badge {{ ['admin' => 'po', 'staff' => 'ok'][$u->role] ?? 'off' }}">{{ ucfirst($u->role) }}</span>
                </td>
                <td><span class="badge {{ $u->is_active ? 'ok' : 'off' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td>{{ $u->created_at->format('d/m/Y') }}</td>
                <td class="actions">
                    <a class="btn btn-outline btn-sm" href="{{ route('users.edit', $u) }}">Ubah</a>
                    @if ($u->id !== auth()->id())
                        <form method="POST" action="{{ route('users.toggle', $u) }}"
                              onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun ini?')">
                            @csrf
                            @method('PATCH')
                            <button class="btn {{ $u->is_active ? 'btn-danger' : 'btn-dark' }} btn-sm" type="submit">
                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Tidak ada akun.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $users->links() }}
@endsection