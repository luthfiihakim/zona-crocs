@extends('layouts.app')
@section('announce', 'Ukuran M11+ wajib PO')
@section('title', 'Katalog')
@section('content')
<h1>Katalog</h1>
<p>Halo, {{ auth()->user()->name }}. Katalog produk akan tampil di sini.</p>
@endsection