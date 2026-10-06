<x-layouts.app-shell title="Movimientos">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Movimientos')" :subtitle="__('Traslados, cambios de responsable, diagnósticos y bajas.')" />

        <livewire:movimientos.index />
    </div>
</x-layouts.app-shell>
