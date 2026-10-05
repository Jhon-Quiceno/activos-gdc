<x-layouts.app-shell title="Administración">
    <div class="space-y-6">
        <x-ui.page-header :title="__('Administración')" :subtitle="__('Usuarios y listas administrables del sistema.')" />

        <livewire:admin.index />
    </div>
</x-layouts.app-shell>
