<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Documento;
use App\Models\Equipo;
use App\Models\Evento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Documentos firmados de un evento y su estado «Pendiente de firma» (RF-30,
 * RN-05, CU-09).
 *
 * - Traslado: exige el formato de baja (firma quien entrega) y el formato de
 *   entrega (firma quien recibe). Si la Dirección TIC aprueba un documento
 *   único, se acepta ese solo en lugar de los dos (decisión 10).
 * - Baja: exige el formato de baja firmado; al subirlo el equipo pasa a
 *   «Dado de baja» (decisión 9, RF-25).
 * - Diagnóstico con formato y alta con entrega: un solo formato.
 *
 * Mientras falte alguno, el evento sigue «Pendiente de firma»; al completar
 * el último, pasa a «Completo».
 */
class GestorFirmas
{
    public const DOCUMENTO_UNICO = 'documento_unico';

    /** Extensiones aceptadas para documentos firmados y evidencias (RNF-12). */
    public const EXTENSIONES = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * Tamaño máximo por archivo en KB. Configurable con
     * `movimientos.adjuntos_max_kb`; por defecto 10 MB (RNF-12).
     */
    public static function maxKb(): int
    {
        return (int) config('movimientos.adjuntos_max_kb', 10240);
    }

    /**
     * @return array<int, string>
     */
    public static function reglasArchivo(bool $requerido = true): array
    {
        return [
            $requerido ? 'required' : 'nullable',
            'file',
            'mimes:'.implode(',', self::EXTENSIONES),
            'max:'.self::maxKb(),
        ];
    }

    /**
     * Formatos que el evento necesita firmados para quedar completo.
     *
     * @return array<int, string>
     */
    public function requeridos(Evento $evento): array
    {
        return match ($evento->tipo) {
            'traslado_responsable' => ['formato_baja', 'formato_entrega'],
            'baja', 'diagnostico' => ['formato_baja'],
            'alta' => ['formato_entrega'],
            default => [],
        };
    }

    public function admiteDocumentoUnico(Evento $evento): bool
    {
        return $evento->tipo === 'traslado_responsable';
    }

    /**
     * Estado de cada formato requerido: el prellenado generado y el firmado
     * más reciente (si ya se subió).
     *
     * @return array<string, array{generado: ?Documento, firmado: ?Documento}>
     */
    public function estado(Evento $evento): array
    {
        $documentos = $evento->documentos()->orderByDesc('id')->get();

        $estado = [];
        foreach ($this->requeridos($evento) as $tipo) {
            $estado[$tipo] = [
                'generado' => $documentos->first(fn (Documento $d) => $d->tipo === $tipo && ! $d->firmado),
                'firmado' => $documentos->first(fn (Documento $d) => $d->tipo === $tipo && $d->firmado),
            ];
        }

        return $estado;
    }

    public function documentoUnico(Evento $evento): ?Documento
    {
        return $evento->documentos()
            ->where('tipo', 'otro')
            ->where('firmado', true)
            ->where('consecutivo', 'like', 'DU-%')
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, Documento>
     */
    public function evidencias(Evento $evento)
    {
        return $evento->documentos()
            ->where('firmado', false)
            ->whereIn('tipo', ['foto', 'otro'])
            ->orderBy('id')
            ->get();
    }

    public function estaCompleto(Evento $evento): bool
    {
        if ($this->admiteDocumentoUnico($evento) && $this->documentoUnico($evento)) {
            return true;
        }

        $requeridos = $this->requeridos($evento);

        if ($requeridos === []) {
            return false;
        }

        $firmados = $evento->documentos()
            ->where('firmado', true)
            ->whereIn('tipo', $requeridos)
            ->distinct()
            ->pluck('tipo')
            ->all();

        return array_diff($requeridos, $firmados) === [];
    }

    /**
     * Sube un documento firmado al evento. $tipo es uno de los formatos
     * requeridos o self::DOCUMENTO_UNICO.
     */
    public function subirFirmado(Evento $evento, string $tipo, UploadedFile $archivo): Documento
    {
        $evento->refresh();

        if ($evento->estado_firma !== 'pendiente_de_firma') {
            throw ValidationException::withMessages([
                'archivo' => __('Este evento no está pendiente de firma: sus documentos ya están completos.'),
            ]);
        }

        if ($evento->anulaciones()->exists()) {
            throw ValidationException::withMessages([
                'archivo' => __('Este evento fue anulado: ya no admite documentos.'),
            ]);
        }

        $esUnico = $tipo === self::DOCUMENTO_UNICO;

        if ($esUnico && ! $this->admiteDocumentoUnico($evento)) {
            throw ValidationException::withMessages([
                'archivo' => __('El documento único solo aplica a traslados o cambios de responsable.'),
            ]);
        }

        if (! $esUnico && ! in_array($tipo, $this->requeridos($evento), true)) {
            throw ValidationException::withMessages([
                'archivo' => __('Este evento no requiere ese formato.'),
            ]);
        }

        return DB::transaction(function () use ($evento, $tipo, $archivo, $esUnico) {
            $ruta = $archivo->storeAs(
                sprintf('movimientos/eventos/%d/firmados', $evento->id),
                sprintf('%s-%s.%s', $esUnico ? 'documento-unico' : $tipo, now()->format('YmdHis'), strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension())),
                FormatosPdf::DISCO,
            );

            $documento = Documento::create([
                'evento_id' => $evento->id,
                'tipo' => $esUnico ? 'otro' : $tipo,
                'consecutivo' => FormatosPdf::consecutivo($evento, $esUnico ? self::DOCUMENTO_UNICO : $tipo),
                'archivo_path' => $ruta,
                'firmado' => true,
                'fecha' => now()->toDateString(),
            ]);

            $this->completarSiCorresponde($evento);

            return $documento;
        });
    }

    /**
     * Guarda una evidencia (foto o PDF) del diagnóstico o de la baja (RF-24).
     */
    public function adjuntarEvidencia(Evento $evento, UploadedFile $archivo): Documento
    {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());

        $ruta = $archivo->storeAs(
            sprintf('movimientos/eventos/%d/evidencias', $evento->id),
            sprintf('evidencia-%s-%s.%s', now()->format('YmdHis'), substr(md5($archivo->getClientOriginalName().microtime()), 0, 6), $extension),
            FormatosPdf::DISCO,
        );

        return Documento::create([
            'evento_id' => $evento->id,
            'tipo' => in_array($extension, ['jpg', 'jpeg', 'png'], true) ? 'foto' : 'otro',
            'consecutivo' => 'EV-'.str_pad((string) $evento->id, 6, '0', STR_PAD_LEFT),
            'archivo_path' => $ruta,
            'firmado' => false,
            'fecha' => now()->toDateString(),
        ]);
    }

    /**
     * Si ya están todos los firmados, cierra el «Pendiente de firma» del evento
     * y aplica el efecto que dependía de la firma (la baja).
     */
    public function completarSiCorresponde(Evento $evento): bool
    {
        if (! $this->estaCompleto($evento)) {
            return false;
        }

        self::cambiarEstadoFirma($evento, 'completo');

        if ($evento->tipo === 'baja') {
            $equipo = Equipo::query()->findOrFail($evento->equipo_id);
            $equipo->estado_ciclo_vida = 'dado_de_baja';
            $equipo->save();
        }

        return true;
    }

    /**
     * Único cambio que se hace sobre un evento ya creado: su estado de firma.
     *
     * Por qué no viola la inmutabilidad (RN-06): `estado_firma` no es parte del
     * hecho registrado (tipo, fecha, autor, descripción), sino el avance del
     * trámite documental que RF-30 exige mostrar («Pendiente de firma» →
     * «Completo»). Evento bloquea update() a propósito, así que se usa una
     * actualización acotada a esa sola columna y solo desde el estado
     * pendiente, para que nunca pueda reescribir el pasado.
     *
     * TODO(Jhon): mover este método a HistorialService (p. ej.
     * `marcarFirma(Evento, string)`) para que la excepción quede en la pieza
     * compartida y no en el bloque de Movimientos.
     */
    public static function cambiarEstadoFirma(Evento $evento, string $nuevoEstado): void
    {
        if (! in_array($nuevoEstado, ['completo', 'no_aplica'], true)) {
            throw new \InvalidArgumentException("Estado de firma no permitido: {$nuevoEstado}");
        }

        Evento::query()
            ->whereKey($evento->id)
            ->where('estado_firma', 'pendiente_de_firma')
            ->update(['estado_firma' => $nuevoEstado, 'updated_at' => now()]);

        $evento->setRawAttributes(array_merge($evento->getAttributes(), ['estado_firma' => $nuevoEstado]), true);
    }

    public function descargar(Documento $documento, ?string $nombre = null)
    {
        abort_unless(Storage::disk(FormatosPdf::DISCO)->exists($documento->archivo_path), 404);

        return Storage::disk(FormatosPdf::DISCO)->download($documento->archivo_path, $nombre ?? basename($documento->archivo_path));
    }
}
