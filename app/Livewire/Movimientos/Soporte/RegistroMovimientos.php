<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Diagnostico;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lógica de negocio de los movimientos de un equipo: traslado o cambio de
 * responsable, diagnóstico, baja y anulación de la baja.
 *
 * Los componentes Livewire solo validan el formulario y llaman a estos
 * métodos; así la regla queda en un solo lugar y se puede probar sin pantalla.
 * Todo evento se crea con HistorialService::registrar() (RN-06, RF-11): el
 * autor siempre es el usuario que se recibe aquí, que es el de la sesión.
 */
class RegistroMovimientos
{
    public function __construct(
        private HistorialService $historial,
        private GestorAsignaciones $asignaciones,
        private FormatosPdf $formatos,
        private GestorFirmas $firmas,
    ) {}

    /**
     * CU-05 · Traslado o cambio de responsable (RF-20).
     *
     * Cierra la asignación anterior, abre la nueva, crea el evento «Pendiente
     * de firma» y genera los dos formatos prellenados: el de baja (quien
     * entrega) y el de entrega (quien recibe).
     *
     * @param  array{persona: ?Persona, sede_id: int, piso_id: int, dependencia_id: ?int, fecha: string, motivo: string}  $datos
     */
    public function trasladar(Equipo $equipo, User $usuario, array $datos): Evento
    {
        return DB::transaction(function () use ($equipo, $usuario, $datos) {
            // Dentro de la transacción (no antes): lockForUpdate() solo
            // protege contra dos movimientos concurrentes sobre el mismo
            // equipo si la fila ya está bloqueada cuando se re-chequea.
            ReglasMovimiento::asegurarQueAdmiteEventos($equipo);

            $equipo->loadMissing('asignacionActual.persona');
            $origen = $equipo->asignacionActual?->persona?->nombre ?? __('Sin asignar (bodega)');
            $persona = $datos['persona'];
            $destino = $persona?->nombre ?? __('Sin asignar (bodega)');

            $evento = $this->historial->registrar(
                equipo: $equipo,
                tipo: 'traslado_responsable',
                usuario: $usuario,
                datos: ['fecha' => $datos['fecha'], 'estado_firma' => 'pendiente_de_firma'],
                descripcion: __('Traslado de :origen a :destino. Motivo: :motivo', [
                    'origen' => $origen,
                    'destino' => $destino,
                    'motivo' => trim($datos['motivo']),
                ]),
            );

            $this->asignaciones->asignar(
                equipo: $equipo,
                persona: $persona,
                sedeId: $datos['sede_id'],
                pisoId: $datos['piso_id'],
                dependenciaId: $datos['dependencia_id'] ?? null,
                fecha: $datos['fecha'],
                eventoOrigen: $evento,
            );

            $this->formatos->generarYGuardar($evento, ['formato_baja', 'formato_entrega']);

            return $evento;
        });
    }

    /**
     * CU-07 · Diagnóstico técnico (RF-24). Si se pide formato, el evento queda
     * «Pendiente de firma» hasta subir el formato firmado por el responsable.
     *
     * @param  array{fecha: string, estado_encontrado: string, causa: string, recomendaciones: ?string, generar_formato: bool}  $datos
     * @param  array<int, UploadedFile>  $evidencias
     */
    public function diagnosticar(Equipo $equipo, User $usuario, array $datos, array $evidencias = []): Evento
    {
        return DB::transaction(function () use ($equipo, $usuario, $datos, $evidencias) {
            // Un diagnóstico se puede registrar aunque haya una baja en
            // trámite: no cambia responsable ni configuración.
            ReglasMovimiento::asegurarQueAdmiteEventos($equipo, permitirConBajaEnTramite: true);

            $conFormato = (bool) ($datos['generar_formato'] ?? false);

            $evento = $this->historial->registrar(
                equipo: $equipo,
                tipo: 'diagnostico',
                usuario: $usuario,
                datos: [
                    'fecha' => $datos['fecha'],
                    'estado_firma' => $conFormato ? 'pendiente_de_firma' : 'no_aplica',
                ],
                descripcion: __('Diagnóstico técnico: :causa', ['causa' => str($datos['causa'])->limit(180)]),
            );

            Diagnostico::create([
                'evento_id' => $evento->id,
                'estado_encontrado' => $datos['estado_encontrado'],
                'causa' => $datos['causa'],
                'recomendaciones' => filled($datos['recomendaciones'] ?? null) ? $datos['recomendaciones'] : null,
                'es_baja' => false,
            ]);

            foreach ($evidencias as $archivo) {
                $this->firmas->adjuntarEvidencia($evento, $archivo);
            }

            if ($conFormato) {
                $this->formatos->generarYGuardar($evento, ['formato_baja']);
            }

            return $evento;
        });
    }

    /**
     * CU-08 · Baja del equipo (RF-25, RN-09).
     *
     * Registra la baja con su diagnóstico y genera el formato de baja. El
     * equipo pasa a «Dado de baja» cuando se sube ese formato firmado (ver
     * GestorFirmas::completarSiCorresponde); si el formato firmado se entrega
     * de una vez, la baja queda completa en esta misma operación.
     *
     * @param  array{motivo_baja_id: int, fecha: string, estado_encontrado: string, diagnostico: string, recomendaciones: ?string}  $datos
     * @param  array<int, UploadedFile>  $evidencias
     */
    public function darDeBaja(Equipo $equipo, User $usuario, array $datos, array $evidencias = [], ?UploadedFile $formatoFirmado = null): Evento
    {
        if ($equipo->propiedad === 'tercero') {
            // CU-08: la precondición es que el equipo sea de la Gobernación; un
            // equipo de tercero se devuelve a su dueño, no se da de baja.
            throw ValidationException::withMessages([
                'equipo' => __('Solo se pueden dar de baja equipos de la Gobernación. Un equipo de tercero se devuelve a su propietario con un traslado.'),
            ]);
        }

        $evento = DB::transaction(function () use ($equipo, $usuario, $datos, $evidencias) {
            ReglasMovimiento::asegurarQueAdmiteEventos($equipo);

            $evento = $this->historial->registrar(
                equipo: $equipo,
                tipo: 'baja',
                usuario: $usuario,
                datos: ['fecha' => $datos['fecha'], 'estado_firma' => 'pendiente_de_firma'],
                descripcion: __('Baja del equipo. Diagnóstico: :diagnostico', ['diagnostico' => str($datos['diagnostico'])->limit(180)]),
            );

            Diagnostico::create([
                'evento_id' => $evento->id,
                'estado_encontrado' => $datos['estado_encontrado'],
                'causa' => $datos['diagnostico'],
                'recomendaciones' => filled($datos['recomendaciones'] ?? null) ? $datos['recomendaciones'] : null,
                'es_baja' => true,
                'motivo_baja_id' => $datos['motivo_baja_id'],
            ]);

            foreach ($evidencias as $archivo) {
                $this->firmas->adjuntarEvidencia($evento, $archivo);
            }

            $this->formatos->generarYGuardar($evento, ['formato_baja']);

            return $evento;
        });

        if ($formatoFirmado) {
            $this->firmas->subirFirmado($evento, 'formato_baja', $formatoFirmado);
        }

        return $evento->refresh();
    }

    /**
     * RF-26 · Anula una baja registrada por error con un evento justificado y
     * devuelve el equipo a su estado anterior. La baja original no se toca
     * (RN-06): queda en la hoja de vida, referenciada por la anulación.
     */
    public function anularBaja(Equipo $equipo, User $usuario, string $justificacion): Evento
    {
        $baja = ReglasMovimiento::bajaVigente($equipo);

        if (! $baja) {
            throw ValidationException::withMessages([
                'justificacion' => __('El equipo no tiene una baja vigente para anular.'),
            ]);
        }

        return DB::transaction(function () use ($equipo, $usuario, $justificacion, $baja) {
            $anulacion = $this->historial->registrar(
                equipo: $equipo,
                tipo: 'anulacion_aclaracion',
                usuario: $usuario,
                datos: ['estado_firma' => 'no_aplica'],
                descripcion: __('Anulación de la baja #:id. Justificación: :justificacion', [
                    'id' => $baja->id,
                    'justificacion' => trim($justificacion),
                ]),
                eventoAnulado: $baja,
            );

            // Si la baja estaba esperando firma, deja de estar pendiente: ya no
            // hay nada que firmar.
            if ($baja->estado_firma === 'pendiente_de_firma') {
                GestorFirmas::cambiarEstadoFirma($baja, 'no_aplica');
            }

            $equipo->loadMissing('asignacionActual');
            $equipo->estado_ciclo_vida = $equipo->asignacionActual?->persona_id ? 'en_servicio' : 'sin_asignar';
            $equipo->save();

            return $anulacion;
        });
    }
}
