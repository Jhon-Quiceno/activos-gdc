{{--
    Página de la ruta `password.obligatoria` (Route::view, sin parámetros).

    Mismo patrón que admin/usuarios.blade.php: layout + <livewire:.../>. El
    componente real vive en cambio-contrasena-obligatorio.blade.php.
--}}
<x-layouts.app-shell :title="__('Cambiar contraseña')">
    <livewire:admin.cambio-contrasena-obligatorio />
</x-layouts.app-shell>
