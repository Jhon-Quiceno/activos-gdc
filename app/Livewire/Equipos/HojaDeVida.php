<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use App\Models\Evento;
use App\Services\HistorialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hoja de vida de un equipo (RF-09): ficha, responsable y ubicación, software,
 * componentes actuales e historial cronológico con el detalle de cada evento.
 *
 * Desde aquí también se corrige el historial (RF-12, RN-06): los eventos nunca
 * se editan ni se borran; se registra un evento nuevo de tipo
 * «anulacion_aclaracion» con su justificación.
 *
 * - Aclarar: se puede sobre cualquier evento. Deja una nota de corrección y no
 *   cambia nada del equipo.
 * - Anular: solo sobre eventos que no cambian el estado, el responsable ni la
 *   configuración del equipo (hoy, el diagnóstico). Deshacer un traslado o un
 *   cambio de componente es lógica de esos flujos; la baja se anula desde
 *   Movimientos, que además devuelve el equipo a su estado anterior (RF-26).
 *
 * Y se exporta a PDF (RF-33): adelantado de la Fase 2 con autorización de Jhon
 * (líder) el 7 de octubre de 2026. Ver exportarPdf().
 */
class HojaDeVida extends Component
{
    #[Locked]
    public Equipo $equipo;

    /** Evento sobre el que se abrió el modal de anular/aclarar. */
    #[Locked]
    public ?int $eventoSeleccionadoId = null;

    /** 'anulacion' o 'aclaracion'. */
    public string $modo = 'aclaracion';

    public string $justificacion = '';

    /**
     * Tipos de evento que se pueden anular desde la hoja de vida porque no
     * tienen efectos que revertir.
     */
    private const TIPOS_ANULABLES = ['diagnostico'];

    public const TIPO_LABELS = [
        'alta' => 'Alta',
        'traslado_responsable' => 'Traslado de responsable',
        'cambio_componente' => 'Cambio de componente',
        'diagnostico' => 'Diagnóstico',
        'baja' => 'Baja',
        'actualizacion_datos' => 'Actualización de datos',
        'anulacion_aclaracion' => 'Anulación / aclaración',
    ];

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo;
    }

    public function abrirCorreccion(int $eventoId): void
    {
        $evento = $this->eventoDelEquipo($eventoId);

        if (! $this->admiteCorreccion($evento)) {
            return;
        }

        $this->eventoSeleccionadoId = $evento->id;
        $this->modo = $this->puedeAnularse($evento) ? 'anulacion' : 'aclaracion';
        $this->justificacion = '';
        $this->resetValidation();

        $this->dispatch('open-modal', 'corregir-evento');
    }

    public function registrarCorreccion(): void
    {
        $evento = $this->eventoDelEquipo($this->eventoSeleccionadoId);

        $this->validate(
            [
                'modo' => ['required', 'in:anulacion,aclaracion'],
                'justificacion' => ['required', 'string', 'min:10', 'max:1000'],
            ],
            [],
            ['justificacion' => __('justificación')],
        );

        if (! $this->admiteCorreccion($evento)) {
            $this->addError('justificacion', __('Este evento ya fue anulado o es una corrección: no admite otra.'));

            return;
        }

        if ($this->modo === 'anulacion' && ! $this->puedeAnularse($evento)) {
            $this->addError('modo', __('Este tipo de evento no se anula desde aquí; regístralo como aclaración.'));

            return;
        }

        $tipo = __(self::TIPO_LABELS[$evento->tipo] ?? $evento->tipo);
        $anula = $this->modo === 'anulacion';

        app(HistorialService::class)->registrar(
            equipo: $this->equipo,
            tipo: 'anulacion_aclaracion',
            usuario: auth()->user(),
            descripcion: __($anula
                ? 'Anulación del evento #:id (:tipo). Justificación: :texto'
                : 'Aclaración del evento #:id (:tipo): :texto', [
                    'id' => $evento->id,
                    'tipo' => $tipo,
                    'texto' => trim($this->justificacion),
                ]),
            // Solo la anulación enlaza el evento: una aclaración no lo deja sin
            // efecto (otros módulos tratan cualquier enlace como anulación).
            eventoAnulado: $anula ? $evento : null,
        );

        $this->reset(['eventoSeleccionadoId', 'justificacion']);
        $this->dispatch('close-modal', 'corregir-evento');

        session()->flash('status', $anula ? __('Evento anulado.') : __('Aclaración registrada.'));
    }

    /**
     * Busca el evento solo dentro de este equipo: un id de otro equipo enviado
     * a mano en la petición no debe poder corregirse desde aquí.
     */
    private function eventoDelEquipo(?int $eventoId): Evento
    {
        return Evento::query()
            ->where('equipo_id', $this->equipo->id)
            ->withCount('anulaciones')
            ->findOrFail($eventoId);
    }

    public function admiteCorreccion(Evento $evento): bool
    {
        return $evento->tipo !== 'anulacion_aclaracion'
            && ($evento->anulaciones_count ?? $evento->anulaciones->count()) === 0;
    }

    public function puedeAnularse(Evento $evento): bool
    {
        return in_array($evento->tipo, self::TIPOS_ANULABLES, true)
            && $this->admiteCorreccion($evento);
    }

    /**
     * RF-33 · Descarga la hoja de vida completa (ficha, componentes e historial)
     * como PDF tamaño carta, sin firma. Se genera al vuelo y no se guarda.
     *
     * Adelantado de la Fase 2 (análisis, sección 13; plan, sección 6) con
     * autorización de Jhon el 7 de octubre de 2026.
     */
    public function exportarPdf(): StreamedResponse
    {
        $this->cargarHojaDeVida();

        $pdf = Pdf::loadView('livewire.equipos.pdf.hoja-de-vida', [
            'equipo' => $this->equipo,
            'generadoPor' => auth()->user()->name,
            'generadoEl' => now(),
        ])->setPaper('letter');

        $nombre = 'hoja-de-vida-'.Str::slug($this->equipo->codigo_activo ?? $this->equipo->serial).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function cargarHojaDeVida(): void
    {
        $this->equipo->load([
            'tipoEquipo',
            'marca',
            'asignacionActual.persona.dependencia',
            'asignacionActual.sede',
            'asignacionActual.piso',
            'asignacionActual.dependencia',
            'configuracionComputo.sistemaOperativo',
            'componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id'),
            'eventos' => fn ($q) => $q->with([
                'usuario',
                'cambioComponente.componenteRetirado.tipoComponente',
                'cambioComponente.componenteInstalado.tipoComponente',
                'diagnostico.motivoBaja',
                'eventoAnulado',
                'anulaciones.usuario',
            ])->orderByDesc('fecha')->orderByDesc('id'),
        ]);
    }

    public function render()
    {
        $this->cargarHojaDeVida();

        return view('livewire.equipos.hoja-de-vida', [
            'eventoSeleccionado' => $this->eventoSeleccionadoId
                ? $this->equipo->eventos->firstWhere('id', $this->eventoSeleccionadoId)
                : null,
        ]);
    }
}
