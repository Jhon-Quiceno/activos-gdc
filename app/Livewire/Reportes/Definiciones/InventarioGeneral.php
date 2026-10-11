<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;

class InventarioGeneral extends ReporteDefinicion
{
    public function titulo(): string
    {
        return 'Inventario general';
    }

    protected function consultaBase(): Builder
    {
        return Equipo::query()->with([
            'tipoEquipo',
            'marca',
            'asignacionActual.sede',
            'asignacionActual.piso',
            'asignacionActual.dependencia',
            'asignacionActual.persona',
        ]);
    }

    public function columnas(): array
    {
        return [
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modelo,
            'Propiedad'        => fn ($e) => $e->propiedad,
            'Propietario'      => fn ($e) => $e->propietario_tercero,
            'Estado'           => fn ($e) => $e->estado_ciclo_vida,
            'Funcionamiento'   => fn ($e) => $e->estado_funcionamiento,
            'Verificación'     => fn ($e) => $e->verificacion,
            'Sede'             => fn ($e) => $e->asignacionActual?->sede?->nombre,
            'Piso'             => fn ($e) => $e->asignacionActual?->piso?->numero,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
            'Cédula'           => fn ($e) => $this->enmascararCedula($e->asignacionActual?->persona?->cedula),
            'Vinculación'      => fn ($e) => $e->asignacionActual?->persona?->tipo_vinculacion,
        ];
    }
}