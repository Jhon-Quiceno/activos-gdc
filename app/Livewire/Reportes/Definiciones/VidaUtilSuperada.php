<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Models\TipoEquipo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Equipos que superaron su vida útil desde la fecha de adquisición (RF-52). La
 * vida útil sale del propio equipo o, si no la tiene, de su tipo de equipo.
 */
class VidaUtilSuperada extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Vida útil superada';
    }

    protected function consultaBase(): Builder
    {
        $tipos = (new TipoEquipo)->getTable();

        return parent::consultaBase()
            ->select('equipos.*')
            ->leftJoin($tipos.' as tip_vu', 'tip_vu.id', '=', 'equipos.tipo_equipo_id')
            ->whereNotNull('equipos.fecha_adquisicion')
            ->whereRaw('COALESCE(equipos.vida_util_meses, tip_vu.vida_util_meses) IS NOT NULL')
            ->whereRaw('DATE_ADD(equipos.fecha_adquisicion, INTERVAL COALESCE(equipos.vida_util_meses, tip_vu.vida_util_meses) MONTH) < ?', [now()->toDateString()]);
    }

    protected function organizar(Builder $q): Builder
    {
        return $q->orderBy('equipos.fecha_adquisicion');
    }

    public function columnas(): array
    {
        return [
            'Serial'             => fn ($e) => $e->serial,
            'Código de activo'   => fn ($e) => $e->codigo_activo,
            'Activo'             => fn ($e) => MatrizFor01::nombreActivo($e),
            'Fecha adquisición'  => fn ($e) => $e->fecha_adquisicion?->format('d/m/Y'),
            'Vida útil (meses)'  => fn ($e) => $e->vida_util_meses ?? $e->tipoEquipo?->vida_util_meses,
            'Sede'               => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia'        => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'        => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
        ];
    }
}
