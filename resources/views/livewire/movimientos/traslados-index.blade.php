{{--
    Página de la ruta `movimientos.traslados.index` (Route::view, sin parámetros).

    Route::view() solo renderiza esta vista tal cual, sin instanciar ningún
    componente Livewire: por eso esta vista es apenas el layout + la etiqueta
    <livewire:movimientos.traslados-listado />. La vista propia de ese
    componente vive en resources/views/livewire/movimientos/traslados-listado.blade.php
    (nombre distinto a propósito, para no chocar con este archivo).
--}}
<x-layouts.app-shell :title="__('Traslados')">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Traslados')" :subtitle="__('Elegí un equipo para registrar su traslado o cambio de responsable.')">
            <x-slot name="actions">
                <x-ui.button variant="secondary" :href="route('movimientos.index')">{{ __('Responsables') }}</x-ui.button>
                <x-ui.button variant="secondary" :href="route('movimientos.pendientes')">{{ __('Pendientes de firma') }}</x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <livewire:movimientos.traslados-listado />
    </div>
</x-layouts.app-shell>
