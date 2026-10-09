<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class Terceros extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Equipos de terceros';
    }

    protected function organizar(Builder $q): Builder
    {
        return $q->where('equipos.propiedad', 'tercero')
            ->orderBy('equipos.propietario_tercero')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Propietario'      => fn ($e) => $e->propietario_tercero,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modelo,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
        ];
    }
}