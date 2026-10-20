<?php

namespace App\Livewire\Levantamientos;

use App\Livewire\Levantamientos\Soporte\GestorLevantamientos;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Models\Equipo;
use App\Models\LevantamientoActivos;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trabajo de un levantamiento (RF-59, CU-18 pasos 2 a 5): equipos de la
 * dependencia y sus subdependencias en la sede, verificación en sitio,
 * valoración con el formulario de Equipos (enlazada al levantamiento), acta
 * prellenada y carga del acta firmada.
 */
class Detalle extends Component
{
    use WithFileUploads;

    #[Locked]
    public LevantamientoActivos $levantamiento;

    /** Equipo que se está valorando en la ventana. */
    #[Locked]
    public ?int $valorandoId = null;

    /** '' | sin_valoracion | por_verificar */
    #[Url]
    public string $filtro = '';

    public $actaFirmada;

    public function mount(LevantamientoActivos $levantamiento): void
    {
        $this->levantamiento = $levantamiento;
    }

    public function verificar(int $equipoId, GestorLevantamientos $gestor): void
    {
        $gestor->verificar($this->levantamiento, Equipo::findOrFail($equipoId), auth()->user());
        $this->levantamiento->refresh();
        session()->flash('status', __('Equipo verificado en sitio.'));
    }

    public function valorar(int $equipoId, GestorLevantamientos $gestor): void
    {
        if ($this->levantamiento->estado === 'aceptado' || ! $gestor->equipos($this->levantamiento)->whereKey($equipoId)->exists()) {
            return;
        }

        $this->valorandoId = $equipoId;
        $this->dispatch('open-modal', 'valorar-en-levantamiento');
    }

    #[On('valoracion-registrada')]
    public function valoracionRegistrada(): void
    {
        app(GestorLevantamientos::class)->registrarCambio($this->levantamiento);
        $this->levantamiento->refresh();
        $this->valorandoId = null;
        session()->flash('status', __('Valoración registrada en el levantamiento.'));
    }

    public function descargarActa(GestorLevantamientos $gestor): ?StreamedResponse
    {
        try {
            $pdf = $gestor->acta($this->levantamiento, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('acta', $e->errors()['acta'][0] ?? $e->getMessage());

            return null;
        }

        $this->levantamiento->refresh();

        return response()->streamDownload(fn () => print ($pdf->output()), "acta-{$this->levantamiento->codigo}.pdf", ['Content-Type' => 'application/pdf']);
    }

    public function subirActa(GestorLevantamientos $gestor): void
    {
        $this->validate(
            ['actaFirmada' => ['required', 'file', 'mimes:pdf', 'max:'.GestorFirmas::maxKb()]],
            [],
            ['actaFirmada' => __('acta firmada')],
        );

        $gestor->aceptar($this->levantamiento, $this->actaFirmada, auth()->user());

        $this->reset('actaFirmada');
        $this->levantamiento->refresh();
        session()->flash('status', __('Acta firmada cargada: el levantamiento quedó aceptado.'));
    }

    public function verActaFirmada(GestorLevantamientos $gestor): ?StreamedResponse
    {
        if (! $this->levantamiento->acta_path) {
            return null;
        }

        abort_unless($gestor->actaFirmadaExiste($this->levantamiento), 404);

        return Storage::disk(GestorLevantamientos::DISCO)->download($this->levantamiento->acta_path, "acta-firmada-{$this->levantamiento->codigo}.pdf");
    }

    public function render(GestorLevantamientos $gestor)
    {
        $todos = $gestor->equipos($this->levantamiento)
            ->with(['tipoEquipo', 'marca', 'modeloEquipo', 'valoracionVigente', 'asignacionActual.persona', 'asignacionActual.piso', 'asignacionActual.dependencia'])
            ->orderBy('numero_activo')
            ->get();

        $sinValoracion = fn (Equipo $e) => ! $e->valoracionVigente && ! $e->tipoEquipo?->esSoporte();
        $equipos = match ($this->filtro) {
            'sin_valoracion' => $todos->filter($sinValoracion),
            'por_verificar' => $todos->where('verificacion', 'pendiente_de_verificar'),
            default => $todos,
        };

        return view('livewire.levantamientos.detalle', [
            'equipos' => $equipos,
            'kpis' => [
                'total' => $todos->count(),
                'sin_valoracion' => $todos->filter($sinValoracion)->count(),
                'por_verificar' => $todos->where('verificacion', 'pendiente_de_verificar')->count(),
                'valorados_aqui' => $this->levantamiento->valoraciones()->count(),
            ],
            'valorando' => $this->valorandoId ? $todos->firstWhere('id', $this->valorandoId) : null,
            'maxMb' => (int) round(GestorFirmas::maxKb() / 1024),
        ]);
    }
}
