<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;

class ObsolescenciaSo extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Obsolescencia por sistema operativo';
    }

    protected function consultaBase(): Builder
    {
        return Equipo::query()
            ->with([
                'tipoEquipo',
                'configuracionComputo.sistemaOperativo',
                'asignacionActual.persona',
                'asignacionActual.dependencia',
            ])
            // Obsolescencia por fecha real de fin de soporte, no por el nombre
            // del sistema: un SO está sin soporte cuando su fin_soporte ya pasó.
            ->whereHas('configuracionComputo.sistemaOperativo', fn ($so) => $so
                ->whereNotNull('fin_soporte')
                ->whereDate('fin_soporte', '<=', now()->toDateString()));
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
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo ?? 'Sin código de activo',
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Sistema operativo'=> fn ($e) => $e->configuracionComputo?->sistemaOperativo?->nombre,
            'Fin de soporte'   => fn ($e) => $e->configuracionComputo?->sistemaOperativo?->fin_soporte?->format('d/m/Y'),
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto() ?? 'Sin asignar',
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre ?? '—',
        ];
    }
}