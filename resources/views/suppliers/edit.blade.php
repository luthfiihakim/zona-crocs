@extends('layouts.app')
@section('title', 'Ubah Supplier')
@section('content')
<div class="card narrow">
    <h1>Ubah Supplier</h1>
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        @include('suppliers._form')
    </form>
    <p class="muted"><a href="{{ route('suppliers.index') }}">Kembali</a></p>
</div>
@endsection