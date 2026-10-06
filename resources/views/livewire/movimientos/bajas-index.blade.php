{{--
    Página de la ruta `movimientos.bajas.index` (Route::view, sin parámetros).
    Mismo patrón que traslados-index.blade.php: wrapper + <livewire:...>, la
    vista propia del componente vive en bajas-listado.blade.php.
--}}
<x-layouts.app-shell :title="__('Bajas')">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Bajas')" :subtitle="__('Elegí un equipo para registrar su baja.')" />

        <livewire:movimientos.bajas-listado />
    </div>
</x-layouts.app-shell>
