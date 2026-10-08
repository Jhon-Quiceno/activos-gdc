<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Equipo;
use App\Models\Evento;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio que comparten todos los movimientos de un equipo.
 *
 * RN-10: un equipo «Dado de baja» conserva su hoja de vida pero no admite
 * nuevos eventos, salvo una anulación justificada o una aclaración.
 *
 * Además, mientras una baja está en trámite (registrada pero sin el formato
 * firmado) no se permite registrar otro movimiento que cambie al responsable
 * o la configuración: el formato de baja que se va a firmar quedaría
 * describiendo una situación que ya no es la real.
 */
class ReglasMovimiento
{
    /**
     * Lanza un error de validación si el equipo no admite eventos nuevos.
     *
     * Se consulta el estado directo en la base de datos (no el atributo en
     * memoria) porque el componente Livewire pudo haberse montado antes de que
     * otro usuario diera de baja el equipo.
     */
    /**
     * Llamar siempre dentro de una DB::transaction() ya abierta por el caller:
     * lockForUpdate() solo bloquea la fila mientras dure esa transacción, así
     * que si esto corre antes de abrirla (sin conexión en modo transacción) no
     * protege nada contra dos requests concurrentes sobre el mismo equipo.
     */
    public static function asegurarQueAdmiteEventos(Equipo $equipo, string $campo = 'equipo', bool $permitirConBajaEnTramite = false): void
    {
        $estado = Equipo::query()->whereKey($equipo->id)->lockForUpdate()->value('estado_ciclo_vida');

        if ($estado === 'dado_de_baja') {
            throw ValidationException::withMessages([
                $campo => __('El equipo está dado de baja: no admite nuevos eventos. Si la baja fue un error, anúlala primero con un evento justificado.'),
            ]);
        }

        if (! $permitirConBajaEnTramite && self::bajaEnTramite($equipo)) {
            throw ValidationException::withMessages([
                $campo => __('El equipo tiene una baja en trámite (pendiente de firma). Sube el formato de baja firmado o anula la baja antes de registrar otro movimiento.'),
            ]);
        }
    }

    public static function estaDadoDeBaja(Equipo $equipo): bool
    {
        return Equipo::query()->whereKey($equipo->id)->value('estado_ciclo_vida') === 'dado_de_baja';
    }

    /**
     * Baja registrada que todavía espera su formato firmado y no fue anulada.
     *
     * También cuenta un diagnóstico con «Generar formato» (DiagnosticoForm)
     * que dejó un formato_baja sin firmar: aunque el evento quede con
     * tipo=diagnostico, ese documento describe una baja en trámite igual que
     * una de tipo=baja, y sin esto una segunda baja independiente podía
     * registrarse sobre el mismo equipo sin que ninguna de las dos lo viera.
     */
    public static function bajaEnTramite(Equipo $equipo): ?Evento
    {
        return Evento::query()
            ->where('equipo_id', $equipo->id)
            ->whereIn('tipo', ['baja', 'diagnostico'])
            ->where('estado_firma', 'pendiente_de_firma')
            ->whereHas('documentos', fn ($query) => $query->where('tipo', 'formato_baja')->where('firmado', false))
            ->whereDoesntHave('anulaciones')
            ->latest('id')
            ->first();
    }

    /**
     * Última baja vigente (no anulada) del equipo, esté completa o en trámite.
     */
    public static function bajaVigente(Equipo $equipo): ?Evento
    {
        return Evento::query()
            ->where('equipo_id', $equipo->id)
            ->where('tipo', 'baja')
            ->whereDoesntHave('anulaciones')
            ->latest('id')
            ->first();
    }
}
