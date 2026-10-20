<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Zona Crocs')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="announce">Ukuran M11+ wajib PO</div>
    <header class="nav">
        <a class="brand" href="/">ZONA<span>CROCS</span></a>
        <nav>
            @auth
                <span class="who">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link" type="submit">Keluar</button>
                </form>
            @else
                <a href="{{ route('login') }}">Masuk</a>
            @endauth
        </nav>
    </header>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>