<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\CambioComponente;
use App\Models\Componente;
use Illuminate\Database\Eloquent\Builder;

class CambiosComponentes extends ReporteEventoDefinicion
{
    public function titulo(): string
    {
        return 'Cambios de componentes';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Acción',
            'todas' => 'Todas',
            'opciones' => CambioComponente::query()
                ->whereNotNull('accion')->distinct()->orderBy('accion')
                ->pluck('accion', 'accion')->all(),
        ];
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()
            ->where('eventos.tipo', 'cambio_componente')
            ->with([
                'cambioComponente.componenteRetirado.tipoComponente',
                'cambioComponente.componenteInstalado.tipoComponente',
            ]);
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        return parent::aplicarFiltros($q, $f)->when(
            filled($f['especifico'] ?? null),
            fn ($q) => $q->whereHas('cambioComponente', fn ($c) => $c->where('accion', $f['especifico']))
        );
    }

    private function describir(?Componente $componente, ?string $serial): string
    {
        $serial = $serial ?: $componente?->serial;

        return implode(' · ', array_filter([
            $componente?->tipoComponente?->nombre,
            $componente?->capacidad_caracteristica,
            $componente?->marca,
            $serial ? 'S/N ' . $serial : null,
        ]));
    }

    public function columnas(): array
    {
        return array_merge(
            ['Fecha' => fn ($e) => $this->fechaEvento($e)],
            $this->columnasEquipo(),
            [
                'Acción'               => fn ($e) => $e->cambioComponente?->accion,
                'Componente retirado'  => fn ($e) => $this->describir($e->cambioComponente?->componenteRetirado, $e->cambioComponente?->serial_retirado),
                'Componente instalado' => fn ($e) => $this->describir($e->cambioComponente?->componenteInstalado, $e->cambioComponente?->serial_instalado),
                'Motivo'               => fn ($e) => $e->cambioComponente?->motivo,
                'Destino de lo retirado' => fn ($e) => $e->cambioComponente?->destino_retirado,
                'Registrado por'       => fn ($e) => $this->autor($e),
            ],
        );
    }
}