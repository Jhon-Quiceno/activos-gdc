<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\Equipo;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Pantalla "Diagnóstico" (CU-07, RF-24): estado encontrado, causa,
 * recomendaciones y evidencias. Si el técnico concluye que el equipo debe
 * salir de servicio, "Continuar a baja" solo navega a movimientos.baja SIN
 * guardar nada (la baja se registra allí con su propio diagnóstico).
 *
 * Si se marca "Generar formato", el evento queda «Pendiente de firma» y se
 * genera el formato de baja (que es el que lleva diagnóstico y
 * recomendaciones; decisión 12: solo existen los formatos de entrega y baja).
 */
class DiagnosticoForm extends Component
{
    use WithFileUploads;

    public Equipo $equipo;

    public string $fechaRevision = '';

    public string $estadoEncontrado = '';

    public string $descripcionEstado = '';

    public string $causa = '';

    public string $recomendaciones = '';

    public bool $generarFormato = false;

    /** @var array<int, TemporaryUploadedFile> */
    public array $evidencias = [];

    public ?int $eventoId = null;

    /**
     * Etiquetas del select "Estado encontrado". La tabla `diagnosticos` solo
     * tiene una columna de texto libre para esto (estado_encontrado), así que
     * se guarda la etiqueta elegida junto con la descripción libre.
     *
     * @var array<string, string>
     */
    public const ESTADOS_ENCONTRADOS = [
        'funciona_correctamente' => 'Funciona correctamente',
        'funciona_con_fallas' => 'Funciona con fallas',
        'no_funciona' => 'No funciona',
    ];

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load(['tipoEquipo', 'marca']);
        $this->fechaRevision = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'fechaRevision' => ['required', 'date', 'before_or_equal:today'],
            'estadoEncontrado' => ['required', 'in:'.implode(',', array_keys(self::ESTADOS_ENCONTRADOS))],
            'descripcionEstado' => ['required', 'string', 'max:2000'],
            'causa' => ['required', 'string', 'max:4000'],
            'recomendaciones' => ['nullable', 'string', 'max:4000'],
            'evidencias' => ['array', 'max:10'],
            'evidencias.*' => GestorFirmas::reglasArchivo(),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'fechaRevision' => __('fecha de revisión'),
            'estadoEncontrado' => __('estado encontrado'),
            'descripcionEstado' => __('descripción del estado'),
            'evidencias.*' => __('evidencia'),
        ];
    }

    public function quitarEvidencia(int $indice): void
    {
        unset($this->evidencias[$indice]);
        $this->evidencias = array_values($this->evidencias);
    }

    public function guardar(RegistroMovimientos $movimientos): void
    {
        ReglasMovimiento::asegurarQueAdmiteEventos($this->equipo, permitirConBajaEnTramite: true);
        $this->validate();

        $etiquetaEstado = self::ESTADOS_ENCONTRADOS[$this->estadoEncontrado] ?? $this->estadoEncontrado;

        $evento = $movimientos->diagnosticar($this->equipo, auth()->user(), [
            'fecha' => $this->fechaRevision,
            'estado_encontrado' => __($etiquetaEstado).': '.$this->descripcionEstado,
            'causa' => $this->causa,
            'recomendaciones' => $this->recomendaciones,
            'generar_formato' => $this->generarFormato,
        ], $this->evidencias);

        if (! $this->generarFormato) {
            $this->redirect(route('equipos.show', $this->equipo), navigate: true);

            return;
        }

        $this->evidencias = [];
        $this->eventoId = $evento->id;
    }

    public function continuarABaja(): void
    {
        $this->redirect(route('movimientos.baja', $this->equipo), navigate: true);
    }

    public function render()
    {
        return view('livewire.movimientos.diagnostico-form', [
            'estadosEncontrados' => self::ESTADOS_ENCONTRADOS,
            'dadoDeBaja' => $this->equipo->estado_ciclo_vida === 'dado_de_baja',
            'maxMb' => (int) round(GestorFirmas::maxKb() / 1024),
        ]);
    }
}
