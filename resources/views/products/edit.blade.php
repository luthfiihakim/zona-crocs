@extends('layouts.app')
@section('title', 'Ubah Produk')
@section('content')
<div class="card narrow">
    <h1>Ubah Produk</h1>
    <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')
        @include('products._form')
    </form>
    <p class="muted"><a href="{{ route('products.show', $product) }}">Kembali</a></p>
</div>
@endsection