<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Builder;

class PendientesFirma extends ReporteEventoDefinicion
{
    public function titulo(): string
    {
        return 'Pendientes de firma';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Tipo de documento',
            'todas' => 'Todos',
            'opciones' => Documento::query()
                ->whereNotNull('tipo')->distinct()->orderBy('tipo')
                ->pluck('tipo', 'tipo')->all(),
        ];
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()->with('documentos');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        $tipoDocumento = $f['especifico'] ?? null;

        return parent::aplicarFiltros($q, $f)->whereHas(
            'documentos',
            fn ($d) => $d->where('firmado', false)
                ->when(filled($tipoDocumento), fn ($d) => $d->where('tipo', $tipoDocumento))
        );
    }

    public function columnas(): array
    {
        return array_merge(
            [
                'N.° de evento' => fn ($e) => $e->id,
                'Fecha'         => fn ($e) => $this->fechaEvento($e),
                'Movimiento'    => fn ($e) => $this->etiquetaTipo($e->tipo),
            ],
            $this->columnasEquipo(),
            [
                'Documentos sin firmar' => fn ($e) => $e->documentos
                    ->where('firmado', false)
                    ->map(fn ($d) => trim($d->tipo . ' ' . $d->consecutivo))
                    ->implode(', '),
                'Estado de firma'    => fn ($e) => $e->estado_firma,
                'Responsable actual' => fn ($e) => $e->equipo?->asignacionActual?->persona?->nombre,
                'Registrado por'     => fn ($e) => $this->autor($e),
            ],
        );
    }
}