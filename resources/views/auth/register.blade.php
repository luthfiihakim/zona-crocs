@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<div class="card narrow">
    <h1>Daftar</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>Nama
            <input type="text" name="name" value="{{ old('name') }}" required autofocus>
        </label>
        @error('name')<p class="err">{{ $message }}</p>@enderror
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>
        @error('email')<p class="err">{{ $message }}</p>@enderror
        <label>No. HP (opsional)
            <input type="text" name="phone" value="{{ old('phone') }}">
        </label>
        @error('phone')<p class="err">{{ $message }}</p>@enderror
        <label>Password
            <input type="password" name="password" required>
        </label>
        @error('password')<p class="err">{{ $message }}</p>@enderror
        <label>Ulangi password
            <input type="password" name="password_confirmation" required>
        </label>
        <button class="btn" type="submit">DAFTAR</button>
    </form>
    <p class="muted">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</div>
@endsection