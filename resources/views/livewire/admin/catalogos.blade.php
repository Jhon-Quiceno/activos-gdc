<x-layouts.app-shell :title="__('Catálogos')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Catálogos')"
            :subtitle="__('Sedes y pisos, municipios, procesos, cargos, proveedores y modelos del modelo v4.')"
        />
        <livewire:admin.catalogos-panel />
    </div>
</x-layouts.app-shell>
