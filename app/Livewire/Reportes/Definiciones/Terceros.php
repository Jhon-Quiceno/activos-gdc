<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;

class Terceros extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Equipos de terceros';
    }

    protected function organizar(Builder $q): Builder
    {
        // El propietario sale del catálogo de proveedores (proveedor_tercero_id):
        // se une para ordenar por razón social, ya que equipos.propietario_tercero
        // (texto) se borra en la migración de cierre.
        return $q->where('equipos.propiedad', 'tercero')
            ->select('equipos.*')
            ->leftJoin((new Proveedor)->getTable().' as prov_ter', 'prov_ter.id', '=', 'equipos.proveedor_tercero_id')
            ->orderBy('prov_ter.razon_social')
            ->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Propietario'      => fn ($e) => $e->proveedorTercero?->razon_social,
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modeloEquipo?->nombre,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
        ];
    }
}
