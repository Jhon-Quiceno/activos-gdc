<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class SinAntivirus extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Equipos sin antivirus';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->with('configuracionComputo.sistemaOperativo')
            ->whereHas('configuracionComputo', fn ($c) => $c->where('tiene_antivirus', false));
    }

    public function columnas(): array
    {
        return [
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Sistema operativo'=> fn ($e) => $e->configuracionComputo?->sistemaOperativo?->nombre,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Sede'             => fn ($e) => $e->asignacionActual?->sede?->nombre,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
        ];
    }
}