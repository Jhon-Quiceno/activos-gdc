{{--
    Página de la ruta `movimientos.baja` (Route::view con {equipo}).

    Route::view() no hace binding implícito de modelo (ver nota larga en
    traslado.blade.php): {equipo} llega como el valor crudo de la URL, así que
    se resuelve a mano antes de pasarlo al componente Livewire.

    Wrapper + <livewire:movimientos.baja-form :equipo>. La vista propia del
    componente vive en baja-form.blade.php para no chocar con este archivo.
--}}
@php
    $equipo = \App\Models\Equipo::findOrFail($equipo);
@endphp

<x-layouts.app-shell :title="__('Baja · :serial', ['serial' => $equipo->serial])">
    <livewire:movimientos.baja-form :equipo="$equipo" />
</x-layouts.app-shell>
