<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class Traslados extends ReporteEventoDefinicion
{
    public function titulo(): string
    {
        return 'Traslados';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->where('eventos.tipo', 'traslado_responsable')
            ->with([
                'asignacionesOrigen.sede',
                'asignacionesOrigen.piso',
                'asignacionesOrigen.dependencia',
                'asignacionesOrigen.persona',
            ]);
    }

    public function columnas(): array
    {
        // El destino es la asignación que originó este evento.
        $destino = fn ($e) => $e->asignacionesOrigen->first();

        return array_merge(
            ['Fecha' => fn ($e) => $this->fechaEvento($e)],
            $this->columnasEquipo(),
            [
                'Nueva sede'        => fn ($e) => $destino($e)?->sede?->nombre,
                'Nuevo piso'        => fn ($e) => $destino($e)?->piso?->numero,
                'Nueva dependencia' => fn ($e) => $destino($e)?->dependencia?->nombre,
                'Nuevo responsable' => fn ($e) => $destino($e)?->persona?->nombre,
                'Detalle'           => fn ($e) => $this->resumenValores($e->valores),
                'Descripción'       => fn ($e) => $e->descripcion,
                'Estado de firma'   => fn ($e) => $e->estado_firma,
                'Registrado por'    => fn ($e) => $this->autor($e),
            ],
        );
    }
}