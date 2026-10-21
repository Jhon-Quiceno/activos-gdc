<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Livewire\Reportes\Exportes\MatrizFor01;
use Illuminate\Database\Eloquent\Builder;

/**
 * Equipos con garantía vigente que vence en los próximos 60 días (RF-52).
 */
class GarantiasPorVencer extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Garantías por vencer';
    }

    public function etiquetaFecha(): string
    {
        return 'Fin de garantía';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->whereNotNull('equipos.fecha_fin_garantia')
            ->whereDate('equipos.fecha_fin_garantia', '>=', now()->toDateString())
            ->whereDate('equipos.fecha_fin_garantia', '<=', now()->addDays(60)->toDateString());
    }

    protected function organizar(Builder $q): Builder
    {
        return $q->orderBy('equipos.fecha_fin_garantia');
    }

    public function columnas(): array
    {
        return [
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Activo'           => fn ($e) => MatrizFor01::nombreActivo($e),
            'Fin de garantía'  => fn ($e) => $e->fecha_fin_garantia?->format('d/m/Y'),
            'Días restantes'   => fn ($e) => $e->fecha_fin_garantia ? (int) now()->startOfDay()->diffInDays($e->fecha_fin_garantia, false) : null,
            'Sede'             => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
        ];
    }
}
