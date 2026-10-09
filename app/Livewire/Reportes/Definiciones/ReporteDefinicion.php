<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class ReporteDefinicion
{
    abstract public function titulo(): string;

    /** Consulta base del reporte, sin filtros. */
    abstract protected function consultaBase(): Builder;

    /** ['Encabezado' => fn ($equipo) => valor, ...] */
    abstract public function columnas(): array;

    /** $filtros: sede, dependencia, estado, especifico. */
    public function consulta(array $filtros): Builder
    {
        return $this->aplicarFiltros($this->consultaBase(), $filtros)
            ->orderByDesc('equipos.id');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        return $q
            ->when(filled($f['estado'] ?? null), fn ($q) => $q->where('estado_ciclo_vida', $f['estado']))
            ->when(filled($f['sede'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual', fn ($a) => $a->where('sede_id', $f['sede'])
            ))
            ->when(filled($f['dependencia'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual', fn ($a) => $a->where('dependencia_id', $f['dependencia'])
            ));
    }

    /** Filas listas para exportar (todas, sin paginar). */
    public function filas(array $filtros): Collection
    {
        $columnas = $this->columnas();

        return $this->consulta($filtros)->get()->map(
            fn ($modelo) => collect($columnas)->map(fn ($fn) => $fn($modelo))->values()->all()
        );
    }

    /** RN-12: la cédula nunca sale completa en listados ni exportaciones. */
    protected function enmascararCedula(?string $cedula): string
    {
        return blank($cedula) ? '' : '****' . substr($cedula, -4);
    }
}