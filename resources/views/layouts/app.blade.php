<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name', 'StudySync') }}</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-light">
    <div class="d-flex flex-column min-vh-100">

        @livewire('navigation-menu')

        @isset($header)
            <header class="bg-white border-bottom">
                <div class="container py-3">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-grow-1 py-4">
            <div class="container">
                {{ $slot }}
            </div>
        </main>

        <footer class="border-top bg-white py-3">
            <div class="container d-flex justify-content-between align-items-center small text-secondary">
                <span>&copy; {{ date('Y') }} StudySync</span>
                <span>Built for {{ config('app.competition_name', 'the campus hackathon') }}</span>
            </div>
        </footer>

    </div>

    @livewireScripts
</body>
</html>
