<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class PorDependencia extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Por dependencia';
    }

    protected function organizar(Builder $q): Builder
    {
        return $this->unirAsignacionActual($q)
            ->whereNotNull('asig.dependencia_id')
            ->orderBy('dep.nombre')
            ->orderBy('sed.nombre')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Sede'             => fn ($e) => $e->asignacionActual?->sede?->nombre,
            'Piso'             => fn ($e) => $e->asignacionActual?->piso?->numero,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modelo,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
        ];
    }
}