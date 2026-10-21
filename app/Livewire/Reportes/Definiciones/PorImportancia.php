<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Models\ValoracionActivo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Activos valorados agrupados por importancia (muy alta, alta, media, baja).
 * Responde «¿cuántos activos hay de cada importancia, por dependencia?».
 */
class PorImportancia extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Activos por importancia';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->with('valoracionVigente')
            ->whereHas('valoracionVigente');
    }

    protected function organizar(Builder $q): Builder
    {
        // Muy alta primero: se ordena por el total CID descendente.
        return $q->withMax('valoracionVigente as orden_total', 'total')
            ->orderByDesc('orden_total')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Importancia' => fn ($e) => ValoracionActivo::IMPORTANCIAS[$e->valoracionVigente?->importancia] ?? null,
            'Total'       => fn ($e) => $e->valoracionVigente?->total,
            'ID ACTIVO'   => fn ($e) => $e->numero_activo,
            'Activo'      => fn ($e) => MatrizFor01::nombreActivo($e),
            'Sede'        => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia' => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable' => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
        ];
    }
}
