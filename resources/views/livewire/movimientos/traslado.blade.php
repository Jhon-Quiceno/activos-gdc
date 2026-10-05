{{--
    Página de la ruta `movimientos.traslado` (Route::view con {equipo}).

    Route::view() usa Illuminate\Routing\ViewController, que invoca con
    argumentos variádicos sin type-hint: NO hace binding implícito de modelo,
    así que {equipo} llega aquí como el valor crudo de la URL (string), no
    como una instancia de Equipo. Por eso se resuelve a mano, igual que ya
    hace livewire/equipos/show.blade.php.

    Esta vista tampoco instancia ningún componente Livewire por sí sola: es
    apenas el layout + <livewire:movimientos.traslado-form :equipo>. La vista
    propia de ese componente vive en traslado-form.blade.php (nombre distinto
    a propósito, para no chocar con este archivo).
--}}
@php
    $equipo = \App\Models\Equipo::findOrFail($equipo);
@endphp

<x-layouts.app-shell :title="__('Traslado · :serial', ['serial' => $equipo->serial])">
    <livewire:movimientos.traslado-form :equipo="$equipo" />
</x-layouts.app-shell>
