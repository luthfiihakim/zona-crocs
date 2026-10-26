@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('content')
<div class="card narrow">
    <h1>Tambah Produk</h1>
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('products._form')
    </form>
    <p class="muted"><a href="{{ route('products.index') }}">Kembali</a></p>
</div>
@endsection