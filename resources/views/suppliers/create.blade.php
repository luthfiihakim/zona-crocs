@extends('layouts.app')
@section('title', 'Tambah Supplier')
@section('content')
<div class="card narrow">
    <h1>Tambah Supplier</h1>
    <form method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        @include('suppliers._form')
    </form>
    <p class="muted"><a href="{{ route('suppliers.index') }}">Kembali</a></p>
</div>
@endsection