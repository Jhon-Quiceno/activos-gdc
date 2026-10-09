<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class ReporteDefinicion
{
    public const ETIQUETAS_ESTADO = [
        'en_servicio' => 'En servicio',
        'sin_asignar' => 'Sin asignar',
        'dado_de_baja' => 'Dado de baja',
    ];

    abstract public function titulo(): string;

    /** ['Encabezado' => fn ($equipo) => valor, ...] */
    abstract public function columnas(): array;

    /** Consulta base. Cada reporte puede sobrescribirla. */
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

    /** Une joins, restricciones propias y orden del reporte. Por defecto, lo más reciente primero. */
    protected function organizar(Builder $q): Builder
    {
        return $q->orderByDesc('equipos.id');
    }

    public function consulta(array $filtros): Builder
    {
        return $this->organizar($this->aplicarFiltros($this->consultaBase(), $filtros));
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        return $q
            ->when(filled($f['estado'] ?? null), fn ($q) => $q->where('equipos.estado_ciclo_vida', $f['estado']))
            ->when(filled($f['tipo'] ?? null), fn ($q) => $q->where('equipos.tipo_equipo_id', $f['tipo']))
            ->when(filled($f['marca'] ?? null), fn ($q) => $q->where('equipos.marca_id', $f['marca']))
            ->when(filled($f['propiedad'] ?? null), fn ($q) => $q->where('equipos.propiedad', $f['propiedad']))
            ->when(filled($f['desde'] ?? null), fn ($q) => $q->whereDate('equipos.created_at', '>=', $f['desde']))
            ->when(filled($f['hasta'] ?? null), fn ($q) => $q->whereDate('equipos.created_at', '<=', $f['hasta']))
            // Ubicación y responsable: siempre sobre la asignación actual (fecha_fin nulo)
            ->when(filled($f['sede'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual', fn ($a) => $a->where('sede_id', $f['sede'])
            ))
            ->when(filled($f['piso'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual', fn ($a) => $a->where('piso_id', $f['piso'])
            ))
            ->when(filled($f['dependencia'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual', fn ($a) => $a->where('dependencia_id', $f['dependencia'])
            ))
            ->when(filled($f['persona'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual', fn ($a) => $a->where('persona_id', $f['persona'])
            ))
            ->when(filled($f['vinculacion'] ?? null), fn ($q) => $q->whereHas(
                'asignacionActual.persona', fn ($p) => $p->where('tipo_vinculacion', $f['vinculacion'])
            ));
    }

    /**
     * Une la asignación abierta del equipo con sus tablas, para poder ordenar.
     * Alias disponibles: asig, dep, sed, pis, per.
     */
    protected function unirAsignacionActual(Builder $q): Builder
    {
        return $q->select('equipos.*')
            ->leftJoin((new Asignacion)->getTable().' as asig', fn ($j) => $j
                ->on('asig.equipo_id', '=', 'equipos.id')
                ->whereNull('asig.fecha_fin'))
            ->leftJoin((new Dependencia)->getTable().' as dep', 'dep.id', '=', 'asig.dependencia_id')
            ->leftJoin((new Sede)->getTable().' as sed', 'sed.id', '=', 'asig.sede_id')
            ->leftJoin((new Piso)->getTable().' as pis', 'pis.id', '=', 'asig.piso_id')
            ->leftJoin((new Persona)->getTable().' as per', 'per.id', '=', 'asig.persona_id');
    }

    /** Une tipo y marca del equipo. Alias: tip, mar. */
    protected function unirTipoYMarca(Builder $q): Builder
    {
        return $q->select('equipos.*')
            ->leftJoin((new TipoEquipo)->getTable().' as tip', 'tip.id', '=', 'equipos.tipo_equipo_id')
            ->leftJoin((new Marca)->getTable().' as mar', 'mar.id', '=', 'equipos.marca_id');
    }

    /** Filas listas para exportar (todas, sin paginar). */
    public function filas(array $filtros): Collection
    {
        $columnas = $this->columnas();

        return $this->consulta($filtros)->get()->map(
            fn ($modelo) => collect($columnas)->map(fn ($fn) => $fn($modelo))->values()->all()
        );
    }

    protected function etiquetaEstado(?string $estado): string
    {
        return self::ETIQUETAS_ESTADO[$estado] ?? (string) $estado;
    }

    /** RN-12: la cédula nunca sale completa en listados ni exportaciones. */
    protected function enmascararCedula(?string $cedula): string
    {
        return blank($cedula) ? '' : '****' . substr($cedula, -4);
    }
}