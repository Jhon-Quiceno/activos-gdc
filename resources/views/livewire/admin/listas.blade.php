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
            :subtitle="__('Solo el Administrador ve esta sección. Estas listas alimentan los formularios; no se acepta texto libre en esos campos.')"
        />

        <livewire:admin.listas-panel />
    </div>
</x-layouts.app-shell>
