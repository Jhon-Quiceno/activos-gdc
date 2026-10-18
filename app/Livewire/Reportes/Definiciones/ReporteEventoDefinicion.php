<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base de los reportes cuyas filas son EVENTOS de la hoja de vida.
 * Los filtros de equipo se aplican al equipo del evento; las fechas, a la fecha del evento.
 */
abstract class ReporteEventoDefinicion extends ReporteDefinicion
{
    public const TIPOS_EVENTO = [
        'alta' => 'Alta',
        'traslado_responsable' => 'Traslado o cambio de responsable',
        'cambio_componente' => 'Cambio de componente',
        'diagnostico' => 'Diagnóstico',
        'baja' => 'Baja',
        'actualizacion_datos' => 'Actualización de datos',
        'anulacion_aclaracion' => 'Anulación o aclaración',
    ];

    public function etiquetaFecha(): string
    {
        return 'Fecha del evento';
    }

    protected function consultaBase(): Builder
    {
        return Evento::query()->with([
            'equipo.tipoEquipo',
            'equipo.marca',
            'equipo.asignacionActual.dependencia',
            'equipo.asignacionActual.persona',
            'usuario',
        ]);
    }

    protected function organizar(Builder $q): Builder
    {
        return $q->orderByDesc('eventos.fecha')->orderByDesc('eventos.id');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        // Las fechas se aplican al evento; el resto de filtros, al equipo del evento.
        $filtrosEquipo = array_merge($f, ['desde' => null, 'hasta' => null]);

        return $q
            ->whereHas('equipo', fn ($e) => parent::aplicarFiltros($e, $filtrosEquipo))
            ->when(filled($f['desde'] ?? null), fn ($q) => $q->whereDate('eventos.fecha', '>=', $f['desde']))
            ->when(filled($f['hasta'] ?? null), fn ($q) => $q->whereDate('eventos.fecha', '<=', $f['hasta']));
    }

    /** Columnas que identifican al equipo del evento. */
    protected function columnasEquipo(): array
    {
        return [
            'Serial'           => fn ($e) => $e->equipo?->serial,
            'Código de activo' => fn ($e) => $e->equipo?->codigo_activo,
            'Tipo'             => fn ($e) => $e->equipo?->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->equipo?->marca?->nombre,
        ];
    }

    protected function etiquetaTipo(?string $tipo): string
    {
        return self::TIPOS_EVENTO[$tipo] ?? (string) $tipo;
    }

    protected function fechaEvento($evento): ?string
    {
        return $evento->fecha?->format('d/m/Y H:i'); // RNF-14
    }

    /** Autor del evento. Si tu modelo User usa otro campo, cámbialo solo aquí. */
    protected function autor($evento): ?string
    {
        return $evento->usuario?->name;
    }

    protected function resumenValores(?array $valores): string
    {
        return collect($valores ?? [])
            ->map(fn ($v, $k) => $k . ': ' . (is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)))
            ->implode('; ');
    }
}