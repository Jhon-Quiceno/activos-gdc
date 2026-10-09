<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\MotivoBaja;
use Illuminate\Database\Eloquent\Builder;

class DadosDeBaja extends ReporteEventoDefinicion
{
    public function titulo(): string
    {
        return 'Equipos dados de baja';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Motivo de baja',
            'todas' => 'Todos los motivos',
            'opciones' => MotivoBaja::query()->orderBy('nombre')->pluck('nombre', 'id')->all(),
        ];
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->where('eventos.tipo', 'baja')
            ->with('diagnostico.motivoBaja');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        return parent::aplicarFiltros($q, $f)->when(
            filled($f['especifico'] ?? null),
            fn ($q) => $q->whereHas('diagnostico', fn ($d) => $d->where('motivo_baja_id', $f['especifico']))
        );
    }

    public function columnas(): array
    {
        return array_merge(
            ['Fecha de baja' => fn ($e) => $this->fechaEvento($e)],
            $this->columnasEquipo(),
            [
                'Motivo'          => fn ($e) => $e->diagnostico?->motivoBaja?->nombre,
                'Estado hallado'  => fn ($e) => $e->diagnostico?->estado_encontrado,
                'Causa'           => fn ($e) => $e->diagnostico?->causa,
                'Recomendaciones' => fn ($e) => $e->diagnostico?->recomendaciones,
                'Registrado por'  => fn ($e) => $this->autor($e),
            ],
        );
    }
}