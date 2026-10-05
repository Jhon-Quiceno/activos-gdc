<x-layouts.app-shell title="Reportes">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Reportes')" :subtitle="__('Consultas sobre todo el parque tecnológico, con exportación.')" />

        <livewire:reportes.index />
    </div>
</x-layouts.app-shell>
