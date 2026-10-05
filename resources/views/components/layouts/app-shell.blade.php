@props(['title' => null])

@php
    $navItems = [
        ['label' => 'Inicio', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ['label' => 'Equipos', 'route' => 'equipos.index', 'pattern' => 'equipos.*'],
        ['label' => 'Movimientos', 'route' => 'movimientos.index', 'pattern' => 'movimientos.*'],
        ['label' => 'Importación', 'route' => 'importacion.index', 'pattern' => 'importacion.*'],
        ['label' => 'Reportes', 'route' => 'reportes.index', 'pattern' => 'reportes.*'],
        ['label' => 'Administración', 'route' => 'admin.index', 'pattern' => 'admin.*'],
        ['label' => 'Etiquetas QR', 'route' => 'qr.index', 'pattern' => 'qr.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name', 'Hoja de Vida de Equipos') }}</title>

    <!-- Fonts: Source Sans 3 (texto) + Work Sans (títulos / KPI) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Work+Sans:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen nav:grid nav:grid-cols-[248px_1fr]">

        {{-- Overlay móvil --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/40 nav:hidden"
        ></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-[248px] -translate-x-full flex-col bg-navy transition-transform duration-200 nav:static nav:translate-x-0"
            :class="sidebarOpen && '!translate-x-0'"
        >
            <div class="flex items-center gap-3 px-5 py-6">
                <div class="rounded-lg bg-white p-2">
                    <img src="{{ asset('images/logoGob.svg') }}" alt="Gobernación de Córdoba" class="h-9 w-auto">
                </div>
                <span class="font-display text-sm font-semibold leading-tight text-white">Hoja de Vida<br>de Equipos</span>
            </div>

            <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 pb-4">
                <ul class="space-y-1">
                    @foreach ($navItems as $item)
                        @php $isActive = request()->routeIs($item['pattern']); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                wire:navigate
                                class="group relative flex items-center gap-3 overflow-hidden rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors duration-150
                                    {{ $isActive ? 'bg-navy-active text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                            >
                                <span
                                    class="absolute inset-y-1 left-0 w-1 rounded-r-full bg-white transition-transform duration-200 ease-out
                                        {{ $isActive ? 'scale-y-100' : 'scale-y-0' }}"
                                ></span>
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full transition-colors duration-150 {{ $isActive ? 'bg-white' : 'bg-white/40 group-hover:bg-white/70' }}"></span>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <livewire:layout.sidebar-user-menu />
        </aside>

        {{-- Columna de contenido --}}
        <div class="flex min-h-screen flex-col">
            {{-- Barra superior (visible siempre; trae el botón hamburguesa en móvil) --}}
            <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-line bg-white px-4 py-3 nav:px-6">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    type="button"
                    class="rounded-lg p-2 text-ink hover:bg-app-bg nav:hidden"
                    aria-label="Abrir menú"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="min-w-0 flex-1">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h1 class="truncate font-display text-lg font-semibold text-ink">{{ $title ?? config('app.name') }}</h1>
                    @endisset
                </div>
            </header>

            <main
                x-data="{ shown: false }"
                x-init="requestAnimationFrame(() => shown = true)"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1'"
                class="flex-1 bg-app-bg p-4 transition-all duration-300 ease-out nav:p-6"
            >
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
