{{--
    Página de la ruta `admin.tipos-evento` (Route::view, sin parámetros).

    Mismo patrón que admin/usuarios.blade.php y admin/listas.blade.php: wrapper
    estático + componente Livewire con nombre distinto (tipos-evento-panel)
    para evitar la colisión de nombres con esta vista.
--}}
<x-layouts.app-shell :title="__('Tipos de evento')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Tipos de evento')"
            :subtitle="__('RF-13: tipos de evento de la hoja de vida. Los 8 del sistema no se editan ni se desactivan; un tipo configurable nuevo se puede registrar sobre un equipo sin desarrollo.')"
        />

        <livewire:admin.tipos-evento-panel />
    </div>
</x-layouts.app-shell>
