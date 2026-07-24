{{-- Lightweight layout for logged-out utility pages (GPA calculator, citation
     generator). No Livewire navigation-menu here since it assumes an authed
     user — this is just a static header with login/signup links. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'StudySync') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                <x-application-logo style="width: 26px; height: 26px;" />
                StudySync
            </a>
            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign up free</a>
                @endauth
            </div>
        </div>
    </nav>

    @isset($header)
        <div class="bg-white border-bottom">
            <div class="container py-3">{{ $header }}</div>
        </div>
    @endisset

    <main class="py-4">
        <div class="container">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
