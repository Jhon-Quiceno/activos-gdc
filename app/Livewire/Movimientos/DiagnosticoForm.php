<?php

namespace App\Livewire\Movimientos;

use App\Models\Diagnostico;
use App\Models\Equipo;
use App\Services\HistorialService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Pantalla "Diagnóstico". Registra un Diagnostico con es_baja=false; si el
 * técnico concluye que el equipo debe salir de servicio, "Continuar a baja"
 * solo navega a movimientos.baja SIN guardar nada (la baja se registra allí).
 */
class DiagnosticoForm extends Component
{
    public Equipo $equipo;

    public string $fechaRevision = '';

    public string $estadoEncontrado = '';

    public string $descripcionEstado = '';

    public string $causa = '';

    public string $recomendaciones = '';

    public bool $generarFormato = false;

    /**
     * Etiquetas del select "Estado encontrado". La tabla `diagnosticos` solo
     * tiene una columna de texto libre para esto (estado_encontrado), así que
     * guardamos la etiqueta elegida junto con la descripción libre en esa
     * misma columna (ver guardar()).
     *
     * @var array<string, string>
     */
    public const ESTADOS_ENCONTRADOS = [
        'funciona_con_fallas' => 'Funciona con fallas',
        'funciona_correctamente' => 'Funciona correctamente',
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
            'fechaRevision' => ['required', 'date'],
            'estadoEncontrado' => ['required', 'in:'.implode(',', array_keys(self::ESTADOS_ENCONTRADOS))],
            'descripcionEstado' => ['required', 'string'],
            'causa' => ['required', 'string'],
            'recomendaciones' => ['nullable', 'string'],
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {
            $evento = app(HistorialService::class)->registrar(
                equipo: $this->equipo,
                tipo: 'diagnostico',
                usuario: auth()->user(),
                datos: [
                    'fecha' => $this->fechaRevision,
                    'estado_firma' => $this->generarFormato ? 'pendiente_de_firma' : 'no_aplica',
                ],
                descripcion: __('Diagnóstico registrado.'),
            );

            $etiquetaEstado = self::ESTADOS_ENCONTRADOS[$this->estadoEncontrado] ?? $this->estadoEncontrado;

            Diagnostico::create([
                'evento_id' => $evento->id,
                'estado_encontrado' => $etiquetaEstado.': '.$this->descripcionEstado,
                'causa' => $this->causa,
                'recomendaciones' => $this->recomendaciones !== '' ? $this->recomendaciones : null,
                'es_baja' => false,
            ]);
        });

        $this->redirect(route('equipos.show', $this->equipo), navigate: true);
    }

    public function continuarABaja(): void
    {
        $this->redirect(route('movimientos.baja', $this->equipo), navigate: true);
    }

    public function render()
    {
        return view('livewire.movimientos.diagnostico-form', [
            'estadosEncontrados' => self::ESTADOS_ENCONTRADOS,
        ]);
    }
}
