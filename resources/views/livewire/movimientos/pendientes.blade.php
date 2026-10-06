{{--
    Página de la ruta `movimientos.pendientes` (Route::view, sin parámetro).

    Wrapper simple: layout + <livewire:movimientos.pendientes-listado /> para que
    los filtros (tipo de evento, antigüedad) sean reactivos. El componente vive
    en PendientesListado.php / pendientes-listado.blade.php (nombres distintos a
    este archivo para no chocar con la convención de nombres de Livewire, igual
    que ya hacen traslado.blade.php / traslado-form.blade.php).
--}}
<x-layouts.app-shell :title="__('Pendientes de firma')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Pendientes de firma')"
            :subtitle="__('Movimientos que aún no tienen sus documentos firmados. Un evento queda completo al subir todos sus formatos.')"
        >
            <x-slot name="actions">
                {{-- TODO: exportación real a Excel pendiente de alcance futuro; por ahora el botón no tiene lógica. --}}
                <x-ui.button variant="secondary" size="md">
                    {{ __('Exportar a Excel') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <livewire:movimientos.pendientes-listado />
    </div>
</x-layouts.app-shell>
