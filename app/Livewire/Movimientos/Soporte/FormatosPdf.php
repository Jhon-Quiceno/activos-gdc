<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Documento;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Persona;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Los dos formatos oficiales de la Dirección TIC (RF-28, RF-29, sección 11.1):
 *
 * - Formato de entrega: lo firma quien RECIBE el equipo (y el área de sistemas).
 * - Formato de baja: lo firma quien ENTREGA el equipo (y el ingeniero de soporte).
 *   Sirve para el lado «entrega» de un traslado, para el retiro del responsable
 *   y para la baja definitiva.
 *
 * RNF-13: los PDF salen en tamaño carta, en blanco y negro (bordes negros,
 * fondos grises claros) y con espacios de firma amplios.
 *
 * Las mismas plantillas se usan para el PDF (dompdf) y para la vista previa
 * imprimible de las rutas movimientos.formato-entrega / formato-baja, así que
 * lo que se ve en pantalla es lo mismo que se imprime.
 */
class FormatosPdf
{
    public const DISCO = 'local';

    public function __construct(private GestorAsignaciones $asignaciones) {}

    /**
     * Datos que necesita la plantilla del formato de un evento.
     *
     * @return array<string, mixed>
     */
    public function datos(Evento $evento, string $tipoFormato): array
    {
        if (! in_array($tipoFormato, ['formato_entrega', 'formato_baja'], true)) {
            throw new InvalidArgumentException("Tipo de formato no soportado: {$tipoFormato}");
        }

        $evento->loadMissing([
            'usuario',
            'diagnostico.motivoBaja',
            'equipo.tipoEquipo',
            'equipo.marca',
            'equipo.componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id'),
        ]);

        // Quien entrega firma el formato de baja; quien recibe, el de entrega.
        $asignacion = $tipoFormato === 'formato_baja'
            ? $this->asignaciones->vigenteAntesDe($evento)
            : $this->asignaciones->vigenteDespuesDe($evento);

        return [
            'tipoFormato' => $tipoFormato,
            'titulo' => $tipoFormato === 'formato_baja'
                ? __('Formato de hoja de vida – baja')
                : __('Formato de entrega de equipo'),
            'consecutivo' => self::consecutivo($evento, $tipoFormato),
            'fecha' => $evento->fecha,
            'evento' => $evento,
            'motivo' => $this->motivo($evento),
            'equipos' => collect([$evento->equipo]),
            'persona' => $asignacion?->persona,
            'asignacion' => $asignacion,
            'diagnostico' => $evento->diagnostico,
            'observaciones' => $evento->descripcion,
            'tecnico' => $evento->usuario,
        ];
    }

    /**
     * Datos del formato de entrega consolidado de un funcionario (RF-22): todos
     * los equipos que tiene a cargo hoy, en un solo documento para firmar.
     *
     * @return array<string, mixed>
     */
    public function datosConsolidado(Persona $persona): array
    {
        $persona->loadMissing('dependencia');
        $equipos = $this->asignaciones->equiposACargo($persona);

        return [
            'tipoFormato' => 'formato_entrega',
            'titulo' => __('Formato de entrega consolidado'),
            'consecutivo' => 'FC-'.str_pad((string) $persona->id, 5, '0', STR_PAD_LEFT).'-'.now()->format('Ymd'),
            'fecha' => now(),
            'evento' => null,
            'motivo' => __('Equipos a cargo del funcionario'),
            'equipos' => $equipos,
            'persona' => $persona,
            'asignacion' => $equipos->first()?->asignacionActual,
            'diagnostico' => null,
            'observaciones' => trans_choice(':count equipo a cargo.|:count equipos a cargo.', $equipos->count(), ['count' => $equipos->count()]),
            'tecnico' => auth()->user(),
        ];
    }

    public function pdf(Evento $evento, string $tipoFormato): DocumentoPdf
    {
        return $this->render($this->datos($evento, $tipoFormato));
    }

    public function pdfConsolidado(Persona $persona): DocumentoPdf
    {
        return $this->render($this->datosConsolidado($persona));
    }

    /**
     * Genera los formatos prellenados del evento y los guarda como Documento
     * sin firmar, para que quede constancia de qué se imprimió (CU-05 paso 5).
     *
     * @param  array<int, string>  $tipos  formato_entrega y/o formato_baja
     * @return Collection<int, Documento>
     */
    public function generarYGuardar(Evento $evento, array $tipos): Collection
    {
        return collect($tipos)->map(function (string $tipo) use ($evento) {
            $ruta = sprintf('movimientos/eventos/%d/%s-prellenado.pdf', $evento->id, $tipo);

            Storage::disk(self::DISCO)->put($ruta, $this->pdf($evento, $tipo)->output());

            return Documento::create([
                'evento_id' => $evento->id,
                'tipo' => $tipo,
                'consecutivo' => self::consecutivo($evento, $tipo),
                'archivo_path' => $ruta,
                'firmado' => false,
                'fecha' => now()->toDateString(),
            ]);
        });
    }

    public static function consecutivo(Evento $evento, string $tipoFormato): string
    {
        $prefijo = match ($tipoFormato) {
            'formato_entrega' => 'FE',
            'formato_baja' => 'FB',
            default => 'DU',
        };

        return $prefijo.'-'.str_pad((string) $evento->id, 6, '0', STR_PAD_LEFT);
    }

    public static function nombreArchivo(Equipo $equipo, string $tipoFormato, ?string $consecutivo = null): string
    {
        $base = $tipoFormato === 'formato_baja' ? 'formato-baja' : 'formato-entrega';

        return $base.'-'.($consecutivo ? $consecutivo.'-' : '').preg_replace('/[^A-Za-z0-9\-]/', '', $equipo->serial).'.pdf';
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function render(array $datos): DocumentoPdf
    {
        $vista = $datos['tipoFormato'] === 'formato_baja'
            ? 'livewire.movimientos.pdf.formato-baja'
            : 'livewire.movimientos.pdf.formato-entrega';

        return Pdf::loadView($vista, $datos + ['modoPdf' => true])
            ->setPaper('letter', 'portrait')
            ->setOption(['defaultFont' => 'DejaVu Sans', 'isFontSubsettingEnabled' => true]);
    }

    private function motivo(Evento $evento): string
    {
        return match ($evento->tipo) {
            'baja' => $evento->diagnostico?->motivoBaja?->nombre ?? __('Baja definitiva'),
            'traslado_responsable' => __('Traslado o cambio de responsable'),
            'diagnostico' => __('Diagnóstico técnico'),
            'alta' => __('Alta del equipo'),
            default => ucfirst(str_replace('_', ' ', $evento->tipo)),
        };
    }

    /**
     * Fecha en formato colombiano DD/MM/AAAA (RNF-14).
     */
    public static function fecha(?Carbon $fecha): string
    {
        return $fecha ? $fecha->format('d/m/Y') : '';
    }
}
