<?php

namespace App\Livewire\Movimientos;

use App\Models\Diagnostico;
use App\Models\Equipo;
use App\Models\MotivoBaja;
use App\Services\HistorialService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Pantalla "Registrar baja". Crea el Diagnostico definitivo (es_baja=true) y
 * mueve el equipo a estado_ciclo_vida=dado_de_baja. El diagnóstico de salida
 * se guarda en las mismas columnas que un diagnóstico normal (estado_encontrado
 * / causa / recomendaciones): aquí "Diagnóstico" del formulario corresponde a
 * la columna `causa`.
 */
class BajaForm extends Component
{
    public Equipo $equipo;

    public ?int $motivoBajaId = null;

    public string $fechaRevision = '';

    public string $estadoEncontrado = '';

    public string $diagnostico = '';

    public string $recomendaciones = '';

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load([
            'tipoEquipo',
            'marca',
            'asignacionActual.persona',
            'asignacionActual.sede',
            'asignacionActual.piso',
            'asignacionActual.dependencia',
        ]);

        $this->fechaRevision = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'motivoBajaId' => ['required', 'exists:motivos_baja,id'],
            'fechaRevision' => ['required', 'date'],
            'estadoEncontrado' => ['required', 'string'],
            'diagnostico' => ['required', 'string'],
            'recomendaciones' => ['nullable', 'string'],
        ];
    }

    public function confirmar(): void
    {
        $this->validate();

        DB::transaction(function () {
            $evento = app(HistorialService::class)->registrar(
                equipo: $this->equipo,
                tipo: 'baja',
                usuario: auth()->user(),
                datos: ['fecha' => $this->fechaRevision, 'estado_firma' => 'pendiente_de_firma'],
                descripcion: __('Baja registrada.'),
            );

            Diagnostico::create([
                'evento_id' => $evento->id,
                'estado_encontrado' => $this->estadoEncontrado,
                'causa' => $this->diagnostico,
                'recomendaciones' => $this->recomendaciones !== '' ? $this->recomendaciones : null,
                'es_baja' => true,
                'motivo_baja_id' => $this->motivoBajaId,
            ]);

            $this->equipo->estado_ciclo_vida = 'dado_de_baja';
            $this->equipo->save();
        });

        $this->redirect(route('equipos.show', $this->equipo), navigate: true);
    }

    public function render()
    {
        return view('livewire.movimientos.baja-form', [
            'motivosBaja' => MotivoBaja::orderBy('nombre')->get(),
        ]);
    }
}
