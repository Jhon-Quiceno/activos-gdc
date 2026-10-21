<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Matriz de inventario de activos en el formato FOR.01 (RF-53). En pantalla
 * usa las mismas columnas y el mismo orden del formato; la exportación a Excel
 * la hace el componente con la clase MatrizFor01 (encabezado oficial y grupos).
 */
class MatrizInventarioFor01 extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Matriz de inventario (FOR.01)';
    }

    protected function consultaBase(): Builder
    {
        return Equipo::query()->with([
            'tipoEquipo',
            'marca',
            'modeloEquipo',
            'proceso',
            'asignacionActual.piso.sede',
            'asignacionActual.dependencia',
            'valoracionVigente',
        ]);
    }

    public function columnas(): array
    {
        $columnas = [];

        foreach (MatrizFor01::columnas() as $indice => $encabezado) {
            $columnas[$encabezado] = fn ($e) => MatrizFor01::fila($e)[$indice];
        }

        return $columnas;
    }
}
