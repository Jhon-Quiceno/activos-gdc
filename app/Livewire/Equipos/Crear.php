<?php

namespace App\Livewire\Equipos;

use App\Livewire\Equipos\Concerns\CaracteristicasPorFamilia;
use App\Livewire\Equipos\Concerns\CatalogoDelEquipo;
use App\Livewire\Equipos\Concerns\DatosDeAdquisicion;
use App\Livewire\Equipos\Concerns\NormalizaIdentificadores;
use App\Models\Asignacion;
use App\Models\Cargo;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Services\HistorialService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
    use CaracteristicasPorFamilia;
    use CatalogoDelEquipo;
    use DatosDeAdquisicion;
    use NormalizaIdentificadores;

    // --- 1. Tipo de equipo ---
    public ?int $tipoEquipoId = null;

    public ?string $familiaSeleccionada = null;

    /**
     * Muestra sistema operativo, antivirus, red y componentes internos
     * (`tipos_equipo.usa_configuracion_computo`). No basta la familia: un NAS
     * es de la familia cómputo pero no lleva esa configuración.
     */
    public bool $usaConfiguracionComputo = false;

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

    // Modelo, descripción y proceso: ver el trait CatalogoDelEquipo.

    public string $estadoFuncionamiento = '';

    // --- 3. Propiedad ---
    public string $propiedad = 'gobernacion';

    // Propietario tercero (proveedor): ver el trait CatalogoDelEquipo.

    /**
     * Figura jurídica del tercero (RF-04): comodato, convenio, proveedor o
     * arrendamiento. Se guarda en `equipos.figura_tercero`.
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

    // --- 4. Demás familias: ver el trait CaracteristicasPorFamilia ---

    // --- 5. Responsable y ubicación ---
    public bool $asignarResponsable = false;

    // RF-18: datos de la persona con los campos v4 (nombres y apellidos aparte,
    // tipo y número de documento, cargo del catálogo oficial).
    public string $responsableNombres = '';

    public string $responsableApellidos = '';

    public string $responsableTipoDocumento = 'CC';

    public string $responsableDocumento = '';

    public ?int $responsableCargoId = null;

    public bool $responsableEncargo = false;

    public string $vinculacion = '';

    public ?int $dependenciaId = null;

    public ?int $sedeId = null;

    public ?int $pisoId = null;

    public string $observaciones = '';

    /**
     * Cuántas veces se limpió el formulario («Guardar y registrar otro»). La vista
     * lo usa como clave del selector de ubicación, que guarda su estado en el
     * navegador (Alpine) y así vuelve a empezar vacío.
     */
    public int $limpiezas = 0;

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
        'seguridad_electronica' => 'Seguridad electrónica',
        'climatizacion' => 'Climatización',
        'telefonia' => 'Telefonía',
    ];

    public function seleccionarTipo(int $tipoEquipoId): void
    {
        $tipo = TipoEquipo::find($tipoEquipoId);

        // Si cambia de familia, limpiamos los campos específicos de la sección 4
        // anterior para no arrastrar datos que ya no aplican.
        if ($tipo?->familia !== $this->familiaSeleccionada || (bool) $tipo?->usa_configuracion_computo !== $this->usaConfiguracionComputo) {
            $this->resetCamposFamilia();
        }

        // Los modelos del catálogo son por marca y tipo: al cambiar el tipo, el elegido ya no aplica.
        if ($tipoEquipoId !== $this->tipoEquipoId) {
            $this->modeloId = null;
        }

        $this->tipoEquipoId = $tipoEquipoId;
        $this->familiaSeleccionada = $tipo?->familia;
        $this->usaConfiguracionComputo = (bool) $tipo?->usa_configuracion_computo;
    }

    private function resetCamposFamilia(): void
    {
        $this->reset([
            'procesador', 'memoriaRam', 'tipoDisco', 'capacidadDisco', 'sistemaOperativoId',
            'tieneAntivirus', 'antivirusProducto', 'nombreRed',
            'ofimatica', 'licenciaOfimatica', 'dominio', 'direccionIp', 'direccionMac',
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
            'estadoFuncionamiento' => ['required', Rule::in(array_keys(Equipo::ESTADOS_FUNCIONAMIENTO))],
            'propiedad' => ['required', 'in:gobernacion,tercero'],
            // RF-19 / RN-21: la ubicación siempre lleva sede, un piso de esa sede
            // y dependencia, tenga o no responsable.
            'sedeId' => ['required', 'exists:sedes,id'],
            'pisoId' => ['required', Rule::exists('pisos', 'id')->where('sede_id', $this->sedeId)],
            'dependenciaId' => ['required', 'exists:dependencias,id'],
        ];

        // Modelo, descripción, proceso y propietario tercero (RF-04, RN-16).
        $rules += $this->reglasCatalogo();

        // Adquisición (RF-52) y, en cómputo, ofimática y red.
        $rules += $this->reglasAdquisicion();

        // Características por tipo (RF-03): números enteros donde aplica.
        $rules += $this->reglasCaracteristicas();
        $rules['observaciones'] = ['nullable', 'string', 'max:2000'];

        if ($this->asignarResponsable) {
            $rules['responsableNombres'] = ['required', 'string', 'max:80'];
            $rules['responsableApellidos'] = ['nullable', 'string', 'max:80'];
            $rules['responsableTipoDocumento'] = ['required', Rule::in(array_keys(Persona::TIPOS_DOCUMENTO))];
            // La cédula solo lleva dígitos; pasaporte y demás pueden traer letras.
            $rules['responsableDocumento'] = [
                'nullable',
                'max:20',
                $this->responsableTipoDocumento === 'CC' ? 'regex:/^\d+$/' : 'regex:/^[A-Z0-9]+$/',
            ];
            $rules['responsableCargoId'] = ['nullable', 'exists:cargos,id'];
            $rules['vinculacion'] = ['required', Rule::in(array_keys(Persona::VINCULACIONES))];
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
            'responsableDocumento.regex' => $this->responsableTipoDocumento === 'CC'
                ? __('La cédula solo puede tener números.')
                : __('El documento solo puede tener letras y números.'),
            'pisoId.exists' => __('Elige un piso de la sede seleccionada.'),
        ] + $this->mensajesCatalogo() + $this->mensajesAdquisicion();
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
            'numTomas' => __('número de tomas'),
            'numPuertos' => __('número de puertos'),
            'observaciones' => __('observaciones'),
            'responsableNombres' => __('nombres'),
            'responsableApellidos' => __('apellidos'),
            'responsableTipoDocumento' => __('tipo de documento'),
            'responsableDocumento' => __('número de documento'),
            'responsableCargoId' => __('cargo'),
            'vinculacion' => __('vinculación'),
            'dependenciaId' => __('dependencia'),
            'sedeId' => __('sede'),
            'pisoId' => __('piso'),
        ] + $this->atributosCatalogo() + $this->atributosAdquisicion();
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

    public function updatedResponsableDocumento(): void
    {
        $this->responsableDocumento = $this->normalizarDocumento($this->responsableDocumento);

        if ($this->responsableDocumento !== '') {
            $this->validateOnly('responsableDocumento');
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

        $limpiezas = $this->limpiezas;
        $this->reset();
        $this->limpiezas = $limpiezas + 1;

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
        $this->responsableDocumento = $this->normalizarDocumento($this->responsableDocumento);
        $this->normalizarAdquisicion();

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
                'modelo_id' => $this->resolverModeloId(),
                'descripcion' => trim($this->descripcion) !== '' ? trim($this->descripcion) : null,
                'proceso_id' => $this->procesoParaGuardar($this->dependenciaId),
                'caracteristicas' => $this->caracteristicas(),
                'propiedad' => $this->propiedad,
                'proveedor_tercero_id' => $this->resolverProveedorTerceroId(),
                'figura_tercero' => $this->propiedad === 'tercero' ? $this->figura : null,
                'estado_funcionamiento' => $this->estadoFuncionamiento,
                'observaciones' => trim($this->observaciones) !== '' ? trim($this->observaciones) : null,
                // RN: sin responsable asignado el equipo queda "sin_asignar" (bodega);
                // con responsable queda "en_servicio".
                'estado_ciclo_vida' => $this->asignarResponsable ? 'en_servicio' : 'sin_asignar',
                // RN-02: con serial provisional el equipo nace "pendiente de
                // verificar"; en el resto de los casos nace verificado (ver resumen).
                'verificacion' => $this->sinSerial ? 'pendiente_de_verificar' : 'verificado',
            ] + $this->columnasAdquisicion());

            if ($this->usaConfiguracionComputo) {
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
        ] + $this->columnasRedYOfimatica());

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
                'nombres' => trim($this->responsableNombres),
                'apellidos' => trim($this->responsableApellidos) !== '' ? trim($this->responsableApellidos) : null,
                'tipo_documento' => $this->responsableTipoDocumento,
                'cargo_id' => $this->responsableCargoId,
                'encargo' => $this->responsableCargoId !== null && $this->responsableEncargo,
                'dependencia_id' => $this->dependenciaId,
                'tipo_vinculacion' => $this->vinculacion !== '' ? $this->vinculacion : null,
            ];

            // RN: si ya existe una persona con ese documento, se reutiliza en vez de
            // duplicarla (el número es único); si no, se crea una nueva.
            $persona = $this->responsableDocumento !== ''
                ? Persona::firstOrCreate(['numero_documento' => $this->responsableDocumento], $datosPersona)
                : Persona::create($datosPersona);
        }

        // RN-21: la sede la define el piso (el modelo Asignacion la copia del piso).
        Asignacion::create([
            'equipo_id' => $equipo->id,
            'persona_id' => $persona?->id,
            'piso_id' => $this->pisoId,
            'dependencia_id' => $this->dependenciaId,
            'fecha_inicio' => now(),
            'evento_origen_id' => $alta->id,
        ]);
    }

    public function render()
    {
        $tiposPorFamilia = TipoEquipo::where('activo', true)->orderBy('nombre')->get()->groupBy('familia');

        $tipoSeleccionado = $this->tipoEquipoId
            ? $tiposPorFamilia->flatten()->firstWhere('id', $this->tipoEquipoId)
            : null;

        $resumen = [
            __('Tipo') => $tipoSeleccionado?->nombre ?? '—',
            __('Familia') => $this->familiaSeleccionada
                ? (self::FAMILIA_LABELS[$this->familiaSeleccionada] ?? $this->familiaSeleccionada)
                : '—',
            __('Modelo') => $this->nombreModeloDelFormulario() ?? '—',
            // RN-27: el consecutivo lo asigna el sistema; no se escribe.
            __('N.º de activo') => __('Se asigna al guardar'),
            __('Propiedad') => $this->propiedad === 'tercero' ? __('Tercero') : __('Gobernación'),
            __('Estado inicial') => $this->asignarResponsable ? __('En servicio') : __('Sin asignar'),
        ];

        return view('livewire.equipos.page-crear', [
            'familiaLabels' => self::FAMILIA_LABELS,
            'tiposPorFamilia' => $tiposPorFamilia,
            'marcas' => Marca::orderBy('nombre')->get(),
            'sistemasOperativos' => SistemaOperativo::orderBy('nombre')->get(),
            'cargos' => Cargo::orderBy('codigo')->orderBy('denominacion')->get(),
            'tiposDocumento' => Persona::TIPOS_DOCUMENTO,
            'vinculaciones' => Persona::VINCULACIONES,
            'formasIngreso' => Equipo::FORMAS_INGRESO,
            'vidaUtilDelTipo' => $tipoSeleccionado?->vida_util_meses,
            'resumen' => $resumen,
        ] + $this->datosCatalogo());
    }
}
