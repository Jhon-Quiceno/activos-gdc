<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Source Sans 3 (texto) + Work Sans (títulos) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Work+Sans:wght@500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-app-login">
            <div>
                <a href="/" wire:navigate>
                    <img src="{{ asset('images/logoGob.svg') }}" alt="Gobernación de Córdoba" class="h-24 w-auto">
                </a>
            </div>

            <div class="w-full sm:max-w-[420px] mt-6 border border-line bg-white overflow-hidden rounded-lg p-6 sm:p-9">
                <div class="mb-5">
                    <h1 class="font-display text-[24px] font-semibold text-ink">{{ __('Hoja de Vida de Equipos') }}</h1>
                    <p class="mt-1.5 text-ink-muted">{{ __('Dirección TIC · Gobernación de Córdoba') }}</p>
                </div>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
