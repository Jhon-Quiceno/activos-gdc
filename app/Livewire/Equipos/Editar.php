<?php

namespace App\Livewire\Equipos;

use App\Livewire\Equipos\Concerns\CaracteristicasPorFamilia;
use App\Livewire\Equipos\Concerns\CatalogoDelEquipo;
use App\Livewire\Equipos\Concerns\DatosDeAdquisicion;
use App\Livewire\Equipos\Concerns\NormalizaIdentificadores;
use App\Livewire\Equipos\Soporte\TextosEquipo;
use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use Illuminate\Validation\Rule;
use App\Models\Marca;
use App\Models\Proceso;
use App\Models\SistemaOperativo;
use App\Services\HistorialService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Edición de los datos de un equipo (plan, días 3–4; RF-01 a RF-04, RF-12).
 *
 * Cada guardado con cambios registra un evento «actualizacion_datos» con el
 * valor anterior y el nuevo de cada campo cambiado, en `eventos.valores`
 * (['antes' => [...], 'despues' => [...]]). Si no cambió nada, no hay evento.
 *
 * No se editan aquí, a propósito:
 * - el tipo de equipo (cambiaría toda la ficha y las características);
 * - el responsable y la ubicación: van por «Trasladar» (Movimientos, RF-20);
 * - los componentes: van por «Cambio de componente» (RN-07).
 *
 * Un equipo dado de baja no admite eventos nuevos (RN-10), así que no se edita.
 */
class Editar extends Component
{
    use CaracteristicasPorFamilia;
    use CatalogoDelEquipo;
    use DatosDeAdquisicion;
    use NormalizaIdentificadores;

    #[Locked]
    public Equipo $equipo;

    /** El tipo no se edita; se guarda para filtrar los modelos del catálogo. */
    #[Locked]
    public ?int $tipoEquipoId = null;

    #[Locked]
    public ?string $familiaSeleccionada = null;

    /** Ver Crear::$usaConfiguracionComputo. */
    #[Locked]
    public bool $usaConfiguracionComputo = false;

    // --- Identificación ---
    public string $serial = '';

    public string $codigoActivo = '';

    public bool $sinCodigoActivo = false;

    public string $codigoActivoJustificacion = '';

    public ?int $marcaId = null;

    // Modelo, descripción, proceso y propietario tercero: ver el trait CatalogoDelEquipo.

    public string $estadoFuncionamiento = '';

    // --- Propiedad ---
    public string $propiedad = 'gobernacion';

    public string $figura = '';

    // --- Software (solo cómputo) ---
    public ?int $sistemaOperativoId = null;

    public string $tieneAntivirus = 'no';

    public string $antivirusProducto = '';

    public string $nombreRed = '';

    public string $observaciones = '';

    /** Nombre visible de cada dato que se compara para el historial. */
    public const ETIQUETAS = [
        'serial' => 'Serial',
        'codigo_activo' => 'Código de activo',
        'codigo_activo_justificacion' => 'Justificación del código de activo',
        'marca' => 'Marca',
        'modelo' => 'Modelo',
        'descripcion' => 'Descripción',
        'proceso' => 'Proceso',
        'estado_funcionamiento' => 'Estado de funcionamiento',
        'propiedad' => 'Propiedad',
        // La clave se conserva para que el historial anterior siga mostrándose igual.
        'propietario_tercero' => 'Propietario',
        'figura_tercero' => 'Figura del tercero',
        'caracteristicas' => 'Características',
        'observaciones' => 'Observaciones',
        'sistema_operativo' => 'Sistema operativo',
        'antivirus' => 'Antivirus',
        'nombre_red' => 'Nombre de red',
        'ofimatica' => 'Ofimática',
        'licencia_ofimatica' => 'Licencia de ofimática',
        'dominio' => 'Dominio',
        'direccion_ip' => 'Dirección IP',
        'direccion_mac' => 'Dirección MAC',
        'forma_ingreso' => 'Forma de ingreso',
        'fecha_adquisicion' => 'Fecha de adquisición',
        'valor_adquisicion' => 'Valor de adquisición',
        'proveedor' => 'Proveedor',
        'numero_contrato' => 'N.º de contrato',
        'numero_factura' => 'N.º de factura',
        'fecha_fin_garantia' => 'Fin de la garantía',
        'vida_util_meses' => 'Vida útil',
    ];

    private const PROPIEDAD_LABELS = ['gobernacion' => 'Gobernación', 'tercero' => 'Tercero'];

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load(['tipoEquipo', 'configuracionComputo']);
        $this->tipoEquipoId = $equipo->tipo_equipo_id;
        $this->familiaSeleccionada = $equipo->tipoEquipo?->familia;
        $this->usaConfiguracionComputo = (bool) $equipo->tipoEquipo?->usa_configuracion_computo;

        $this->serial = $equipo->serial;
        $this->codigoActivo = (string) $equipo->codigo_activo;
        $this->sinCodigoActivo = $equipo->codigo_activo === null;
        $this->codigoActivoJustificacion = (string) $equipo->codigo_activo_justificacion;
        $this->marcaId = $equipo->marca_id;
        $this->modeloId = $equipo->modelo_id;
        $this->descripcion = (string) $equipo->descripcion;
        $this->procesoId = $equipo->proceso_id;
        $this->estadoFuncionamiento = (string) $equipo->estado_funcionamiento;
        $this->propiedad = $equipo->propiedad;
        $this->proveedorTerceroId = $equipo->proveedor_tercero_id;
        $this->figura = (string) $equipo->figura_tercero;
        $this->observaciones = (string) $equipo->observaciones;
        $this->llenarCaracteristicas($equipo->caracteristicas);
        $this->llenarAdquisicion($equipo);

        if ($configuracion = $equipo->configuracionComputo) {
            $this->sistemaOperativoId = $configuracion->sistema_operativo_id;
            $this->tieneAntivirus = $configuracion->tiene_antivirus ? 'si' : 'no';
            $this->antivirusProducto = (string) $configuracion->antivirus_producto;
            $this->nombreRed = (string) $configuracion->nombre_red;
        }
    }

    public function dadoDeBaja(): bool
    {
        return Equipo::query()->whereKey($this->equipo->id)->value('estado_ciclo_vida') === 'dado_de_baja';
    }

    protected function rules(): array
    {
        $rules = [
            // RN-02: único en todo el inventario (sin contar este mismo equipo).
            'serial' => ['required', 'string', 'max:255', $this->noUsadoPorOtroEquipo('serial')],
            // RN-03: obligatorio salvo que el equipo no tenga código; si se repite, con justificación.
            'codigoActivo' => [
                $this->sinCodigoActivo ? 'nullable' : 'required',
                'string',
                'max:255',
                'regex:'.self::PATRON_CODIGO_ACTIVO,
            ],
            'codigoActivoJustificacion' => [
                $this->equipoConMismoCodigo() ? 'required' : 'nullable',
                'string',
                'min:10',
                'max:1000',
            ],
            'marcaId' => ['required', 'exists:marcas,id'],
            'estadoFuncionamiento' => ['required', Rule::in(array_keys(Equipo::ESTADOS_FUNCIONAMIENTO))],
            'propiedad' => ['required', 'in:gobernacion,tercero'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ] + $this->reglasCatalogo() + $this->reglasAdquisicion() + $this->reglasCaracteristicas();

        if ($this->usaConfiguracionComputo) {
            $rules['sistemaOperativoId'] = ['nullable', 'exists:sistemas_operativos,id'];
            $rules['antivirusProducto'] = ['nullable', 'string', 'max:255'];
            $rules['nombreRed'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'codigoActivo.required' => __('Escribe el código de activo o marca «El equipo no tiene código de activo».'),
            'codigoActivo.regex' => __('El código de activo debe tener el formato I1-###### (por ejemplo, I1-024147).'),
            'codigoActivoJustificacion.required' => __('Este código de activo ya está en otro equipo: escribe por qué se repite (RN-03).'),
        ] + $this->mensajesCatalogo() + $this->mensajesAdquisicion();
    }

    protected function validationAttributes(): array
    {
        return [
            'serial' => __('serial'),
            'codigoActivo' => __('código de activo'),
            'codigoActivoJustificacion' => __('justificación'),
            'marcaId' => __('marca'),
            'estadoFuncionamiento' => __('estado de funcionamiento'),
            'numTomas' => __('número de tomas'),
            'numPuertos' => __('número de puertos'),
            'observaciones' => __('observaciones'),
        ] + $this->atributosCatalogo() + $this->atributosAdquisicion();
    }

    /**
     * Otro equipo (no este) que ya tiene este código de activo, si existe.
     */
    public function equipoConMismoCodigo(): ?Equipo
    {
        if ($this->sinCodigoActivo || $this->codigoActivo === '') {
            return null;
        }

        return Equipo::with('tipoEquipo')
            ->where('codigo_activo', $this->codigoActivo)
            ->whereKeyNot($this->equipo->id)
            ->first();
    }

    private function noUsadoPorOtroEquipo(string $columna): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($columna): void {
            $otro = Equipo::with('tipoEquipo')
                ->where($columna, $value)
                ->whereKeyNot($this->equipo->id)
                ->first();

            if ($otro) {
                $fail(__('Ya está registrado en otro equipo: :tipo con serial :serial.', [
                    'tipo' => $otro->tipoEquipo?->nombre ?? __('equipo'),
                    'serial' => $otro->serial,
                ]));
            }
        };
    }

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

    public function guardar(): void
    {
        if ($this->dadoDeBaja()) {
            $this->addError('equipo', __('El equipo está dado de baja: no admite cambios. Si la baja fue un error, anúlala primero.'));

            return;
        }

        $this->serial = $this->normalizarSerial($this->serial);
        $this->codigoActivo = $this->sinCodigoActivo ? '' : $this->normalizarCodigoActivo($this->codigoActivo);
        $this->normalizarAdquisicion();

        $this->validate();

        $equipo = $this->equipo->fresh(['marca', 'modeloEquipo', 'proceso', 'proveedorTercero', 'asignacionActual', 'configuracionComputo.sistemaOperativo']);
        $antes = $this->valoresDe($equipo);
        $despues = $this->valoresDelFormulario();

        $cambiados = array_keys(array_filter($despues, fn ($valor, $campo) => $valor !== $antes[$campo], ARRAY_FILTER_USE_BOTH));

        if ($cambiados === []) {
            session()->flash('status', __('No hubo cambios que guardar.'));
            $this->redirect(route('equipos.show', $equipo), navigate: true);

            return;
        }

        try {
            DB::transaction(function () use ($equipo, $antes, $despues, $cambiados) {
                $justificacion = trim($this->codigoActivoJustificacion);

                $equipo->update([
                    'serial' => $this->serial,
                    'codigo_activo' => $this->codigoActivo !== '' ? $this->codigoActivo : null,
                    'codigo_activo_justificacion' => $this->equipoConMismoCodigo() && $justificacion !== '' ? $justificacion : null,
                    'marca_id' => $this->marcaId,
                    'modelo_id' => $this->resolverModeloId(),
                    'descripcion' => trim($this->descripcion) !== '' ? trim($this->descripcion) : null,
                    'proceso_id' => $this->procesoParaGuardar($equipo->asignacionActual?->dependencia_id),
                    'estado_funcionamiento' => $this->estadoFuncionamiento,
                    'propiedad' => $this->propiedad,
                    'proveedor_tercero_id' => $this->resolverProveedorTerceroId(),
                    'figura_tercero' => $this->propiedad === 'tercero' ? $this->figura : null,
                    'caracteristicas' => $this->caracteristicas(),
                    'observaciones' => trim($this->observaciones) !== '' ? trim($this->observaciones) : null,
                ] + $this->columnasAdquisicion());

                if ($this->usaConfiguracionComputo) {
                    ConfiguracionComputo::updateOrCreate(['equipo_id' => $equipo->id], [
                        'sistema_operativo_id' => $this->sistemaOperativoId,
                        'tiene_antivirus' => $this->tieneAntivirus === 'si',
                        'antivirus_producto' => $this->tieneAntivirus === 'si' && trim($this->antivirusProducto) !== '' ? trim($this->antivirusProducto) : null,
                        'nombre_red' => trim($this->nombreRed) !== '' ? trim($this->nombreRed) : null,
                    ] + $this->columnasRedYOfimatica());
                }

                app(HistorialService::class)->registrar(
                    equipo: $equipo,
                    tipo: 'actualizacion_datos',
                    usuario: auth()->user(),
                    datos: ['valores' => [
                        'antes' => array_intersect_key($antes, array_flip($cambiados)),
                        'despues' => array_intersect_key($despues, array_flip($cambiados)),
                    ]],
                    descripcion: __('Actualización de datos: :campos.', [
                        'campos' => collect($cambiados)->map(fn ($campo) => __(self::ETIQUETAS[$campo]))->implode(', '),
                    ]),
                );
            });
        } catch (ValidationException $e) {
            // El modelo valida RN-03 con la clave de columna; se muestra en el campo del formulario.
            $errores = $e->errors();

            throw array_key_exists('codigo_activo', $errores)
                ? ValidationException::withMessages(['codigoActivo' => $errores['codigo_activo']])
                : $e;
        }

        session()->flash('status', __('Datos del equipo actualizados.'));
        $this->redirect(route('equipos.show', $equipo), navigate: true);
    }

    /**
     * Valores legibles actuales del equipo, con las mismas claves que
     * valoresDelFormulario(), para comparar y guardar el antes/después.
     *
     * @return array<string, string|null>
     */
    private function valoresDe(Equipo $equipo): array
    {
        $configuracion = $equipo->configuracionComputo;

        return $this->sinVacios([
            'serial' => $equipo->serial,
            'codigo_activo' => $equipo->codigo_activo,
            'codigo_activo_justificacion' => $equipo->codigo_activo_justificacion,
            'marca' => $equipo->marca?->nombre,
            'modelo' => $equipo->modeloEquipo?->nombre,
            'descripcion' => $equipo->descripcion,
            'proceso' => $equipo->proceso?->nombre,
            'estado_funcionamiento' => $equipo->estado_funcionamiento,
            'propiedad' => self::PROPIEDAD_LABELS[$equipo->propiedad] ?? $equipo->propiedad,
            'propietario_tercero' => $equipo->proveedorTercero?->razon_social,
            'figura_tercero' => $equipo->figura_tercero ? (TextosEquipo::FIGURAS_TERCERO[$equipo->figura_tercero] ?? $equipo->figura_tercero) : null,
            'caracteristicas' => self::textoCaracteristicas($equipo->caracteristicas),
            'observaciones' => $equipo->observaciones,
            'sistema_operativo' => $this->usaConfiguracionComputo ? $configuracion?->sistemaOperativo?->nombre : null,
            // Sin configuración de cómputo guardada, el equipo no tiene antivirus
            // registrado: equivale a «No» (así no aparece como un cambio falso).
            'antivirus' => $this->usaConfiguracionComputo
                ? $this->textoAntivirus((bool) $configuracion?->tiene_antivirus, $configuracion?->antivirus_producto)
                : null,
            'nombre_red' => $this->usaConfiguracionComputo ? $configuracion?->nombre_red : null,
            'ofimatica' => $this->usaConfiguracionComputo ? $configuracion?->ofimatica : null,
            'licencia_ofimatica' => $this->usaConfiguracionComputo ? $configuracion?->licencia_ofimatica : null,
            'dominio' => $this->usaConfiguracionComputo ? $configuracion?->dominio : null,
            'direccion_ip' => $this->usaConfiguracionComputo ? $configuracion?->direccion_ip : null,
            'direccion_mac' => $this->usaConfiguracionComputo ? $configuracion?->direccion_mac : null,
        ] + $this->textosAdquisicion(
            $equipo->forma_ingreso,
            $equipo->fecha_adquisicion?->toDateString(),
            $equipo->valor_adquisicion,
            $equipo->proveedor_id,
            $equipo->numero_contrato,
            $equipo->numero_factura,
            $equipo->fecha_fin_garantia?->toDateString(),
            $equipo->vida_util_meses !== null ? (string) $equipo->vida_util_meses : null,
        ));
    }

    /**
     * @return array<string, string|null>
     */
    private function valoresDelFormulario(): array
    {
        $tercero = $this->propiedad === 'tercero';
        $computo = $this->usaConfiguracionComputo;
        $justificacion = trim($this->codigoActivoJustificacion);
        $dependenciaId = $this->equipo->asignacionActual()->value('dependencia_id');

        return $this->sinVacios([
            'serial' => $this->serial,
            'codigo_activo' => $this->codigoActivo,
            'codigo_activo_justificacion' => $this->equipoConMismoCodigo() ? $justificacion : null,
            'marca' => Marca::find($this->marcaId)?->nombre,
            'modelo' => $this->nombreModeloDelFormulario(),
            'descripcion' => trim($this->descripcion),
            'proceso' => Proceso::query()->whereKey($this->procesoParaGuardar($dependenciaId))->value('nombre'),
            'estado_funcionamiento' => $this->estadoFuncionamiento,
            'propiedad' => self::PROPIEDAD_LABELS[$this->propiedad] ?? $this->propiedad,
            'propietario_tercero' => $this->nombreProveedorDelFormulario(),
            'figura_tercero' => $tercero ? (TextosEquipo::FIGURAS_TERCERO[$this->figura] ?? $this->figura) : null,
            'caracteristicas' => self::textoCaracteristicas($this->caracteristicas()),
            'observaciones' => trim($this->observaciones),
            'sistema_operativo' => $computo ? SistemaOperativo::find($this->sistemaOperativoId)?->nombre : null,
            'antivirus' => $computo ? $this->textoAntivirus($this->tieneAntivirus === 'si', trim($this->antivirusProducto)) : null,
            'nombre_red' => $computo ? trim($this->nombreRed) : null,
            'ofimatica' => $computo ? trim($this->ofimatica) : null,
            'licencia_ofimatica' => $computo ? trim($this->licenciaOfimatica) : null,
            'dominio' => $computo ? trim($this->dominio) : null,
            'direccion_ip' => $computo ? trim($this->direccionIp) : null,
            'direccion_mac' => $computo ? trim($this->direccionMac) : null,
        ] + $this->textosAdquisicion(
            $this->formaIngreso,
            $this->fechaAdquisicion,
            $this->valorAdquisicion,
            $this->proveedorId,
            trim($this->numeroContrato),
            trim($this->numeroFactura),
            $this->fechaFinGarantia,
            $this->vidaUtilMeses,
        ));
    }

    private function textoAntivirus(bool $tiene, ?string $producto): string
    {
        if (! $tiene) {
            return __('No');
        }

        return filled($producto) ? __('Sí (:producto)', ['producto' => $producto]) : __('Sí');
    }

    /**
     * Normaliza vacíos a null para que '' y null no cuenten como un cambio.
     *
     * @param  array<string, mixed>  $valores
     * @return array<string, string|null>
     */
    private function sinVacios(array $valores): array
    {
        return array_map(fn ($valor) => $valor === null || trim((string) $valor) === '' ? null : (string) $valor, $valores);
    }

    public function render()
    {
        return view('livewire.equipos.page-editar', [
            'marcas' => Marca::orderBy('nombre')->get(),
            'sistemasOperativos' => SistemaOperativo::orderBy('nombre')->get(),
            'bloqueado' => $this->dadoDeBaja(),
            'formasIngreso' => Equipo::FORMAS_INGRESO,
            'vidaUtilDelTipo' => $this->equipo->tipoEquipo?->vida_util_meses,
        ] + $this->datosCatalogo());
    }
}
