@extends('layouts.app')
@section('title', 'Katalog')
@section('content')
<h1>Katalog</h1>
<p>Halo, {{ auth()->user()->name }}. Katalog produk akan tampil di sini.</p>
@endsection