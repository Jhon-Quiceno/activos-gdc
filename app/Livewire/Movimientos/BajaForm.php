<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\Equipo;
use App\Models\MotivoBaja;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Pantalla "Registrar baja" (CU-08, RF-25, RF-26, RN-09, RN-10).
 *
 * Tiene tres estados según el equipo:
 * 1. Sin baja: formulario (motivo, estado encontrado, diagnóstico,
 *    recomendaciones y evidencias). Al confirmar se crea el evento de baja
 *    «Pendiente de firma» y se genera el formato de baja.
 * 2. Baja en trámite: panel para descargar el formato y subirlo firmado; al
 *    subirlo, el equipo pasa a «Dado de baja».
 * 3. Dado de baja: resumen y opción de anular la baja con justificación.
 *
 * En los estados 2 y 3 se puede anular la baja registrada por error.
 */
class BajaForm extends Component
{
    use WithFileUploads;

    public Equipo $equipo;

    public ?int $motivoBajaId = null;

    public string $fechaRevision = '';

    public string $estadoEncontrado = '';

    public string $diagnostico = '';

    public string $recomendaciones = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $evidencias = [];

    public string $justificacion = '';

    public bool $mostrarAnulacion = false;

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo;
        $this->cargarEquipo();
        $this->fechaRevision = now()->toDateString();
    }

    private function cargarEquipo(): void
    {
        $this->equipo->refresh()->load([
            'tipoEquipo',
            'marca',
            'asignacionActual.persona',
            'asignacionActual.sede',
            'asignacionActual.piso',
            'asignacionActual.dependencia',
        ]);
    }

    protected function rules(): array
    {
        return [
            'motivoBajaId' => ['required', 'exists:motivos_baja,id'],
            'fechaRevision' => ['required', 'date', 'before_or_equal:today'],
            'estadoEncontrado' => ['required', 'string', 'max:2000'],
            'diagnostico' => ['required', 'string', 'max:4000'],
            'recomendaciones' => ['nullable', 'string', 'max:4000'],
            'evidencias' => ['array', 'max:10'],
            'evidencias.*' => GestorFirmas::reglasArchivo(),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'motivoBajaId' => __('motivo'),
            'fechaRevision' => __('fecha de revisión'),
            'estadoEncontrado' => __('estado encontrado'),
            'evidencias.*' => __('evidencia'),
        ];
    }

    public function quitarEvidencia(int $indice): void
    {
        unset($this->evidencias[$indice]);
        $this->evidencias = array_values($this->evidencias);
    }

    public function confirmar(RegistroMovimientos $movimientos): void
    {
        ReglasMovimiento::asegurarQueAdmiteEventos($this->equipo);
        $this->validate();

        $movimientos->darDeBaja($this->equipo, auth()->user(), [
            'motivo_baja_id' => $this->motivoBajaId,
            'fecha' => $this->fechaRevision,
            'estado_encontrado' => $this->estadoEncontrado,
            'diagnostico' => $this->diagnostico,
            'recomendaciones' => $this->recomendaciones,
        ], $this->evidencias);

        $this->reset(['evidencias', 'estadoEncontrado', 'diagnostico', 'recomendaciones', 'motivoBajaId']);
        $this->cargarEquipo();
    }

    public function anular(RegistroMovimientos $movimientos): void
    {
        $this->validate(
            ['justificacion' => ['required', 'string', 'min:10', 'max:1000']],
            [],
            ['justificacion' => __('justificación')],
        );

        $movimientos->anularBaja($this->equipo, auth()->user(), $this->justificacion);

        $this->reset(['justificacion', 'mostrarAnulacion']);
        $this->cargarEquipo();
        session()->flash('estado-baja', __('Baja anulada. El equipo volvió a su estado anterior y admite movimientos de nuevo.'));
    }

    /**
     * Refresca la pantalla cuando el panel de documentos completa la firma
     * (el equipo acaba de pasar a «Dado de baja»).
     */
    #[On('documentos-actualizados')]
    public function documentosActualizados(): void
    {
        $this->cargarEquipo();
    }

    public function render()
    {
        return view('livewire.movimientos.baja-form', [
            'motivosBaja' => MotivoBaja::orderBy('nombre')->get(),
            'bajaVigente' => ReglasMovimiento::bajaVigente($this->equipo)?->load(['diagnostico.motivoBaja', 'usuario']),
            'maxMb' => (int) round(GestorFirmas::maxKb() / 1024),
        ]);
    }
}
