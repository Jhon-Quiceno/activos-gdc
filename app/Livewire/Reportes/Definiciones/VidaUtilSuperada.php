<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

/**
 * Equipos que ya superaron su vida útil desde la fecha de adquisición (RF-52).
 * Misma regla que Equipo::vidaUtilSuperada(): vida_util_meses del equipo o, si no
 * tiene, la de su tipo.
 */
class VidaUtilSuperada extends ReporteDefinicion
{
    /** Fecha en que termina la vida útil (NULL si falta la adquisición o los meses). */
    private const FIN_VIDA_UTIL = 'DATE_ADD(equipos.fecha_adquisicion, INTERVAL COALESCE(equipos.vida_util_meses, tip.vida_util_meses) MONTH)';

    public function titulo(): string
    {
        return 'Vida útil superada';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->with('modeloEquipo')
            ->where('equipos.estado_ciclo_vida', '!=', 'dado_de_baja')
            ->whereNotNull('equipos.fecha_adquisicion');
    }

    /** Los que la superaron hace más tiempo, primero. */
    protected function organizar(Builder $q): Builder
    {
        return $this->unirTipoYMarca($q)
            ->whereRaw(self::FIN_VIDA_UTIL.' <= CURDATE()')
            ->orderByRaw(self::FIN_VIDA_UTIL)
            ->orderBy('equipos.id');
    }

    private function meses($e): ?int
    {
        return $e->vida_util_meses ?? $e->tipoEquipo?->vida_util_meses;
    }

    private function finVidaUtil($e)
    {
        $meses = $this->meses($e);

        return ($e->fecha_adquisicion && $meses !== null)
            ? $e->fecha_adquisicion->copy()->addMonths($meses)
            : null;
    }

    public function columnas(): array
    {
        return [
            'Serial'               => fn ($e) => $e->serial,
            'Código de activo'     => fn ($e) => $e->codigo_activo,
            'Tipo'                 => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'                => fn ($e) => $e->marca?->nombre,
            'Modelo'               => fn ($e) => $e->modeloEquipo?->nombre,
            'Fecha de adquisición' => fn ($e) => $e->fecha_adquisicion?->format('d/m/Y'),
            'Vida útil (meses)'    => fn ($e) => $this->meses($e),
            'Fin de vida útil'     => fn ($e) => $this->finVidaUtil($e)?->format('d/m/Y'),
            'Meses de exceso'      => fn ($e) => ($fin = $this->finVidaUtil($e)) ? (int) $fin->diffInMonths(today()) : null,
            'Sede'                 => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia'          => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'          => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
        ];
    }
}