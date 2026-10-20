@extends('layouts.app')
@section('title', 'Ubah Kategori')
@section('content')
<div class="card narrow">
    <h1>Ubah Kategori</h1>
    <form method="POST" action="{{ route('categories.update', $category) }}">
        @csrf
        @method('PUT')
        @include('categories._form')
    </form>
    <p class="muted"><a href="{{ route('categories.index') }}">Kembali</a></p>
</div>
@endsection