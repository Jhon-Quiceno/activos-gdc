<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Equipo;
use App\Models\Evento;
use App\Models\PuestoTrabajo;
use Illuminate\Support\Collection;

/**
 * Puestos de trabajo en los movimientos (RF-05, parte del traslado).
 *
 * La fuente de verdad del puesto de un equipo es su asignación abierta
 * (`asignaciones.puesto_trabajo_id`), igual que el piso (decisión del líder en
 * base-de-datos.md, F3-1). La asignación del equipo al puesto la hace el bloque
 * de Equipos; aquí solo se leen los equipos de un puesto y se agrupan los
 * traslados que se hacen juntos.
 *
 * Un traslado del puesto completo crea un evento por equipo (cada hoja de vida
 * tiene el suyo, RN-06), pero un solo juego de formatos para todo el puesto.
 * Los eventos del mismo traslado se reconocen por `eventos.valores`:
 *
 *     ['traslado_puesto' => ['grupo' => uuid, 'puesto_trabajo_id' => 3,
 *                             'puesto' => 'PT-003', 'equipos' => 4, ...]]
 */
class PuestosDeTrabajo
{
    public const CLAVE = 'traslado_puesto';

    /**
     * Equipos que están hoy en el puesto: asignación abierta con ese puesto y
     * equipo que no está dado de baja.
     *
     * @return Collection<int, Equipo>
     */
    public function equiposDelPuesto(PuestoTrabajo|int $puesto): Collection
    {
        $puestoId = $puesto instanceof PuestoTrabajo ? $puesto->id : $puesto;

        return Equipo::query()
            ->whereHas('asignaciones', fn ($q) => $q->where('puesto_trabajo_id', $puestoId)->whereNull('fecha_fin'))
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->with(['tipoEquipo', 'marca', 'modeloEquipo', 'asignacionActual.persona'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Datos del traslado de puesto al que pertenece el evento, o null si fue
     * un traslado de un solo equipo.
     *
     * @return array{grupo: string, puesto_trabajo_id: int, puesto: string, equipos: int}|null
     */
    public static function grupo(Evento $evento): ?array
    {
        $grupo = $evento->valores[self::CLAVE] ?? null;

        return is_array($grupo) && isset($grupo['grupo']) ? $grupo : null;
    }

    /**
     * Todos los eventos del mismo traslado de puesto (incluido el propio), en
     * el orden en que se crearon. Para un traslado normal, solo el evento.
     *
     * @return Collection<int, Evento>
     */
    public static function eventosDelGrupo(Evento $evento): Collection
    {
        $grupo = self::grupo($evento);

        if (! $grupo) {
            return collect([$evento]);
        }

        return Evento::query()
            ->where('tipo', 'traslado_responsable')
            ->where('valores->'.self::CLAVE.'->grupo', $grupo['grupo'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Evento que da el consecutivo y la ruta del juego de formatos del puesto:
     * el primero del grupo.
     */
    public static function principal(Evento $evento): Evento
    {
        return self::grupo($evento) ? self::eventosDelGrupo($evento)->first() : $evento;
    }
}
