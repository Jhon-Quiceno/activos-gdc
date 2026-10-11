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
            ->whereHas('configuracionComputo.sistemaOperativo', function ($query) {
                $query->where(function ($so) {
                    $so->where('nombre', 'like', '%7%')
                        ->orWhere('nombre', 'like', '%8%');
                });
            });
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
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre ?? 'Sin asignar',
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre ?? '—',
        ];
    }
}