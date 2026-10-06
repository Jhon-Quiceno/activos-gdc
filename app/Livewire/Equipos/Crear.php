<?php

namespace App\Livewire\Equipos;

use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Services\HistorialService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Pantalla "RegistrarEquipo" (RF-01/RF-02): formulario de alta de un equipo.
 *
 * Toda la reactividad (mostrar la sección 4 según la familia del tipo elegido,
 * mostrar/ocultar campos de propiedad/antivirus/responsable, etc.) se resuelve
 * con propiedades públicas de Livewire + @if en la vista; no hace falta Alpine.
 */
class Crear extends Component
{
    // --- 1. Tipo de equipo ---
    public ?int $tipoEquipoId = null;

    public ?string $familiaSeleccionada = null;

    // --- 2. Identificación ---
    public string $serial = '';

    public string $codigoActivo = '';

    public bool $sinCodigoActivo = false;

    public ?int $marcaId = null;

    public string $modelo = '';

    public string $estadoFuncionamiento = '';

    // --- 3. Propiedad ---
    public string $propiedad = 'gobernacion';

    public string $propietarioTercero = '';

    /**
     * Figura jurídica del tercero. No existe columna para esto en `equipos`
     * (ver migración), así que se captura para completar el formulario del
     * prototipo pero no se persiste, igual que "Observaciones" más abajo.
     */
    public string $figura = '';

    // --- 4. Cómputo ---
    public string $procesador = '';

    public string $memoriaRam = '';

    public string $tipoDisco = '';

    public string $capacidadDisco = '';

    public ?int $sistemaOperativoId = null;

    public string $tieneAntivirus = 'no';

    public string $antivirusProducto = '';

    public string $nombreRed = '';

    /** @var array<int, array{nombre: string, tiene: bool, marca: string, serial: string}> */
    public array $perifericos = [
        ['nombre' => 'Teclado', 'tiene' => false, 'marca' => '', 'serial' => ''],
        ['nombre' => 'Mouse', 'tiene' => false, 'marca' => '', 'serial' => ''],
        ['nombre' => 'Sonido', 'tiene' => false, 'marca' => '', 'serial' => ''],
        ['nombre' => 'Cámara', 'tiene' => false, 'marca' => '', 'serial' => ''],
    ];

    // --- 4. Monitor (familia "video") ---
    public string $tamanoPulgadas = '';

    public string $conexionMonitor = '';

    // --- 4. Impresión ---
    public string $funcionesImpresora = '';

    public string $tipoImpresion = '';

    public string $conexionImpresora = '';

    // --- 4. Digitalización ---
    public string $tipoEscaner = '';

    public string $conexionEscaner = '';

    // --- 4. Energía ---
    public string $tipoEnergia = '';

    public string $capacidadVa = '';

    public string $numTomas = '';

    // --- 4. Conectividad ---
    public string $numPuertos = '';

    public string $administrable = '';

    public string $velocidad = '';

    // --- 4. Proyección ---
    public string $lumenes = '';

    public string $resolucion = '';

    // --- 5. Responsable y ubicación ---
    public bool $asignarResponsable = false;

    public string $responsableNombre = '';

    public string $responsableCedula = '';

    public string $responsableCargo = '';

    public string $vinculacion = '';

    public ?int $dependenciaId = null;

    public ?int $sedeId = null;

    public ?int $pisoId = null;

    public string $observaciones = '';

    /**
     * Etiquetas visibles de cada familia (RF-02). Las claves son los valores
     * reales del enum `tipos_equipo.familia` (ver CatalogosSeeder).
     *
     * @var array<string, string>
     */
    private const FAMILIA_LABELS = [
        'computo' => 'Cómputo',
        'video' => 'Video',
        'impresion' => 'Impresión',
        'digitalizacion' => 'Digitalización',
        'energia' => 'Energía',
        'conectividad' => 'Conectividad',
        'proyeccion' => 'Proyección',
    ];

    public function seleccionarTipo(int $tipoEquipoId): void
    {
        $nuevaFamilia = TipoEquipo::find($tipoEquipoId)?->familia;

        // Si cambia de familia, limpiamos los campos específicos de la sección 4
        // anterior para no arrastrar datos que ya no aplican.
        if ($nuevaFamilia !== $this->familiaSeleccionada) {
            $this->resetCamposFamilia();
        }

        $this->tipoEquipoId = $tipoEquipoId;
        $this->familiaSeleccionada = $nuevaFamilia;
    }

    private function resetCamposFamilia(): void
    {
        $this->reset([
            'procesador', 'memoriaRam', 'tipoDisco', 'capacidadDisco', 'sistemaOperativoId',
            'tieneAntivirus', 'antivirusProducto', 'nombreRed',
            'tamanoPulgadas', 'conexionMonitor',
            'funcionesImpresora', 'tipoImpresion', 'conexionImpresora',
            'tipoEscaner', 'conexionEscaner',
            'tipoEnergia', 'capacidadVa', 'numTomas',
            'numPuertos', 'administrable', 'velocidad',
            'lumenes', 'resolucion',
        ]);

        $this->perifericos = [
            ['nombre' => 'Teclado', 'tiene' => false, 'marca' => '', 'serial' => ''],
            ['nombre' => 'Mouse', 'tiene' => false, 'marca' => '', 'serial' => ''],
            ['nombre' => 'Sonido', 'tiene' => false, 'marca' => '', 'serial' => ''],
            ['nombre' => 'Cámara', 'tiene' => false, 'marca' => '', 'serial' => ''],
        ];
    }

    protected function rules(): array
    {
        $rules = [
            'tipoEquipoId' => ['required', 'exists:tipos_equipo,id'],
            'serial' => ['required', 'string', 'max:255', 'unique:equipos,serial'],
            'codigoActivo' => ['nullable', 'string', 'max:255', 'unique:equipos,codigo_activo'],
            'marcaId' => ['required', 'exists:marcas,id'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'estadoFuncionamiento' => ['required', 'string'],
            'propiedad' => ['required', 'in:gobernacion,tercero'],
            'sedeId' => ['required', 'exists:sedes,id'],
            'pisoId' => ['required', 'exists:pisos,id'],
        ];

        if ($this->propiedad === 'tercero') {
            $rules['propietarioTercero'] = ['required', 'string', 'max:255'];
            $rules['figura'] = ['required', 'string'];
        }

        if ($this->asignarResponsable) {
            $rules['responsableNombre'] = ['required', 'string', 'max:255'];
            $rules['vinculacion'] = ['required', 'in:planta,contratista'];
            $rules['dependenciaId'] = ['required', 'exists:dependencias,id'];
        }

        return $rules;
    }

    public function guardar(): void
    {
        $equipo = $this->guardarEquipo();

        $this->redirect(route('equipos.show', $equipo), navigate: true);
    }

    public function guardarYRegistrarOtro(): void
    {
        $this->guardarEquipo();

        $this->reset();

        session()->flash('status', __('Equipo guardado. Podés registrar otro a continuación.'));
    }

    private function guardarEquipo(): Equipo
    {
        // Normaliza antes de validar: si se marcó "no tiene código", el input
        // queda vacío/deshabilitado en la vista, pero igual podría traer texto
        // residual; nos aseguramos de que viaje null.
        $this->codigoActivo = $this->sinCodigoActivo ? '' : $this->codigoActivo;

        $this->validate();

        return DB::transaction(function () {
            $equipo = Equipo::create([
                'serial' => $this->serial,
                'codigo_activo' => $this->codigoActivo !== '' ? $this->codigoActivo : null,
                'tipo_equipo_id' => $this->tipoEquipoId,
                'marca_id' => $this->marcaId,
                'modelo' => $this->modelo !== '' ? $this->modelo : null,
                'propiedad' => $this->propiedad,
                'propietario_tercero' => $this->propiedad === 'tercero' ? $this->propietarioTercero : null,
                'estado_funcionamiento' => $this->estadoFuncionamiento,
                // RN: sin responsable asignado el equipo queda "sin_asignar" (bodega);
                // con responsable queda "en_servicio".
                'estado_ciclo_vida' => $this->asignarResponsable ? 'en_servicio' : 'sin_asignar',
                // El serial es obligatorio en este formulario, así que el equipo
                // siempre nace verificado (ver resumen de la pantalla).
                'verificacion' => 'verificado',
            ]);

            if ($this->familiaSeleccionada === 'computo') {
                $this->guardarConfiguracionComputo($equipo);
            }

            $this->guardarUbicacionResponsable($equipo);

            app(HistorialService::class)->registrar(
                equipo: $equipo,
                tipo: 'alta',
                usuario: auth()->user(),
                descripcion: 'Alta registrada desde el formulario de registro.',
            );

            return $equipo;
        });
    }

    private function guardarConfiguracionComputo(Equipo $equipo): void
    {
        ConfiguracionComputo::create([
            'equipo_id' => $equipo->id,
            'sistema_operativo_id' => $this->sistemaOperativoId,
            'tiene_antivirus' => $this->tieneAntivirus === 'si',
            'antivirus_producto' => $this->tieneAntivirus === 'si' && $this->antivirusProducto !== ''
                ? $this->antivirusProducto
                : null,
            'nombre_red' => $this->nombreRed !== '' ? $this->nombreRed : null,
        ]);

        $tipoComponenteIds = TipoComponente::whereIn('nombre', ['Procesador', 'RAM', 'Disco'])
            ->pluck('id', 'nombre');

        if ($this->procesador !== '' && $tipoComponenteIds->has('Procesador')) {
            Componente::create([
                'equipo_id' => $equipo->id,
                'tipo_componente_id' => $tipoComponenteIds['Procesador'],
                'capacidad_caracteristica' => $this->procesador,
                'fecha_instalacion' => now(),
            ]);
        }

        if ($this->memoriaRam !== '' && $tipoComponenteIds->has('RAM')) {
            Componente::create([
                'equipo_id' => $equipo->id,
                'tipo_componente_id' => $tipoComponenteIds['RAM'],
                'capacidad_caracteristica' => $this->memoriaRam,
                'fecha_instalacion' => now(),
            ]);
        }

        if (($this->tipoDisco !== '' || $this->capacidadDisco !== '') && $tipoComponenteIds->has('Disco')) {
            Componente::create([
                'equipo_id' => $equipo->id,
                'tipo_componente_id' => $tipoComponenteIds['Disco'],
                'capacidad_caracteristica' => trim($this->tipoDisco.' '.$this->capacidadDisco),
                'fecha_instalacion' => now(),
            ]);
        }

        $perifericoIds = TipoComponente::where('es_periferico', true)->pluck('id', 'nombre');

        foreach ($this->perifericos as $fila) {
            if (! empty($fila['tiene']) && $perifericoIds->has($fila['nombre'])) {
                Componente::create([
                    'equipo_id' => $equipo->id,
                    'tipo_componente_id' => $perifericoIds[$fila['nombre']],
                    'marca' => $fila['marca'] !== '' ? $fila['marca'] : null,
                    'serial' => $fila['serial'] !== '' ? $fila['serial'] : null,
                    'fecha_instalacion' => now(),
                ]);
            }
        }
    }

    private function guardarUbicacionResponsable(Equipo $equipo): void
    {
        $persona = null;

        if ($this->asignarResponsable) {
            $datosPersona = [
                'nombre' => $this->responsableNombre,
                'cargo' => $this->responsableCargo !== '' ? $this->responsableCargo : null,
                'dependencia_id' => $this->dependenciaId,
                'tipo_vinculacion' => $this->vinculacion !== '' ? $this->vinculacion : null,
            ];

            // RN: si ya existe una persona con esa cédula, se reutiliza en vez de
            // duplicarla; si no, se crea una nueva (no hace falta autocompletar).
            $persona = $this->responsableCedula !== ''
                ? Persona::firstOrCreate(['cedula' => $this->responsableCedula], $datosPersona)
                : Persona::create($datosPersona);
        }

        Asignacion::create([
            'equipo_id' => $equipo->id,
            'persona_id' => $persona?->id,
            'sede_id' => $this->sedeId,
            'piso_id' => $this->pisoId,
            'dependencia_id' => $this->asignarResponsable ? $this->dependenciaId : null,
            'fecha_inicio' => now(),
        ]);
    }

    public function render()
    {
        $tiposPorFamilia = TipoEquipo::orderBy('nombre')->get()->groupBy('familia');

        $tipoSeleccionado = $this->tipoEquipoId
            ? $tiposPorFamilia->flatten()->firstWhere('id', $this->tipoEquipoId)
            : null;

        $resumen = [
            __('Tipo') => $tipoSeleccionado?->nombre ?? '—',
            __('Familia') => $this->familiaSeleccionada
                ? (self::FAMILIA_LABELS[$this->familiaSeleccionada] ?? $this->familiaSeleccionada)
                : '—',
            __('Propiedad') => $this->propiedad === 'tercero' ? __('Tercero') : __('Gobernación'),
            __('Estado inicial') => $this->asignarResponsable ? __('En servicio') : __('Sin asignar'),
        ];

        return view('livewire.equipos.page-crear', [
            'familiaLabels' => self::FAMILIA_LABELS,
            'tiposPorFamilia' => $tiposPorFamilia,
            'marcas' => Marca::orderBy('nombre')->get(),
            'sistemasOperativos' => SistemaOperativo::orderBy('nombre')->get(),
            'dependencias' => Dependencia::orderBy('nombre')->get(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'pisos' => Piso::orderBy('numero')->get(),
            'resumen' => $resumen,
        ]);
    }
}
