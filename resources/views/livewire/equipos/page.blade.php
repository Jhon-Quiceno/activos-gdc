<x-layouts.app-shell title="Equipos">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Equipos')" :subtitle="__('Se identifican por serial y código de activo.')" />

        <livewire:equipos.index />
    </div>
</x-layouts.app-shell>
