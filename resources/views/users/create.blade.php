@extends('layouts.app')
@section('title', 'Tambah Akun')
@section('content')
<div class="card narrow">
    <h1>Tambah Akun</h1>
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form')
    </form>
    <p class="muted"><a href="{{ route('users.index') }}">Kembali</a></p>
</div>
@endsection