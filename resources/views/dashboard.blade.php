@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1>Dashboard</h1>
<p>Halo, {{ auth()->user()->name }} ({{ auth()->user()->role }}).</p>
@endsection