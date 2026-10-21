<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use App\Models\SistemaOperativo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Equipos de cómputo cuyo sistema operativo ya no tiene soporte del fabricante.
 * Usa la fecha real SistemaOperativo::fin_soporte, no el nombre «Windows 7».
 */
class ObsolescenciaSo extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Obsolescencia por sistema operativo';
    }

    /** Sistemas operativos con la fecha de fin de soporte cumplida (igual que SistemaOperativo::sinSoporte()). */
    public static function sistemasSinSoporte(): Collection
    {
        return SistemaOperativo::query()
            ->whereNotNull('fin_soporte')
            ->whereDate('fin_soporte', '<=', today())
            ->orderBy('fin_soporte')
            ->get();
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Sistema operativo',
            'todas' => 'Todos los que ya no tienen soporte',
            'opciones' => self::sistemasSinSoporte()->mapWithKeys(fn ($so) => [$so->id => $so->nombre])->all(),
        ];
    }

    protected function consultaBase(): Builder
    {
        return Equipo::query()
            ->with([
                'tipoEquipo',
                'configuracionComputo.sistemaOperativo',
                'asignacionActual.piso.sede',
                'asignacionActual.persona',
                'asignacionActual.dependencia',
            ])
            ->where('equipos.estado_ciclo_vida', '!=', 'dado_de_baja')
            ->whereHas('configuracionComputo.sistemaOperativo', fn ($so) => $so
                ->whereNotNull('fin_soporte')
                ->whereDate('fin_soporte', '<=', today()));
    }

    /** Los que perdieron el soporte hace más tiempo, primero. */
    protected function organizar(Builder $q): Builder
    {
        return $q->select('equipos.*')
            ->leftJoin((new ConfiguracionComputo)->getTable().' as cfg', 'cfg.equipo_id', '=', 'equipos.id')
            ->leftJoin((new SistemaOperativo)->getTable().' as sop', 'sop.id', '=', 'cfg.sistema_operativo_id')
            ->orderBy('sop.fin_soporte')
            ->orderBy('equipos.id');
    }

    public function consulta(array $filtros): Builder
    {
        return parent::consulta($filtros)->when(
            filled($filtros['especifico'] ?? null),
            fn ($q) => $q->whereHas(
                'configuracionComputo.sistemaOperativo',
                fn ($so) => $so->where('id', $filtros['especifico'])
            )
        );
    }

    public function columnas(): array
    {
        return [
            'Serial'            => fn ($e) => $e->serial,
            'Código de activo'  => fn ($e) => $e->codigo_activo ?? 'Sin código de activo',
            'Tipo'              => fn ($e) => $e->tipoEquipo?->nombre,
            'Sistema operativo' => fn ($e) => $e->configuracionComputo?->sistemaOperativo?->nombre,
            'Fin de soporte'    => fn ($e) => $e->configuracionComputo?->sistemaOperativo?->fin_soporte?->format('d/m/Y'),
            'Días sin soporte'  => fn ($e) => ($fin = $e->configuracionComputo?->sistemaOperativo?->fin_soporte) ? (int) $fin->diffInDays(today()) : null,
            'Sede'              => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia'       => fn ($e) => $e->asignacionActual?->dependencia?->nombre ?? '—',
            'Responsable'       => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto() ?? 'Sin asignar',
        ];
    }
}