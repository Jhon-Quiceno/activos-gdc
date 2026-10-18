<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class PorSedePiso extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Por sede y piso';
    }

    protected function organizar(Builder $q): Builder
    {
        return $this->unirAsignacionActual($q)
            ->whereNotNull('asig.sede_id')
            ->orderBy('sed.nombre')
            ->orderBy('pis.numero')
            ->orderBy('dep.nombre')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Sede'             => fn ($e) => $e->asignacionActual?->sede?->nombre,
            'Piso'             => fn ($e) => $e->asignacionActual?->piso?->numero,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
        ];
    }
}