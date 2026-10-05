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
                {{ __('Este es el panel de inicio del Sistema de Hoja de Vida de Equipos. Los indicadores reales se conectarán aquí próximamente.') }}
            </p>
        </x-ui.card>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 nav:grid-cols-3">
            <x-ui.kpi-card value="—" label="{{ __('Equipos registrados') }}" />
            <x-ui.kpi-card value="—" label="{{ __('Movimientos del mes') }}" />
            <x-ui.kpi-card value="—" label="{{ __('Pendientes por verificar') }}" />
        </div>
    </div>
</x-layouts.app-shell>
