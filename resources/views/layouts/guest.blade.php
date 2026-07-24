<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudySync') }}</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center min-vh-100 bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-9 col-md-6 col-lg-4">

                <div class="text-center mb-4">
                    <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex align-items-center gap-2" wire:navigate>
                        <x-application-logo style="width: 32px; height: 32px;" />
                        <span class="fw-bold fs-4" style="font-family: 'Sora', sans-serif; color: #14162B;">StudySync</span>
                    </a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        {{ $slot }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
