<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Livewire\Reportes\Exportes\MatrizFor01;
use Illuminate\Database\Eloquent\Builder;

/**
 * Equipos a cargo de personas cuya vinculación termina en los próximos 90 días:
 * hay que planear la entrega antes del retiro.
 */
class PersonasPorRetirarse extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Personas por retirarse con equipos a cargo';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()->whereHas('asignacionActual.persona', fn ($p) => $p
            ->whereNotNull('fecha_fin_vinculacion')
            ->whereDate('fecha_fin_vinculacion', '>=', now()->toDateString())
            ->whereDate('fecha_fin_vinculacion', '<=', now()->addDays(90)->toDateString()));
    }

    public function columnas(): array
    {
        return [
            'Responsable'       => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
            'Cédula'            => fn ($e) => $this->enmascararCedula($e->asignacionActual?->persona?->numero_documento),
            'Fin de vinculación'=> fn ($e) => $e->asignacionActual?->persona?->fecha_fin_vinculacion?->format('d/m/Y'),
            'Vinculación'       => fn ($e) => $e->asignacionActual?->persona?->tipo_vinculacion,
            'Dependencia'       => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Serial'            => fn ($e) => $e->serial,
            'Código de activo'  => fn ($e) => $e->codigo_activo,
            'Activo'            => fn ($e) => MatrizFor01::nombreActivo($e),
        ];
    }
}
