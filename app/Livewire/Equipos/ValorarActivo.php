<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use App\Models\LevantamientoActivos;
use App\Models\ValoracionActivo;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Formulario de valoración del activo según el Manual GT-M4 (RF-56, RF-57) y
 * reclasificación (RF-58): cada guardado crea una valoración nueva y deja la
 * anterior como histórica.
 *
 * El cálculo y las reglas (RN-22 a RN-25) los hace App\Models\ValoracionActivo;
 * esta pantalla arma los datos, muestra el resultado en vivo y enseña los
 * errores que lanza el modelo junto al campo que corresponde.
 *
 * Es reutilizable (RF-59): se monta con el equipo y, desde un levantamiento,
 * también con su id, que queda enlazado a la valoración.
 *
 *   <livewire:equipos.valorar-activo :equipo="$equipo" />
 *   <livewire:equipos.valorar-activo :equipo="$equipo" :levantamiento-id="$levantamiento->id" />
 *
 * Al guardar emite `valoracion-registrada` (con equipoId y valoracionId).
 */
class ValorarActivo extends Component
{
    #[Locked]
    public Equipo $equipo;

    #[Locked]
    public ?int $levantamientoId = null;

    public string $contieneDatosPersonales = '';

    public string $clasificacionDatos = 'no_aplica';

    public string $infraestructuraCritica = '';

    public string $continuidadNegocio = '';

    public string $propietarioCargo = '';

    public string $custodioCargo = '';

    public string $tipoUbicacion = 'fisico';

    public string $tiempoRetencion = '';

    public ?int $confidencialidad = null;

    public ?int $integridad = null;

    public ?int $disponibilidad = null;

    /** @var array<int, string> */
    public array $controlesMonitoreo = [];

    public const SI_NO = ['si' => 'Sí', 'no' => 'No', 'no_aplica' => 'No aplica'];

    public const TIPOS_UBICACION = [
        'fisico' => 'Físico',
        'electronico' => 'Electrónico',
        'fisico_electronico' => 'Físico y electrónico',
    ];

    /** Los errores del modelo vienen con el nombre de la columna. */
    private const CAMPO_DEL_MODELO = [
        'contiene_datos_personales' => 'contieneDatosPersonales',
        'clasificacion_datos' => 'clasificacionDatos',
        'infraestructura_critica' => 'infraestructuraCritica',
        'continuidad_negocio' => 'continuidadNegocio',
        'propietario_cargo' => 'propietarioCargo',
        'custodio_cargo' => 'custodioCargo',
        'tipo_ubicacion' => 'tipoUbicacion',
        'tiempo_retencion' => 'tiempoRetencion',
        'equipo_id' => 'equipo',
    ];

    public function mount(Equipo $equipo, ?int $levantamientoId = null): void
    {
        $this->equipo = $equipo->loadMissing(['tipoEquipo', 'asignacionActual.persona.cargoOficial']);
        $this->levantamientoId = $levantamientoId;

        $vigente = $equipo->valoracionVigente;

        if ($vigente) {
            // Reclasificar parte de la valoración vigente: solo se cambia lo que cambió.
            $this->contieneDatosPersonales = $vigente->contiene_datos_personales;
            $this->clasificacionDatos = $vigente->clasificacion_datos ?? 'no_aplica';
            $this->infraestructuraCritica = $vigente->infraestructura_critica;
            $this->continuidadNegocio = $vigente->continuidad_negocio;
            $this->propietarioCargo = $vigente->propietario_cargo;
            $this->custodioCargo = $vigente->custodio_cargo;
            $this->tipoUbicacion = $vigente->tipo_ubicacion ?? 'fisico';
            $this->tiempoRetencion = (string) $vigente->tiempo_retencion;
            $this->confidencialidad = $vigente->confidencialidad;
            $this->integridad = $vigente->integridad;
            $this->disponibilidad = $vigente->disponibilidad;
            $this->controlesMonitoreo = array_values(array_filter(array_map('trim', explode(',', (string) $vigente->controles_monitoreo))));

            return;
        }

        // GT-M4 4.2.3: el custodio es el cargo de quien tiene el activo a cargo.
        $this->custodioCargo = (string) $equipo->asignacionActual?->persona?->cargoTexto();
    }

    /**
     * RN-24: sin datos personales la clasificación es «No aplica».
     */
    public function updatedContieneDatosPersonales(): void
    {
        if ($this->contieneDatosPersonales !== 'si') {
            $this->clasificacionDatos = 'no_aplica';
        } elseif ($this->clasificacionDatos === 'no_aplica') {
            $this->clasificacionDatos = '';
        }
    }

    /**
     * Resultado en vivo con el mismo cálculo del modelo (GT-M4 tabla 6 y 4.2.6).
     *
     * @return array{completo: bool, total: int, importancia: ?string, requiere_gestion_riesgo: bool}
     */
    public function resultado(): array
    {
        $completo = $this->confidencialidad !== null && $this->integridad !== null && $this->disponibilidad !== null;

        $calculo = new ValoracionActivo([
            'confidencialidad' => (int) $this->confidencialidad,
            'integridad' => (int) $this->integridad,
            'disponibilidad' => (int) $this->disponibilidad,
        ]);
        $calculo->calcular();

        return [
            'completo' => $completo,
            'total' => (int) $calculo->total,
            'importancia' => $completo ? $calculo->importancia : null,
            'requiere_gestion_riesgo' => $completo && $calculo->requiere_gestion_riesgo,
        ];
    }

    public function esSoporte(): bool
    {
        return (bool) $this->equipo->tipoEquipo?->esSoporte();
    }

    protected function rules(): array
    {
        $siNo = Rule::in(array_keys(self::SI_NO));

        // El rango 1–4, la clasificación de datos (RN-24) y los activos de soporte
        // (RN-25) los valida el modelo: así la regla vive en un solo lugar.
        return [
            'contieneDatosPersonales' => ['required', $siNo],
            'clasificacionDatos' => ['nullable', Rule::in(array_keys(ValoracionActivo::CLASIFICACIONES_DATOS))],
            'infraestructuraCritica' => ['required', $siNo],
            'continuidadNegocio' => ['required', $siNo],
            'propietarioCargo' => ['required', 'string', 'max:120'],
            'custodioCargo' => ['required', 'string', 'max:120'],
            'tipoUbicacion' => ['required', Rule::in(array_keys(self::TIPOS_UBICACION))],
            'tiempoRetencion' => ['nullable', 'string', 'max:40'],
            'confidencialidad' => ['required', 'integer'],
            'integridad' => ['required', 'integer'],
            'disponibilidad' => ['required', 'integer'],
            'controlesMonitoreo' => ['array'],
            'controlesMonitoreo.*' => [Rule::in(ValoracionActivo::CONTROLES_MONITOREO)],
        ];
    }

    protected function messages(): array
    {
        return [
            'confidencialidad.required' => __('Elige un nivel de confidencialidad.'),
            'integridad.required' => __('Elige un nivel de integridad.'),
            'disponibilidad.required' => __('Elige un nivel de disponibilidad.'),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'contieneDatosPersonales' => __('contiene datos personales'),
            'clasificacionDatos' => __('clasificación de los datos'),
            'infraestructuraCritica' => __('infraestructura crítica'),
            'continuidadNegocio' => __('continuidad del negocio'),
            'propietarioCargo' => __('propietario (cargo)'),
            'custodioCargo' => __('custodio (cargo)'),
            'tipoUbicacion' => __('tipo de ubicación'),
            'tiempoRetencion' => __('tiempo de retención'),
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        $controles = implode(', ', $this->controlesMonitoreo);

        try {
            $valoracion = ValoracionActivo::registrar(
                equipo: $this->equipo,
                usuario: auth()->user(),
                datos: [
                    'contiene_datos_personales' => $this->contieneDatosPersonales,
                    'clasificacion_datos' => $this->clasificacionDatos !== '' ? $this->clasificacionDatos : null,
                    'infraestructura_critica' => $this->infraestructuraCritica,
                    'continuidad_negocio' => $this->continuidadNegocio,
                    'propietario_cargo' => trim($this->propietarioCargo),
                    'custodio_cargo' => trim($this->custodioCargo),
                    'tipo_ubicacion' => $this->tipoUbicacion,
                    'tiempo_retencion' => trim($this->tiempoRetencion) !== '' ? trim($this->tiempoRetencion) : null,
                    'confidencialidad' => $this->confidencialidad,
                    'integridad' => $this->integridad,
                    'disponibilidad' => $this->disponibilidad,
                    'controles_monitoreo' => $controles !== '' ? mb_substr($controles, 0, 200) : null,
                ],
                levantamiento: $this->levantamientoId ? LevantamientoActivos::find($this->levantamientoId) : null,
            );
        } catch (ValidationException $e) {
            // Muestra cada error del modelo (RN-24, RN-25, rango 1–4) en su campo.
            $errores = [];
            foreach ($e->errors() as $campo => $mensajes) {
                $errores[self::CAMPO_DEL_MODELO[$campo] ?? $campo] = $mensajes;
            }

            throw ValidationException::withMessages($errores);
        }

        $this->dispatch('valoracion-registrada', equipoId: $this->equipo->id, valoracionId: $valoracion->id);
        $this->dispatch('close-modal', 'valorar-activo');
    }

    public function render()
    {
        return view('livewire.equipos.valorar-activo', [
            'resultado' => $this->resultado(),
            'escalas' => [
                'confidencialidad' => ['titulo' => __('Confidencialidad'), 'pregunta' => __('¿Qué pasaría si la información del activo la conoce alguien no autorizado?'), 'niveles' => ValoracionActivo::ESCALA_CONFIDENCIALIDAD],
                'integridad' => ['titulo' => __('Integridad'), 'pregunta' => __('¿Qué pasaría si la información se pierde o se altera?'), 'niveles' => ValoracionActivo::ESCALA_INTEGRIDAD],
                'disponibilidad' => ['titulo' => __('Disponibilidad'), 'pregunta' => __('¿Cuánto tiempo puede estar el activo sin funcionar?'), 'niveles' => ValoracionActivo::ESCALA_DISPONIBILIDAD],
            ],
            'clasificaciones' => ValoracionActivo::CLASIFICACIONES_DATOS,
            'importancias' => ValoracionActivo::IMPORTANCIAS,
            'controles' => ValoracionActivo::CONTROLES_MONITOREO,
            'esReclasificacion' => $this->equipo->valoracionVigente()->exists(),
        ]);
    }
}
