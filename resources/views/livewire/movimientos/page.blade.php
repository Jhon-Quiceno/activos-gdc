<x-layouts.app-shell :title="__('Responsables')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Responsables')"
            :subtitle="__('Personas a cargo de equipos, sus equipos y su formato de entrega consolidado.')"
        />

        <livewire:movimientos.index />
    </div>
</x-layouts.app-shell>
