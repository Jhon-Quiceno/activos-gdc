<x-layouts.app-shell :title="__('Dependencias')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Dependencias')"
            :subtitle="__('Árbol oficial del Decreto 609. Las dependencias no se borran: se desactivan para conservar el historial (RN-20).')"
        />

        <livewire:admin.dependencias-panel />
    </div>
</x-layouts.app-shell>
