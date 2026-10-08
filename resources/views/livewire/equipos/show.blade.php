{{--
    Página de la ruta `equipos.show` (Route::view, recibe el id en $equipo).
    Solo arma el layout; la hoja de vida es el componente Livewire
    App\Livewire\Equipos\HojaDeVida (vista hoja-de-vida.blade.php).
--}}
@php
    $registro = \App\Models\Equipo::findOrFail($equipo);
@endphp

<x-layouts.app-shell :title="$registro->codigo_activo ?? $registro->serial">
    <livewire:equipos.hoja-de-vida :equipo="$registro" />
</x-layouts.app-shell>
