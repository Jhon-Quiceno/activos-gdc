<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class PorTipoMarcaModelo extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Por tipo, marca y modelo';
    }

    protected function organizar(Builder $q): Builder
    {
        return $this->unirTipoYMarca($q)
            ->orderBy('tip.nombre')
            ->orderBy('mar.nombre')
            ->orderBy('equipos.modelo')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modelo,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Funcionamiento'   => fn ($e) => $e->estado_funcionamiento,
            'Propiedad'        => fn ($e) => $e->propiedad,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
        ];
    }
}