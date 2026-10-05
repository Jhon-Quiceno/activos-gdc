{{--
    Página de la ruta `admin.listas` (Route::view, sin parámetros).

    Mismo patrón que admin/usuarios.blade.php: wrapper estático + componente
    Livewire con nombre distinto (listas-panel) para evitar la colisión de
    nombres con esta vista.
--}}
<x-layouts.app-shell :title="__('Listas')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Listas')"
            :subtitle="__('Los elementos en uso no se eliminan: se desactivan para que el historial no se pierda.')"
        />

        <livewire:admin.listas-panel />
    </div>
</x-layouts.app-shell>
