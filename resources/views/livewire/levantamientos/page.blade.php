<x-layouts.app-shell title="Levantamientos">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Levantamiento de activos')"
            :subtitle="__('Inventario por dependencia y proceso con acta de aceptación firmada (Manual GT-M4)')"
        />

        <livewire:levantamientos.index />
    </div>
</x-layouts.app-shell>
