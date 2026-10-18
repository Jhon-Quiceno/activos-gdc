<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Asignacion;
use App\Models\Diagnostico;
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
 * Formatos para firma de la Dirección TIC (RF-28, RF-29, sección 11.1).
 *
 * En la práctica la Dirección TIC usa UN SOLO formato oficial, «FORMATO DE HOJA
 * DE VIDA» v1.0 (archivo «HOJA DE VIDA BAJA NUEVO 20-12-2024.docx»), con
 * distinto contenido según el contexto (ver partials/oficial.blade.php):
 *
 * - formato_baja de un traslado → contexto «retiro»: lo firma quien ENTREGA.
 * - formato_entrega             → contexto «entrega»: lo firma quien RECIBE.
 * - formato_baja de una baja    → contexto «baja».
 * - formato_baja de un diagnóstico → contexto «diagnostico».
 *
 * Los nombres formato_entrega / formato_baja se conservan porque son los tipos
 * de Documento que ya se guardan y se suben firmados (RF-30).
 *
 * RNF-13: blanco y negro, espacios de firma amplios. Tamaño oficio, como el
 * formato oficial en Word.
 *
 * La misma plantilla se usa para el PDF (dompdf) y para la vista previa
 * imprimible de las rutas movimientos.formato-entrega / formato-baja.
 */
class FormatosPdf
{
    public const DISCO = 'local';

    /** Oficio, 8,5 × 13 pulgadas, en puntos (como el formato oficial en Word). */
    public const PAPEL_OFICIO = [0, 0, 612, 936];

    public const TITULOS = [
        'retiro' => 'Formato de hoja de vida – retiro del equipo',
        'entrega' => 'Formato de hoja de vida – entrega del equipo',
        'baja' => 'Formato de hoja de vida – baja del equipo',
        'diagnostico' => 'Formato de hoja de vida – diagnóstico',
    ];

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

        $contexto = match (true) {
            $tipoFormato === 'formato_entrega' => 'entrega',
            $evento->tipo === 'baja' => 'baja',
            $evento->tipo === 'diagnostico' => 'diagnostico',
            default => 'retiro',
        };

        return [
            'tipoFormato' => $tipoFormato,
            'contexto' => $contexto,
            'titulo' => self::TITULOS[$contexto],
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
            'contexto' => 'entrega',
            'titulo' => __('Formatos de entrega de los equipos a cargo'),
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

    /**
     * «formato-retiro-FB-000012-SN123.pdf». Con $contexto (retiro, entrega, baja,
     * diagnostico) el nombre dice qué es; sin él se usa el tipo de documento.
     */
    public static function nombreArchivo(Equipo $equipo, string $tipoFormato, ?string $consecutivo = null, ?string $contexto = null): string
    {
        $base = $contexto
            ? 'formato-'.$contexto
            : ($tipoFormato === 'formato_baja' ? 'formato-baja' : 'formato-entrega');

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
            ->setPaper(self::PAPEL_OFICIO, 'portrait')
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
     * Texto de las cajas «Diagnóstico» y «Recomendaciones / Sugerencias del área
     * de sistemas» del formato oficial, según el contexto. Se genera con los
     * datos registrados para que el formato salga listo para firmar.
     *
     * @return array{diagnostico: string, recomendaciones: string}
     */
    public static function contenido(
        string $contexto,
        Equipo $equipo,
        ?Evento $evento,
        ?Persona $persona,
        ?Asignacion $asignacion,
        ?Diagnostico $diagnostico,
    ): array {
        $equipo->loadMissing(['componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id')]);
        $asignacion?->loadMissing(['sede', 'piso', 'dependencia']);

        $fecha = self::fecha($evento?->fecha ?? now());
        $estado = $equipo->estado_funcionamiento
            ? __('Estado de funcionamiento: :estado.', ['estado' => $equipo->estado_funcionamiento])
            : null;
        $componentes = self::textoComponentes($equipo);
        $motivo = self::motivoDelEvento($evento);

        [$diagnosticoTexto, $recomendaciones] = match ($contexto) {
            'retiro' => [
                [
                    __('Retiro del equipo por traslado o cambio de responsable. :quien entrega el equipo al área de sistemas el :fecha.', [
                        'quien' => $persona ? __('El funcionario :nombre', ['nombre' => $persona->nombre]) : __('El responsable'),
                        'fecha' => $fecha,
                    ]),
                    $motivo ? __('Motivo: :motivo.', ['motivo' => rtrim($motivo, '.')]) : null,
                    $estado,
                    $componentes,
                ],
                $diagnostico?->recomendaciones
                    ?: __('El equipo queda bajo custodia del área de sistemas para su revisión y posterior reasignación.'),
            ],
            'entrega' => [
                [
                    __('Entrega del equipo :a para su uso y custodia:ubicacion, el :fecha.', [
                        'a' => $persona
                            ? __('al funcionario :nombre:cargo', ['nombre' => $persona->nombre, 'cargo' => $persona->cargo ? ' ('.$persona->cargo.')' : ''])
                            : __('al área de sistemas (bodega)'),
                        'ubicacion' => self::textoUbicacion($asignacion),
                        'fecha' => $fecha,
                    ]),
                    $motivo ? __('Motivo: :motivo.', ['motivo' => rtrim($motivo, '.')]) : null,
                    $estado,
                    $componentes,
                ],
                __('Hacer buen uso del equipo y reportar a la Dirección TIC cualquier daño, pérdida o traslado.'),
            ],
            'baja' => [
                [
                    __('Baja definitiva del equipo.'),
                    $diagnostico?->motivoBaja ? __('Motivo de la baja: :motivo.', ['motivo' => $diagnostico->motivoBaja->nombre]) : null,
                    $diagnostico?->estado_encontrado ? __('Estado encontrado: :estado.', ['estado' => rtrim($diagnostico->estado_encontrado, '.')]) : null,
                    $diagnostico?->causa ? __('Diagnóstico: :causa', ['causa' => $diagnostico->causa]) : null,
                ],
                $diagnostico?->recomendaciones ?: __('Retirar el equipo de servicio de forma definitiva.'),
            ],
            default => [
                [
                    $diagnostico?->estado_encontrado ? __('Estado encontrado: :estado.', ['estado' => rtrim($diagnostico->estado_encontrado, '.')]) : null,
                    $diagnostico?->causa ? __('Causa: :causa', ['causa' => $diagnostico->causa]) : null,
                    $componentes,
                ],
                (string) $diagnostico?->recomendaciones,
            ],
        };

        return [
            'diagnostico' => implode("\n", array_filter($diagnosticoTexto)),
            'recomendaciones' => $recomendaciones,
        ];
    }

    /**
     * «Componentes: Procesador Intel i5; RAM 8GB; Teclado (serial KB-1).»
     */
    private static function textoComponentes(Equipo $equipo): ?string
    {
        if ($equipo->componentes->isEmpty()) {
            return null;
        }

        $lista = $equipo->componentes->map(function ($componente) {
            $texto = trim(($componente->tipoComponente?->nombre ?? __('Componente')).' '.($componente->capacidad_caracteristica ?? ''));

            if ($componente->marca) {
                $texto .= ' '.$componente->marca;
            }

            return $componente->serial ? $texto.' ('.__('serial').' '.$componente->serial.')' : $texto;
        });

        return __('Componentes: :lista.', ['lista' => $lista->implode('; ')]);
    }

    private static function textoUbicacion(?Asignacion $asignacion): string
    {
        if (! $asignacion) {
            return '';
        }

        $partes = array_filter([
            $asignacion->dependencia?->nombre,
            $asignacion->sede?->nombre,
            $asignacion->piso ? __('piso :numero', ['numero' => $asignacion->piso->numero]) : null,
        ]);

        return $partes ? ', '.__('en :ubicacion', ['ubicacion' => implode(', ', $partes)]) : '';
    }

    /**
     * El motivo de un traslado va en la descripción del evento como
     * «Traslado de A a B. Motivo: …» (RegistroMovimientos::trasladar()).
     */
    private static function motivoDelEvento(?Evento $evento): ?string
    {
        if (! $evento || $evento->tipo !== 'traslado_responsable' || ! $evento->descripcion) {
            return null;
        }

        $partes = explode('Motivo:', $evento->descripcion, 2);

        return isset($partes[1]) ? trim($partes[1]) : null;
    }

    /**
     * Fecha en formato colombiano DD/MM/AAAA (RNF-14).
     */
    public static function fecha(?Carbon $fecha): string
    {
        return $fecha ? $fecha->format('d/m/Y') : '';
    }
}
