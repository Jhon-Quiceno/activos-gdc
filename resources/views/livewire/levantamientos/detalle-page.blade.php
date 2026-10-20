{{--
    Página de la ruta `levantamientos.show` (Route::view con {levantamiento}).
    Route::view() no hace binding implícito de modelo: {levantamiento} llega
    como el valor crudo de la URL y se resuelve aquí, igual que en las páginas
    de Movimientos.
--}}
@php
    $levantamiento = \App\Models\LevantamientoActivos::findOrFail($levantamiento);
@endphp

<x-layouts.app-shell :title="__('Levantamiento :codigo', ['codigo' => $levantamiento->codigo])">
    <livewire:levantamientos.detalle :levantamiento="$levantamiento" />
</x-layouts.app-shell>
