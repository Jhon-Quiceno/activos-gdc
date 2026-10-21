<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

/**
 * Equipos con garantía vigente que vence pronto (RF-52). Misma regla que
 * Equipo::garantiaPorVencer(): termina después de hoy y dentro de la ventana de días.
 */
class GarantiasPorVencer extends ReporteDefinicion
{
    public const DIAS_POR_DEFECTO = 60;

    public function titulo(): string
    {
        return 'Garantías por vencer';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Vence en los próximos',
            'todas' => self::DIAS_POR_DEFECTO.' días (por defecto)',
            'opciones' => [30 => '30 días', 90 => '90 días', 180 => '180 días'],
        ];
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->with('modeloEquipo')
            ->where('equipos.estado_ciclo_vida', '!=', 'dado_de_baja');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        $dias = (int) ($f['especifico'] ?? 0) ?: self::DIAS_POR_DEFECTO;

        return parent::aplicarFiltros($q, $f)
            ->whereNotNull('equipos.fecha_fin_garantia')
            ->whereDate('equipos.fecha_fin_garantia', '>', today())
            ->whereDate('equipos.fecha_fin_garantia', '<=', today()->addDays($dias));
    }

    /** La que vence primero, arriba. */
    protected function organizar(Builder $q): Builder
    {
        return $q->orderBy('equipos.fecha_fin_garantia')->orderBy('equipos.id');
    }

    public function columnas(): array
    {
        return [
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Modelo'           => fn ($e) => $e->modeloEquipo?->nombre,
            'Fin de garantía'  => fn ($e) => $e->fecha_fin_garantia?->format('d/m/Y'),
            'Días restantes'   => fn ($e) => $e->fecha_fin_garantia ? (int) today()->diffInDays($e->fecha_fin_garantia) : null,
            'N.° de contrato'  => fn ($e) => $e->numero_contrato,
            'N.° de factura'   => fn ($e) => $e->numero_factura,
            'Sede'             => fn ($e) => $e->asignacionActual?->piso?->sede?->nombre,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombreCompleto(),
        ];
    }
}