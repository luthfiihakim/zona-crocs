<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Zona Crocs')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="announce">@yield('announce', 'Crocs Original dan Termurah Se Indonesia')</div>
    <header class="nav">
        <a class="brand" href="/">ZONA<span>CROCS</span></a>
        <nav>
            @auth
                @if (auth()->user()->isStaff())
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('products.*', 'variants.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Produk</a>
                    <a class="nav-link {{ request()->routeIs('photos.*') ? 'active' : '' }}" href="{{ route('photos.index') }}">Foto</a>
                    <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">Kategori</a>
                    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">Supplier</a>
                    <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">Pembelian</a>
                    <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">Penjualan</a>
                @endif
                @if (auth()->user()->isStaff())
                    <a class="nav-link {{ request()->routeIs('catalog*') ? 'active' : '' }}" href="{{ route('catalog') }}">Toko</a>
                @else
                    <a class="nav-link {{ request()->routeIs('catalog*') ? 'active' : '' }}" href="{{ route('catalog') }}">Katalog</a>
                    <a class="nav-link {{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">Keranjang ({{ collect(session('cart', []))->sum() }})</a>
                    <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">Pesanan</a>
                @endif
                @if (auth()->user()->isAdmin())
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Pengguna</a>
                @endif
                <span class="who">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-dark btn-sm" type="submit">Keluar</button>
                </form>
            @else
                <a class="nav-link" href="{{ route('login') }}">Masuk</a>
            @endauth
        </nav>
    </header>
    <main class="container">
        @if (session('success'))
            <div class="alert ok">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert bad">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>