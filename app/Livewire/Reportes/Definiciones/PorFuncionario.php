<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class PorFuncionario extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Por funcionario';
    }

    protected function organizar(Builder $q): Builder
    {
        return $this->unirAsignacionActual($q)
            ->whereNotNull('asig.persona_id')
            ->orderBy('per.nombre')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
            'Cédula'           => fn ($e) => $this->enmascararCedula($e->asignacionActual?->persona?->cedula),
            'Cargo'            => fn ($e) => $e->asignacionActual?->persona?->cargo,
            'Vinculación'      => fn ($e) => $e->asignacionActual?->persona?->tipo_vinculacion,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
        ];
    }
}