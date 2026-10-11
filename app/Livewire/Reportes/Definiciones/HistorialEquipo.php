<?php

namespace App\Livewire\Reportes\Definiciones;

use Illuminate\Database\Eloquent\Builder;

class HistorialEquipo extends ReporteEventoDefinicion
{
    public function titulo(): string
    {
        return 'Historial por equipo';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Tipo de evento',
            'todas' => 'Todos',
            'opciones' => self::TIPOS_EVENTO,
        ];
    }

    /** Agrupado por equipo, del evento más reciente al más antiguo. */
    protected function organizar(Builder $q): Builder
    {
        return $q->orderBy('eventos.equipo_id')
            ->orderByDesc('eventos.fecha')
            ->orderByDesc('eventos.id');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        return parent::aplicarFiltros($q, $f)->when(
            filled($f['especifico'] ?? null),
            fn ($q) => $q->where('eventos.tipo', $f['especifico'])
        );
    }

    public function columnas(): array
    {
        return array_merge(
            $this->columnasEquipo(),
            [
                'Fecha'           => fn ($e) => $this->fechaEvento($e),
                'Evento'          => fn ($e) => $this->etiquetaTipo($e->tipo),
                'Descripción'     => fn ($e) => $e->descripcion,
                'Detalle'         => fn ($e) => $this->resumenValores($e->valores),
                'Estado de firma' => fn ($e) => $e->estado_firma,
                'Anula al evento' => fn ($e) => $e->evento_anulado_id,
                'Registrado por'  => fn ($e) => $this->autor($e),
            ],
        );
    }
}