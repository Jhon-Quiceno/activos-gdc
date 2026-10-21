<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;

/**
 * Personas cuya vinculación termina pronto (o ya terminó) y que todavía tienen
 * equipos a cargo. Las filas son personas, no equipos. El documento sale
 * enmascarado (RN-12).
 */
class PersonasPorRetirarse extends ReporteDefinicion
{
    public const VENTANA_POR_DEFECTO = 60;

    public function titulo(): string
    {
        return 'Personas por retirarse con equipos a cargo';
    }

    public function etiquetaFecha(): string
    {
        return 'Fin de vinculación';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Termina en los próximos',
            'todas' => self::VENTANA_POR_DEFECTO.' días (por defecto)',
            'opciones' => [30 => '30 días', 90 => '90 días', 180 => '180 días'],
        ];
    }

    /** Asignaciones abiertas de equipos que no están dados de baja. */
    private function abiertas(Builder $asignaciones)
    {
        return $asignaciones->whereNull('fecha_fin')
            ->whereHas('equipo', fn ($e) => $e->where('estado_ciclo_vida', '!=', 'dado_de_baja'));
    }

    public function consulta(array $filtros): Builder
    {
        $q = Persona::query()
            ->with(['cargoOficial', 'dependencia'])
            ->withCount(['asignaciones as equipos_a_cargo' => fn ($a) => $this->abiertas($a)])
            ->whereNotNull('fecha_fin_vinculacion')
            ->whereHas('asignaciones', fn ($a) => $this->abiertas($a));

        // Con fechas elegidas se usa ese rango; si no, la ventana de días (incluye a quien ya terminó).
        if (filled($filtros['desde'] ?? null) || filled($filtros['hasta'] ?? null)) {
            $q->when(filled($filtros['desde'] ?? null), fn ($q) => $q->whereDate('fecha_fin_vinculacion', '>=', $filtros['desde']))
                ->when(filled($filtros['hasta'] ?? null), fn ($q) => $q->whereDate('fecha_fin_vinculacion', '<=', $filtros['hasta']));
        } else {
            $dias = (int) ($filtros['especifico'] ?? 0) ?: self::VENTANA_POR_DEFECTO;
            $q->whereDate('fecha_fin_vinculacion', '<=', today()->addDays($dias));
        }

        return $q
            ->when(filled($filtros['vinculacion'] ?? null), fn ($q) => $q->where('tipo_vinculacion', $filtros['vinculacion']))
            ->when(filled($filtros['persona'] ?? null), fn ($q) => $q->whereKey($filtros['persona']))
            ->when(filled($filtros['dependencia'] ?? null), function ($q) use ($filtros) {
                $ids = empty($filtros['solo_dependencia'])
                    ? \App\Models\Dependencia::idsDeRama((int) $filtros['dependencia'])
                    : [(int) $filtros['dependencia']];

                $q->whereIn('dependencia_id', $ids);
            })
            ->orderBy('fecha_fin_vinculacion') // los que ya terminaron, primero
            ->orderBy('apellidos')
            ->orderBy('nombres');
    }

    private function situacion(Persona $p): string
    {
        $dias = (int) today()->diffInDays($p->fecha_fin_vinculacion, false);

        return match (true) {
            $dias < 0 => 'Terminó hace '.abs($dias).' días',
            $dias === 0 => 'Termina hoy',
            default => 'Termina en '.$dias.' días',
        };
    }

    public function columnas(): array
    {
        return [
            'Responsable'       => fn ($p) => $p->nombreCompleto(),
            'Documento'         => fn ($p) => trim($p->tipo_documento.' '.$this->enmascararCedula($p->numero_documento)),
            'Vinculación'       => fn ($p) => Persona::VINCULACIONES[$p->tipo_vinculacion] ?? $p->tipo_vinculacion,
            'Cargo'             => fn ($p) => $p->cargoTexto(),
            'Dependencia'       => fn ($p) => $p->dependencia?->nombre,
            'N.° de contrato'   => fn ($p) => $p->numero_contrato,
            'Fin de vinculación' => fn ($p) => $p->fecha_fin_vinculacion?->format('d/m/Y'),
            'Situación'         => fn ($p) => $this->situacion($p),
            'Equipos a cargo'   => fn ($p) => (int) $p->equipos_a_cargo,
            'Activo'            => fn ($p) => $p->activo ? 'Sí' : 'No',
        ];
    }
}