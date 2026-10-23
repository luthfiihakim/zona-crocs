@extends('layouts.app')
@section('title', 'Ubah Akun')
@section('content')
<div class="card narrow">
    <h1>Ubah Akun</h1>
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users._form')
    </form>
    <p class="muted"><a href="{{ route('users.index') }}">Kembali</a></p>
</div>
@endsection