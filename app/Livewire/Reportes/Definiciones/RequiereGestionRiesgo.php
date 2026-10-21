<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Models\ValoracionActivo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Activos que deben pasar a la matriz de gestión de riesgos (RF-54, GT-M4
 * 4.2.6): la valoración vigente marca `requiere_gestion_riesgo` cuando algún
 * criterio CID vale 4 o la importancia es alta o muy alta (RN-23).
 */
class RequiereGestionRiesgo extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Activos que requieren gestión de riesgos';
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->with('valoracionVigente')
            ->whereHas('valoracionVigente', fn ($v) => $v->where('requiere_gestion_riesgo', true));
    }

    public function columnas(): array
    {
        return [
            'ID ACTIVO'        => fn ($e) => $e->numero_activo,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Activo'           => fn ($e) => MatrizFor01::nombreActivo($e),
            'Sede'             => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Proceso'          => fn ($e) => $e->proceso?->nombre,
            'C'                => fn ($e) => $e->valoracionVigente?->confidencialidad,
            'I'                => fn ($e) => $e->valoracionVigente?->integridad,
            'D'                => fn ($e) => $e->valoracionVigente?->disponibilidad,
            'Importancia'      => fn ($e) => ValoracionActivo::IMPORTANCIAS[$e->valoracionVigente?->importancia] ?? null,
            'Criterio'         => fn ($e) => $this->criterio($e->valoracionVigente),
        ];
    }

    private function criterio(?ValoracionActivo $v): string
    {
        if (! $v) {
            return '';
        }

        $motivos = [];

        if ((int) $v->confidencialidad === 4 || (int) $v->integridad === 4 || (int) $v->disponibilidad === 4) {
            $motivos[] = 'Algún criterio CID = 4';
        }

        if (in_array($v->importancia, ['alta', 'muy_alta'], true)) {
            $motivos[] = 'Importancia '.(ValoracionActivo::IMPORTANCIAS[$v->importancia] ?? $v->importancia);
        }

        return implode(' · ', $motivos);
    }
}
