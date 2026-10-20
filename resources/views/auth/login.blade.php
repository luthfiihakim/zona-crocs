@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<div class="card narrow">
    <h1>Masuk</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        @error('email')<p class="err">{{ $message }}</p>@enderror
        <label>Password
            <input type="password" name="password" required>
        </label>
        <label class="check"><input type="checkbox" name="remember"> Ingat saya</label>
        <button class="btn" type="submit">MASUK</button>
    </form>
    <p class="muted">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai customer</a></p>
</div>
@endsection