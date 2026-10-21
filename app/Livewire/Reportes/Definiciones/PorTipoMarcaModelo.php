<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\ModeloEquipo;
use Illuminate\Database\Eloquent\Builder;

class PorTipoMarcaModelo extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Por tipo, marca y modelo';
    }

    protected function organizar(Builder $q): Builder
    {
        // El modelo sale del catálogo (modelo_id): se une para ordenar por su
        // nombre, ya que la columna de texto equipos.modelo se borra en el cierre.
        return $this->unirTipoYMarca($q)
            ->leftJoin((new ModeloEquipo)->getTable().' as mod', 'mod.id', '=', 'equipos.modelo_id')
            ->orderBy('tip.nombre')
            ->orderBy('mar.nombre')
            ->orderBy('mod.nombre')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modeloEquipo?->nombre,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Funcionamiento'   => fn ($e) => $e->estadoFuncionamientoTexto(),
            'Propiedad'        => fn ($e) => $e->propiedad,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
        ];
    }
}
