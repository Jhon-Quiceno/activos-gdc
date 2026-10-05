<x-layouts.app-shell title="Reportes">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Reportes')" :subtitle="__('15 reportes con filtros combinables · exportables a Excel y PDF')" />

        <livewire:reportes.index />
    </div>
</x-layouts.app-shell>
