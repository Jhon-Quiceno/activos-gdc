<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class PorEstado extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Por estado';
    }

    protected function organizar(Builder $q): Builder
    {
        return $q->orderBy('equipos.estado_ciclo_vida')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Funcionamiento'   => fn ($e) => $e->estado_funcionamiento,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modelo,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
        ];
    }
}