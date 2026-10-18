{{--
    Página de la ruta `movimientos.formato-baja` (Route::view con {evento}).

    Route::view() no hace binding implícito de modelo: {evento} llega como el
    valor crudo de la URL, así que se resuelve a mano.

    Vista previa imprimible del formato de baja. Usa exactamente la misma
    plantilla que el PDF (livewire/movimientos/pdf/partials/baja.blade.php) y los
    mismos datos (FormatosPdf::datos), para que lo que se ve en pantalla sea lo
    que se imprime y se firma.
--}}
@php
    $evento = \App\Models\Evento::findOrFail($evento);
    $datos = app(\App\Livewire\Movimientos\Soporte\FormatosPdf::class)->datos($evento, 'formato_baja');
@endphp

@extends('layouts.documento')

@section('titulo', $datos['titulo'].' · '.$datos['consecutivo'])

@section('contenido')
    @include('livewire.movimientos.pdf.partials.estilos')
    @include('livewire.movimientos.pdf.partials.baja', $datos + ['modoPdf' => false])
@endsection
