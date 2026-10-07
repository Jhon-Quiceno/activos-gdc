<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Asignacion;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Persona;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Responsables y ubicación de un equipo (RF-18, RF-19, RF-21).
 *
 * RN-04: un equipo tiene como máximo un responsable a la vez. Por eso toda
 * asignación nueva cierra antes cualquier asignación abierta del equipo, aunque
 * por un error de datos hubiera quedado más de una abierta.
 */
class GestorAsignaciones
{
    /**
     * Cierra la asignación abierta y abre la nueva. Si $persona es null el
     * equipo queda «Sin asignar» (bodega), que es un estado válido (RF-21).
     */
    public function asignar(
        Equipo $equipo,
        ?Persona $persona,
        int $sedeId,
        int $pisoId,
        ?int $dependenciaId,
        CarbonInterface|string $fecha,
        ?Evento $eventoOrigen = null,
    ): Asignacion {
        $fecha = $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);

        Asignacion::query()
            ->where('equipo_id', $equipo->id)
            ->whereNull('fecha_fin')
            ->get()
            ->each(fn (Asignacion $abierta) => $abierta->update(['fecha_fin' => $fecha->toDateString()]));

        $asignacion = Asignacion::create([
            'equipo_id' => $equipo->id,
            'persona_id' => $persona?->id,
            'sede_id' => $sedeId,
            'piso_id' => $pisoId,
            'dependencia_id' => $persona ? ($dependenciaId ?? $persona->dependencia_id) : $dependenciaId,
            'fecha_inicio' => $fecha->toDateString(),
            'fecha_fin' => null,
            'evento_origen_id' => $eventoOrigen?->id,
        ]);

        $equipo->estado_ciclo_vida = $persona ? 'en_servicio' : 'sin_asignar';
        $equipo->save();

        $equipo->unsetRelation('asignacionActual');

        return $asignacion;
    }

    /**
     * Asignación que estaba vigente cuando se registró el evento (antes de que
     * el propio evento la cambiara). Sirve para saber quién entrega el equipo.
     */
    public function vigenteAntesDe(Evento $evento): ?Asignacion
    {
        $creadaPorElEvento = Asignacion::query()
            ->where('evento_origen_id', $evento->id)
            ->value('id');

        return Asignacion::query()
            ->where('equipo_id', $evento->equipo_id)
            ->when($creadaPorElEvento, fn ($q) => $q->where('id', '<', $creadaPorElEvento))
            ->where(fn ($q) => $q->whereNull('evento_origen_id')->orWhere('evento_origen_id', '<', $evento->id))
            ->with(['persona.dependencia', 'sede', 'piso', 'dependencia'])
            ->latest('id')
            ->first();
    }

    /**
     * Asignación que quedó vigente después del evento (quién recibe). Para un
     * traslado es la asignación que el propio evento abrió; para los demás
     * tipos de evento, la que estaba vigente en ese momento.
     */
    public function vigenteDespuesDe(Evento $evento): ?Asignacion
    {
        return Asignacion::query()
            ->where('evento_origen_id', $evento->id)
            ->with(['persona.dependencia', 'sede', 'piso', 'dependencia'])
            ->first()
            ?? $this->vigenteAntesDe($evento);
    }

    /**
     * Equipos que una persona tiene a cargo hoy (RF-22): asignación abierta y
     * equipo que no está dado de baja.
     */
    public function equiposACargo(Persona $persona)
    {
        return Equipo::query()
            ->whereHas('asignaciones', fn ($q) => $q->where('persona_id', $persona->id)->whereNull('fecha_fin'))
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->with([
                'tipoEquipo',
                'marca',
                'asignacionActual.sede',
                'asignacionActual.piso',
                'asignacionActual.dependencia',
                'componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id'),
            ])
            ->orderBy('tipo_equipo_id')
            ->orderBy('serial')
            ->get();
    }
}
