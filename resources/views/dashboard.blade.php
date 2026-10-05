@php
    $totalEquipos = \App\Models\Equipo::count();

    $movimientosMes = \App\Models\Evento::whereMonth('fecha', now()->month)
        ->whereYear('fecha', now()->year)
        ->count();

    $pendientesVerificar = \App\Models\Equipo::where('verificacion', 'pendiente_de_verificar')->count();

    $porcentajePendientes = $totalEquipos > 0
        ? (int) round(($pendientesVerificar / $totalEquipos) * 100)
        : 0;

    $tipoEventoLabels = [
        'alta' => __('Alta'),
        'traslado_responsable' => __('Traslado de responsable'),
        'cambio_componente' => __('Cambio de componente'),
        'diagnostico' => __('Diagnóstico'),
        'baja' => __('Baja'),
        'actualizacion_datos' => __('Actualización de datos'),
        'anulacion_aclaracion' => __('Anulación / aclaración'),
    ];

    $eventosRecientes = \App\Models\Evento::with(['equipo', 'usuario'])
        ->latest('fecha')
        ->take(5)
        ->get()
        ->map(fn (\App\Models\Evento $evento) => [
            'date' => optional($evento->fecha)->translatedFormat('d M Y, H:i'),
            'type' => trim(($tipoEventoLabels[$evento->tipo] ?? $evento->tipo)
                . ($evento->equipo?->codigo_activo ? ' — ' . $evento->equipo->codigo_activo : '')),
            'description' => $evento->descripcion,
            'author' => $evento->usuario?->name,
        ]);
@endphp

<x-layouts.app-shell title="Inicio">
    <x-slot name="header">
        <h1 class="truncate font-display text-lg font-semibold text-ink">{{ __('Inicio') }}</h1>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <p class="font-display text-xl font-semibold text-ink">
                {{ __('Bienvenido, :name', ['name' => auth()->user()->name]) }}
            </p>
            <p class="mt-2 text-sm text-ink-muted">
                {{ __('Este es el panel de inicio del Sistema de Hoja de Vida de Equipos.') }}
            </p>
        </x-ui.card>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 nav:grid-cols-3">
            <x-ui.kpi-card :value="$totalEquipos" label="{{ __('Equipos registrados') }}" accent="primary">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.129V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card :value="$movimientosMes" label="{{ __('Movimientos del mes') }}" accent="info">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 3m0 0l-4.5 4.5M21 3H7.5" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card
                :value="$pendientesVerificar"
                label="{{ __('Pendientes por verificar') }}"
                :accent="$pendientesVerificar > 0 ? 'warning' : 'success'"
                :progress="$porcentajePendientes"
                :progress-label="__(':pct% del total de equipos', ['pct' => $porcentajePendientes])"
            >
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>
        </div>

        <x-ui.card :padding="false">
            <div class="border-b border-line px-5 py-4">
                <p class="font-display text-lg font-semibold text-ink">{{ __('Actividad reciente') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Últimos eventos registrados en la hoja de vida de los equipos.') }}</p>
            </div>

            <div class="px-5 py-4">
                <x-ui.timeline :events="$eventosRecientes" />
            </div>
        </x-ui.card>
    </div>
</x-layouts.app-shell>
