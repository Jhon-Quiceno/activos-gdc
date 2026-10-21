<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Asignacion;
use App\Models\Dependencia;
use Illuminate\Database\Eloquent\Builder;

/**
 * Árbol de dependencias con los equipos de cada una y el total de su rama
 * (RF-60): «¿qué tiene cada secretaría, incluidas sus direcciones y áreas?».
 * Las filas son dependencias, en orden de árbol.
 */
class EquiposPorEstructura extends ReporteDefinicion
{
    private bool $cargado = false;

    /** @var array<int, int> */
    private array $orden = [];

    /** @var array<int, int> */
    private array $profundidad = [];

    /** @var array<int, string> */
    private array $tipo = [];

    /** @var array<int, int> */
    private array $propios = [];

    /** @var array<int, int> */
    private array $totalRama = [];

    public function titulo(): string
    {
        return 'Equipos por estructura';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Tipo de dependencia',
            'todas' => 'Todos los tipos',
            'opciones' => Dependencia::TIPOS,
        ];
    }

    private function cargar(): void
    {
        if ($this->cargado) {
            return;
        }
        $this->cargado = true;

        $padres = [];
        foreach (Dependencia::arbol(false) as $d) {
            $this->orden[] = $d->id;
            $this->profundidad[$d->id] = (int) $d->profundidad;
            $this->tipo[$d->id] = $d->tipo;
            $padres[$d->id] = $d->padre_id;
        }

        // Equipos con asignación abierta, sin contar los dados de baja.
        $this->propios = Asignacion::query()
            ->whereNull('fecha_fin')
            ->whereNotNull('dependencia_id')
            ->whereHas('equipo', fn ($e) => $e->where('estado_ciclo_vida', '!=', 'dado_de_baja'))
            ->selectRaw('dependencia_id, COUNT(*) as total')
            ->groupBy('dependencia_id')
            ->pluck('total', 'dependencia_id')
            ->all();

        // En orden de árbol los hijos van después del padre: al recorrer al revés,
        // cada hijo suma su total a su padre antes de que este se calcule.
        $acumulado = [];
        foreach (array_reverse($this->orden) as $id) {
            $this->totalRama[$id] = (int) ($this->propios[$id] ?? 0) + ($acumulado[$id] ?? 0);

            if (($padres[$id] ?? null) !== null) {
                $acumulado[$padres[$id]] = ($acumulado[$padres[$id]] ?? 0) + $this->totalRama[$id];
            }
        }
    }

    public function consulta(array $filtros): Builder
    {
        $this->cargar();
        $ids = $this->orden;

        if (filled($filtros['dependencia'] ?? null)) {
            $rama = empty($filtros['solo_dependencia'])
                ? Dependencia::idsDeRama((int) $filtros['dependencia'])
                : [(int) $filtros['dependencia']];

            $ids = array_values(array_intersect($ids, $rama));
        }

        if (filled($filtros['especifico'] ?? null)) {
            $ids = array_values(array_filter($ids, fn ($id) => ($this->tipo[$id] ?? null) === $filtros['especifico']));
        }

        $ids = array_map('intval', $ids);
        $q = Dependencia::query()->with('padre');

        if ($ids === []) {
            return $q->whereRaw('1 = 0');
        }

        // FIELD conserva el orden de árbol (cada dependencia seguida de sus hijas).
        return $q->whereIn('id', $ids)->orderByRaw('FIELD(id, '.implode(',', $ids).')');
    }

    public function columnas(): array
    {
        $this->cargar();

        return [
            'Dependencia'               => fn ($d) => str_repeat('· ', $this->profundidad[$d->id] ?? 0).$d->nombre,
            'Tipo'                      => fn ($d) => Dependencia::TIPOS[$d->tipo] ?? $d->tipo,
            'Estructura'                => fn ($d) => $d->ruta(),
            'Equipos propios'           => fn ($d) => (int) ($this->propios[$d->id] ?? 0),
            'Total con subdependencias' => fn ($d) => (int) ($this->totalRama[$d->id] ?? 0),
            'Estado'                    => fn ($d) => $d->activa ? 'Activa' : 'Inactiva',
        ];
    }
}