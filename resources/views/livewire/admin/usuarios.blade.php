{{--
    Página de la ruta `admin.usuarios` (Route::view, sin parámetros).

    Route::view() solo renderiza esta vista tal cual, sin instanciar ningún
    componente Livewire: por eso esta vista es apenas el layout + la etiqueta
    <livewire:admin.usuarios-panel />, igual que el patrón ya usado en
    equipos/page-crear.blade.php y movimientos/pendientes.blade.php. El
    componente Livewire real (con la tabla y el formulario) vive en
    resources/views/livewire/admin/usuarios-panel.blade.php, con un nombre
    distinto al de esta vista para evitar que choquen.
--}}
<x-layouts.app-shell :title="__('Usuarios')">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Usuarios')"
            :subtitle="__('Solo el Administrador ve esta sección. Los usuarios se desactivan, nunca se eliminan.')"
        />

        <livewire:admin.usuarios-panel />
    </div>
</x-layouts.app-shell>
