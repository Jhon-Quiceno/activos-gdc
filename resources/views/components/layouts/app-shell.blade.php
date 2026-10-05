@props(['title' => null])

@php
    $navItems = [
        ['label' => 'Inicio', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'inicio'],
        ['label' => 'Equipos', 'route' => 'equipos.index', 'pattern' => 'equipos.*', 'icon' => 'equipos'],
        ['label' => 'Movimientos', 'route' => 'movimientos.index', 'pattern' => 'movimientos.*', 'icon' => 'movimientos'],
        ['label' => 'Importación', 'route' => 'importacion.index', 'pattern' => 'importacion.*', 'icon' => 'importacion'],
        ['label' => 'Reportes', 'route' => 'reportes.index', 'pattern' => 'reportes.*', 'icon' => 'reportes'],
    ];

    $adminItems = [
        ['label' => 'Administración', 'route' => 'admin.index', 'pattern' => 'admin.*', 'icon' => 'admin'],
        ['label' => 'Etiquetas QR', 'route' => 'qr.index', 'pattern' => 'qr.*', 'icon' => 'qr'],
    ];

    // RF-30/RN-05: eventos cuyo movimiento aún no tiene los documentos firmados.
    $pendientesFirma = \App\Models\Evento::where('estado_firma', 'pendiente_de_firma')->count();
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
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">

        {{-- Overlay móvil --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/40 nav:hidden"
        ></div>

        {{-- Sidebar: SIEMPRE fijo al viewport (no se mueve con el scroll del contenido) --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-[248px] -translate-x-full flex-col bg-navy transition-transform duration-200 nav:translate-x-0"
            :class="sidebarOpen && '!translate-x-0'"
        >
            <div class="flex items-center gap-3 border-b border-white/10 px-4 py-[18px]">
                <div class="shrink-0 rounded-lg bg-white p-1.5">
                    <img src="{{ asset('images/logoGob.svg') }}" alt="Gobernación de Córdoba" class="h-8 w-auto">
                </div>
                <div class="min-w-0 leading-tight">
                    <p class="truncate font-display text-[16px] font-semibold text-white">{{ __('Hoja de Vida de Equipos') }}</p>
                    <p class="truncate text-[13px] text-white/60">{{ __('Dirección TIC · Gobernación de Córdoba') }}</p>
                </div>
            </div>

            <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 py-3">
                <ul class="space-y-0.5">
                    @foreach ($navItems as $item)
                        <x-layouts.nav-item :item="$item" />
                    @endforeach
                </ul>

                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-[.08em] text-[#9FB3CC]">
                    {{ __('Administración') }}
                </p>

                <ul class="space-y-0.5">
                    @foreach ($adminItems as $item)
                        <x-layouts.nav-item :item="$item" />
                    @endforeach
                </ul>
            </nav>
        </aside>

        {{-- Columna de contenido: desplazada por el ancho del sidebar fijo --}}
        <div class="flex min-h-screen flex-col nav:pl-[248px]">
            {{-- Barra superior: solo búsqueda global, notificaciones y usuario (el título de
                 cada pantalla va dentro del contenido, no aquí — ver <x-ui.page-header>). --}}
            <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-line bg-white px-4 py-2.5 nav:px-6">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    type="button"
                    class="shrink-0 rounded-lg p-2 text-ink hover:bg-app-bg nav:hidden"
                    aria-label="Abrir menú"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- En Equipos la búsqueda ya vive en la tabla de esa pantalla; no se repite aquí. --}}
                @unless(request()->routeIs('equipos.*'))
                    <form method="GET" action="{{ route('equipos.index') }}" class="relative w-full max-w-[560px]">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7" />
                            <path stroke-linecap="round" d="M21 21l-4.3-4.3" />
                        </svg>
                        <input
                            type="search"
                            name="q"
                            aria-label="{{ __('Buscar equipos') }}"
                            placeholder="{{ __('Buscar por serial, código de activo, responsable o cédula…') }}"
                            class="h-11 w-full rounded-lg border-line bg-app-bg pl-10 text-[15px] text-ink placeholder:text-ink-muted focus:border-primary focus:bg-white focus:ring-primary"
                        >
                    </form>
                @endunless

                <div class="ml-auto flex shrink-0 items-center gap-4">
                    <a
                        href="{{ route('movimientos.index') }}"
                        wire:navigate
                        class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-line text-ink transition hover:bg-app-bg"
                        aria-label="{{ __('Pendientes de firma: :count', ['count' => $pendientesFirma]) }}"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8a6 6 0 0112 0c0 7 3 8 3 8H3s3-1 3-8" />
                            <path stroke-linecap="round" d="M10 21h4" />
                        </svg>
                        @if($pendientesFirma > 0)
                            <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-danger-hover px-1 text-[11px] font-semibold text-white">
                                {{ $pendientesFirma > 99 ? '99+' : $pendientesFirma }}
                            </span>
                        @endif
                    </a>

                    <livewire:layout.topbar-user-menu />
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
