<?php

namespace App\Livewire\Equipos;

use App\Livewire\Equipos\Concerns\NormalizaIdentificadores;
use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Services\HistorialService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
    use NormalizaIdentificadores;

    // --- 1. Tipo de equipo ---
    public ?int $tipoEquipoId = null;

    public ?string $familiaSeleccionada = null;

    // --- 2. Identificación ---
    public string $serial = '';

    /**
     * RN-02: el inventario 2026 trae equipos sin serial legible. En vez de exigir
     * una migración para permitir serial nulo, se guarda uno provisional
     * (`PENDIENTE-REG-######`) y el equipo nace "pendiente de verificar"; al
     * confirmar el serial real se corrige luego desde "Editar datos".
     */
    public bool $sinSerial = false;

    public string $codigoActivo = '';

    public bool $sinCodigoActivo = false;

    /**
     * RN-03: obligatoria solo si el código de activo ya existe en otro equipo
     * (p. ej. un All in One que comparte código con su pantalla integrada).
     */
    public string $codigoActivoJustificacion = '';

    public ?int $marcaId = null;

    public string $modelo = '';

    public string $estadoFuncionamiento = '';

    // --- 3. Propiedad ---
    public string $propiedad = 'gobernacion';

    public string $propietarioTercero = '';

    /**
     * Figura jurídica del tercero (RF-04): comodato, convenio o proveedor.
     * Se guarda en `equipos.figura_tercero`.
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
            // RN-02: obligatorio y único en todo el inventario, incluidos los dados de baja,
            // salvo que se marque que el equipo no tiene serial (se genera uno provisional).
            'serial' => [
                $this->sinSerial ? 'nullable' : 'required',
                'string',
                'max:255',
                $this->noRepetidoEnOtroEquipo('serial'),
            ],
            // RN-03: obligatorio salvo que se marque que el equipo no tiene código.
            // Puede repetirse, pero entonces se exige justificación (abajo).
            'codigoActivo' => [
                $this->sinCodigoActivo ? 'nullable' : 'required',
                'string',
                'max:255',
                'regex:'.self::PATRON_CODIGO_ACTIVO,
            ],
            // Un equipo tiene que quedar identificable por al menos una de las dos vías.
            'sinSerial' => [$this->noAmbosSinIdentificar()],
            'codigoActivoJustificacion' => [
                $this->equipoConMismoCodigo() ? 'required' : 'nullable',
                'string',
                'min:10',
                'max:1000',
            ],
            'marcaId' => ['required', 'exists:marcas,id'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'estadoFuncionamiento' => ['required', 'string'],
            'propiedad' => ['required', 'in:gobernacion,tercero'],
            // RF-19: la ubicación siempre lleva sede, piso y dependencia, tenga o no responsable.
            'sedeId' => ['required', 'exists:sedes,id'],
            'pisoId' => ['required', 'exists:pisos,id'],
            'dependenciaId' => ['required', 'exists:dependencias,id'],
        ];

        if ($this->propiedad === 'tercero') {
            $rules['propietarioTercero'] = ['required', 'string', 'max:255'];
            // RF-04: mismas opciones que el enum `equipos.figura_tercero`.
            $rules['figura'] = ['required', 'in:comodato,convenio,proveedor'];
        }

        // Características por tipo (RF-03): números enteros donde aplica.
        $rules['numTomas'] = ['nullable', 'integer', 'min:0', 'max:100'];
        $rules['numPuertos'] = ['nullable', 'integer', 'min:0', 'max:1000'];
        $rules['observaciones'] = ['nullable', 'string', 'max:2000'];

        if ($this->asignarResponsable) {
            $rules['responsableNombre'] = ['required', 'string', 'max:255'];
            $rules['responsableCedula'] = ['nullable', 'regex:/^\d+$/', 'max:15'];
            $rules['vinculacion'] = ['required', 'in:planta,contratista'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'serial.required' => __('Escribe el serial o marca «El equipo no tiene serial».'),
            'codigoActivo.required' => __('Escribe el código de activo o marca «El equipo no tiene código de activo».'),
            'codigoActivo.regex' => __('El código de activo debe tener el formato I1-###### (por ejemplo, I1-024147).'),
            'codigoActivoJustificacion.required' => __('Este código de activo ya está en otro equipo: escribe por qué se repite (RN-03).'),
            'responsableCedula.regex' => __('La cédula solo puede tener números.'),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'tipoEquipoId' => __('tipo de equipo'),
            'serial' => __('serial'),
            'codigoActivo' => __('código de activo'),
            'codigoActivoJustificacion' => __('justificación'),
            'marcaId' => __('marca'),
            'estadoFuncionamiento' => __('estado de funcionamiento'),
            'propietarioTercero' => __('propietario'),
            'figura' => __('figura'),
            'numTomas' => __('número de tomas'),
            'numPuertos' => __('número de puertos'),
            'observaciones' => __('observaciones'),
            'responsableNombre' => __('responsable'),
            'responsableCedula' => __('cédula'),
            'vinculacion' => __('vinculación'),
            'dependenciaId' => __('dependencia'),
            'sedeId' => __('sede'),
            'pisoId' => __('piso'),
        ];
    }

    /**
     * RF-02 / RN-03: otro equipo que ya tiene este código de activo, si existe.
     * La vista lo usa para avisar con cuál choca y pedir la justificación.
     */
    public function equipoConMismoCodigo(): ?Equipo
    {
        if ($this->sinCodigoActivo || $this->codigoActivo === '') {
            return null;
        }

        return Equipo::with('tipoEquipo')->where('codigo_activo', $this->codigoActivo)->first();
    }

    /**
     * RN-02: serial provisional cuando el equipo no trae uno legible, con el
     * mismo formato que ya usa la importación del inventario 2026. Se reintenta
     * por si choca por azar con uno ya existente (el serial es único).
     */
    private function serialProvisional(): string
    {
        do {
            $candidato = 'PENDIENTE-REG-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Equipo::where('serial', $candidato)->exists());

        return $candidato;
    }

    /**
     * Un equipo necesita al menos un identificador: no se puede marcar a la vez
     * «no tiene serial» y «no tiene código de activo».
     */
    private function noAmbosSinIdentificar(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->sinSerial && $this->sinCodigoActivo) {
                $fail(__('El equipo debe tener al menos serial o código de activo: no se pueden marcar las dos casillas a la vez.'));
            }
        };
    }

    /**
     * RF-02: si el valor ya pertenece a otro equipo, avisa cuál es, para que la
     * persona pueda revisarlo en vez de recibir solo un «ya está en uso».
     */
    private function noRepetidoEnOtroEquipo(string $columna): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($columna): void {
            $otro = Equipo::with('tipoEquipo')->where($columna, $value)->first();

            if ($otro) {
                $fail(__('Ya está registrado en otro equipo: :tipo con serial :serial.', [
                    'tipo' => $otro->tipoEquipo?->nombre ?? __('equipo'),
                    'serial' => $otro->serial,
                ]));
            }
        };
    }

    // Al salir de cada campo se normaliza y se valida de una vez, para avisar
    // de un duplicado o de un formato inválido antes de llegar a «Guardar».

    public function updatedSerial(): void
    {
        $this->serial = $this->normalizarSerial($this->serial);

        if ($this->serial !== '') {
            $this->validateOnly('serial');
        }
    }

    public function updatedCodigoActivo(): void
    {
        $this->codigoActivo = $this->normalizarCodigoActivo($this->codigoActivo);

        if ($this->codigoActivo !== '') {
            $this->validateOnly('codigoActivo');
        }
    }

    public function updatedSinCodigoActivo(): void
    {
        if ($this->sinCodigoActivo) {
            $this->codigoActivo = '';
            $this->codigoActivoJustificacion = '';
            $this->resetValidation(['codigoActivo', 'codigoActivoJustificacion']);
        }
    }

    public function updatedSinSerial(): void
    {
        if ($this->sinSerial) {
            $this->serial = '';
            $this->resetValidation(['serial']);
        }
    }

    public function updatedResponsableCedula(): void
    {
        $this->responsableCedula = $this->normalizarCedula($this->responsableCedula);

        if ($this->responsableCedula !== '') {
            $this->validateOnly('responsableCedula');
        }
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
        // Normaliza antes de validar (los hooks updated* ya lo hacen al salir de
        // cada campo, pero se repite por si se guarda sin haber salido de él).
        // Si se marcó "no tiene código", el input queda vacío/deshabilitado en la
        // vista, pero igual podría traer texto residual; nos aseguramos de que viaje null.
        $this->serial = $this->sinSerial ? '' : $this->normalizarSerial($this->serial);
        $this->codigoActivo = $this->sinCodigoActivo ? '' : $this->normalizarCodigoActivo($this->codigoActivo);
        $this->responsableCedula = $this->normalizarCedula($this->responsableCedula);

        $this->validate();

        try {
            return $this->guardarEquipoEnTransaccion();
        } catch (ValidationException $validationException) {
            // El modelo valida RN-03 con la clave de columna `codigo_activo`; la
            // remapeamos a la propiedad Livewire `codigoActivo` para que el error
            // se muestre junto al campo correcto (x-ui.input busca $errors por el
            // atributo `name`, que aquí es "codigoActivo").
            $errores = $validationException->errors();

            throw array_key_exists('codigo_activo', $errores)
                ? ValidationException::withMessages(['codigoActivo' => $errores['codigo_activo']])
                : $validationException;
        }
    }

    private function guardarEquipoEnTransaccion(): Equipo
    {
        return DB::transaction(function () {
            $justificacion = trim($this->codigoActivoJustificacion);

            $equipo = Equipo::create([
                'serial' => $this->sinSerial ? $this->serialProvisional() : $this->serial,
                'codigo_activo' => $this->codigoActivo !== '' ? $this->codigoActivo : null,
                // Solo se guarda si el código realmente se repite (RN-03).
                'codigo_activo_justificacion' => $this->equipoConMismoCodigo() && $justificacion !== '' ? $justificacion : null,
                'tipo_equipo_id' => $this->tipoEquipoId,
                'marca_id' => $this->marcaId,
                'modelo' => $this->modelo !== '' ? $this->modelo : null,
                'caracteristicas' => $this->caracteristicas(),
                'propiedad' => $this->propiedad,
                'propietario_tercero' => $this->propiedad === 'tercero' ? $this->propietarioTercero : null,
                'figura_tercero' => $this->propiedad === 'tercero' ? $this->figura : null,
                'estado_funcionamiento' => $this->estadoFuncionamiento,
                'observaciones' => trim($this->observaciones) !== '' ? trim($this->observaciones) : null,
                // RN: sin responsable asignado el equipo queda "sin_asignar" (bodega);
                // con responsable queda "en_servicio".
                'estado_ciclo_vida' => $this->asignarResponsable ? 'en_servicio' : 'sin_asignar',
                // RN-02: con serial provisional el equipo nace "pendiente de
                // verificar"; en el resto de los casos nace verificado (ver resumen).
                'verificacion' => $this->sinSerial ? 'pendiente_de_verificar' : 'verificado',
            ]);

            if ($this->familiaSeleccionada === 'computo') {
                $this->guardarConfiguracionComputo($equipo);
            }

            $alta = app(HistorialService::class)->registrar(
                equipo: $equipo,
                tipo: 'alta',
                usuario: auth()->user(),
                descripcion: 'Alta registrada desde el formulario de registro.',
            );

            $this->guardarUbicacionResponsable($equipo, $alta);

            return $equipo;
        });
    }

    /**
     * Características propias del tipo (RF-03) para la columna JSON
     * `equipos.caracteristicas`. Solo se guardan las de la familia elegida y
     * con valor; el cómputo no usa esta columna (va en configuración y
     * componentes). Devuelve null si no hay ninguna.
     *
     * @return array<string, string|int>|null
     */
    private function caracteristicas(): ?array
    {
        $porFamilia = [
            'video' => [
                'tamano_pulgadas' => $this->tamanoPulgadas,
                'conexion' => $this->conexionMonitor,
            ],
            'impresion' => [
                'funciones' => $this->funcionesImpresora,
                'tipo_impresion' => $this->tipoImpresion,
                'conexion' => $this->conexionImpresora,
            ],
            'digitalizacion' => [
                'tipo_escaner' => $this->tipoEscaner,
                'conexion' => $this->conexionEscaner,
            ],
            'energia' => [
                'tipo' => $this->tipoEnergia,
                'capacidad_va' => $this->capacidadVa,
                'numero_tomas' => $this->numTomas !== '' ? (int) $this->numTomas : '',
            ],
            'conectividad' => [
                'numero_puertos' => $this->numPuertos !== '' ? (int) $this->numPuertos : '',
                'administrable' => $this->administrable,
                'velocidad' => $this->velocidad,
            ],
            'proyeccion' => [
                'lumenes' => $this->lumenes,
                'resolucion' => $this->resolucion,
            ],
        ];

        $valores = array_filter(
            array_map(fn ($valor) => is_string($valor) ? trim($valor) : $valor, $porFamilia[$this->familiaSeleccionada] ?? []),
            fn ($valor) => $valor !== '' && $valor !== null,
        );

        return $valores === [] ? null : $valores;
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

    /**
     * Abre la primera asignación del equipo (responsable y ubicación), enlazada
     * al evento de alta que la originó.
     */
    private function guardarUbicacionResponsable(Equipo $equipo, Evento $alta): void
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
            'dependencia_id' => $this->dependenciaId,
            'fecha_inicio' => now(),
            'evento_origen_id' => $alta->id,
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
