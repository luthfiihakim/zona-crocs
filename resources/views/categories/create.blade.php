@extends('layouts.app')
@section('title', 'Tambah Kategori')
@section('content')
<div class="card narrow">
    <h1>Tambah Kategori</h1>
    <form method="POST" action="{{ route('categories.store') }}">
        @csrf
        @include('categories._form')
    </form>
    <p class="muted"><a href="{{ route('categories.index') }}">Kembali</a></p>
</div>
@endsection