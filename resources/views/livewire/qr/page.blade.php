<x-layouts.app-shell title="Etiquetas QR">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Etiquetas QR')" :subtitle="__('Generación, impresión y escaneo de códigos QR por equipo.')" />

        <livewire:qr.index />
    </div>
</x-layouts.app-shell>
