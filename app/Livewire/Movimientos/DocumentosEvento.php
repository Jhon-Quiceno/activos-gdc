<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Livewire\Movimientos\Soporte\GestorAsignaciones;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Models\Documento;
use App\Models\Evento;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Panel reutilizable de documentos de un evento (CU-09, RF-28 a RF-30):
 * descargar los formatos prellenados, subir los firmados (PDF, JPG o PNG, máx.
 * 10 MB) y ver si el evento sigue «Pendiente de firma».
 *
 * Se usa dentro de Traslado, Baja, Diagnóstico y Pendientes de firma. Juan
 * José puede incrustarlo también en la hoja de vida:
 * <livewire:movimientos.documentos-evento :evento-id="$evento->id" />
 */
class DocumentosEvento extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $eventoId;

    /** @var array<string, mixed> Archivo por tipo de formato. */
    public array $archivos = [];

    public $archivoUnico = null;

    public bool $usarDocumentoUnico = false;

    public ?string $mensaje = null;

    public function mount(int $eventoId): void
    {
        $this->eventoId = $eventoId;
    }

    protected function evento(): Evento
    {
        return Evento::with(['equipo', 'anulaciones'])->findOrFail($this->eventoId);
    }

    public function subir(string $tipo, GestorFirmas $firmas): void
    {
        $evento = $this->evento();
        abort_unless(in_array($tipo, $firmas->requeridos($evento), true), 422);

        $this->validate(
            ["archivos.$tipo" => GestorFirmas::reglasArchivo()],
            [],
            ["archivos.$tipo" => __('documento firmado')],
        );

        $firmas->subirFirmado($evento, $tipo, $this->archivos[$tipo]);

        unset($this->archivos[$tipo]);
        $this->mensaje = $this->mensajeTrasSubir();
        $this->dispatch('documentos-actualizados', eventoId: $this->eventoId);
    }

    public function subirUnico(GestorFirmas $firmas): void
    {
        $this->validate(
            ['archivoUnico' => GestorFirmas::reglasArchivo()],
            [],
            ['archivoUnico' => __('documento único firmado')],
        );

        $firmas->subirFirmado($this->evento(), GestorFirmas::DOCUMENTO_UNICO, $this->archivoUnico);

        $this->archivoUnico = null;
        $this->mensaje = $this->mensajeTrasSubir();
        $this->dispatch('documentos-actualizados', eventoId: $this->eventoId);
    }

    /**
     * Descarga el formato prellenado. Si por algún motivo no quedó guardado
     * (eventos anteriores a este bloque), se genera en el momento.
     */
    public function descargarPrellenado(string $tipo, GestorFirmas $firmas, FormatosPdf $formatos)
    {
        $evento = $this->evento();
        abort_unless(in_array($tipo, ['formato_entrega', 'formato_baja'], true), 404);

        $generado = $firmas->estado($evento)[$tipo]['generado'] ?? null;
        $nombre = FormatosPdf::nombreArchivo($evento->equipo, $tipo, FormatosPdf::consecutivo($evento, $tipo));

        if ($generado) {
            return $firmas->descargar($generado, $nombre);
        }

        $pdf = $formatos->pdf($evento, $tipo);

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre, ['Content-Type' => 'application/pdf']);
    }

    public function descargarDocumento(int $documentoId, GestorFirmas $firmas)
    {
        $documento = Documento::query()->where('evento_id', $this->eventoId)->findOrFail($documentoId);

        return $firmas->descargar($documento);
    }

    private function mensajeTrasSubir(): string
    {
        $estado = Evento::query()->whereKey($this->eventoId)->value('estado_firma');

        return $estado === 'completo'
            ? __('Documentos completos. El evento ya no está pendiente de firma.')
            : __('Documento subido. Falta el otro formato firmado para completar el evento.');
    }

    public function render(GestorFirmas $firmas, GestorAsignaciones $asignaciones)
    {
        $evento = $this->evento();

        $firmantes = [
            'formato_baja' => $asignaciones->vigenteAntesDe($evento)?->persona?->nombre ?? __('Sin responsable'),
            'formato_entrega' => $asignaciones->vigenteDespuesDe($evento)?->persona?->nombre ?? __('Sin responsable (bodega)'),
        ];

        return view('livewire.movimientos.documentos-evento', [
            'evento' => $evento,
            'firmantes' => $firmantes,
            'estado' => $firmas->estado($evento),
            'documentoUnico' => $firmas->documentoUnico($evento),
            'admiteUnico' => $firmas->admiteDocumentoUnico($evento),
            'evidencias' => $firmas->evidencias($evento),
            'pendiente' => $evento->estado_firma === 'pendiente_de_firma' && $evento->anulaciones->isEmpty(),
            'maxMb' => (int) round(GestorFirmas::maxKb() / 1024),
        ]);
    }
}
