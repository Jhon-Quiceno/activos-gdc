<?php

namespace App\Livewire\Importacion;

use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Importacion;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Services\HistorialService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads;

    public int $pasoActual = 1;

    public $archivo;

    public array $filas = [];

    public array $equivalencias = [];

    public array $advertencias = [];

    public array $kpis = [
        'filas_leidas' => 0,
        'equipos_detectados' => 0,
        'con_advertencias' => 0,
        'excluidos' => 0,
    ];

    public function updatedArchivo(): void
    {
        $this->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $this->leerArchivo();
    }

    public function leerArchivo(): void
    {
        $this->reset(['filas', 'equivalencias', 'advertencias', 'kpis']);

        $hoja = Excel::toArray(new InventarioExcel, $this->archivo->getRealPath())[0] ?? [];
        if ($hoja === []) {
            $this->addError('archivo', __('El archivo no contiene filas.'));
            return;
        }

        $encabezados = $this->encabezados($hoja[0]);
        foreach (array_slice($hoja, 1) as $indice => $fila) {
            $fila = $this->filaAsociativa($encabezados, $fila);
            if ($this->filaVacia($fila)) {
                continue;
            }

            $numero = $indice + 2;
            $equipos = $this->equiposDeFila($fila, $numero);
            $this->kpis['filas_leidas']++;
            $personal = $this->esPersonal($fila);
            if ($personal) {
                $this->kpis['excluidos']++;
                continue;
            }

            $this->kpis['equipos_detectados'] += count($equipos);
            $this->filas[] = ['numero' => $numero, 'datos' => $fila, 'equipos' => $equipos];
        }

        $this->equivalencias = $this->detectarEquivalencias();
        $this->kpis['con_advertencias'] = count($this->advertenciasPorFila());
        $this->pasoActual = 2;
    }

    public function confirmar(): void
    {
        $this->validate([
            'archivo' => ['required'],
            'equivalencias.*.sugerido' => ['nullable', 'string', 'max:255'],
        ]);

        $usuario = auth()->user();
        if (! $usuario) {
            abort(403);
        }

        // RN-03: un código de activo repetido entre dos equipos no-AllInOne necesita
        // justificación antes de guardarse. Por ahora bloqueamos la confirmación con
        // un error claro en vez de dejar que la restricción única de la base de datos
        // reviente a medio guardar (dejaría equipos huérfanos de esa fila).
        $repetidos = $this->codigosRepetidosEnLote();
        if ($repetidos !== []) {
            $this->addError('archivo', __('Hay códigos de activo repetidos en el archivo (:codigos). Corrígelos antes de confirmar.', ['codigos' => implode(', ', $repetidos)]));
            return;
        }

        try {
            DB::transaction(function () use ($usuario): void {
                $importacion = Importacion::create([
                'archivo' => $this->archivo->getClientOriginalName(),
                'fecha' => now(),
                'usuario_id' => $usuario->id,
                'filas' => $this->kpis['filas_leidas'],
                'errores' => $this->advertenciasPorFila(),
            ]);

            foreach ($this->filas as $fila) {
                $puesto = $this->resolverPuesto($fila['datos']);
                // Una sola persona por fila (no una por cada equipo que contenga):
                // un PC, su monitor y su impresora comparten el mismo responsable.
                $persona = $this->resolverPersona($puesto, $fila['datos']);
                foreach ($fila['equipos'] as $datos) {
                    try {
                        $equipo = Equipo::create([
                            'serial' => $datos['serial'] ?: $this->serialPendiente($fila['numero'], $datos['tipo']),
                            'codigo_activo' => $this->normalizarCodigo($datos['codigo']),
                            'tipo_equipo_id' => $this->tipoId($datos['tipo']),
                            'marca_id' => $this->marcaId($datos['marca']),
                            'modelo' => $datos['modelo'],
                            'propiedad' => $datos['tercero'] ? 'tercero' : 'gobernacion',
                            'propietario_tercero' => $datos['propietario'],
                            'estado_funcionamiento' => $datos['estado'],
                            'estado_ciclo_vida' => $puesto ? 'en_servicio' : 'sin_asignar',
                            'verificacion' => 'pendiente_de_verificar',
                            'puesto_trabajo_id' => $puesto?->id,
                            'importacion_id' => $importacion->id,
                            'fila_origen_importacion' => $fila['numero'],
                        ]);
                    } catch (ValidationException $validacionModelo) {
                        // RN-03: el código ya existe en un equipo fuera de este archivo
                        // (contra la base, no contra el lote). codigosRepetidosEnLote()
                        // de arriba solo mira duplicados DENTRO del archivo; este caso
                        // solo lo detecta el modelo al guardar. Lo convertimos en un
                        // error de fila legible en vez de dejarlo reventar sin contexto.
                        throw new \RuntimeException(__('Fila :fila: :mensaje', [
                            'fila' => $fila['numero'],
                            'mensaje' => $validacionModelo->errors()['codigo_activo'][0] ?? $validacionModelo->getMessage(),
                        ]), previous: $validacionModelo);
                    }

                    $alta = app(HistorialService::class)->registrar(
                        equipo: $equipo,
                        tipo: 'alta',
                        usuario: $usuario,
                        descripcion: 'Alta por importación del inventario 2026, fila '.$fila['numero'].'.',
                    );

                    $this->crearAsignacionInicial($equipo, $puesto, $persona, $alta);
                    $this->crearComponentes($equipo, $fila['datos']);
                    $this->crearConfiguracion($equipo, $fila['datos']);
                }
            }
            });
        } catch (\RuntimeException $errorDeFila) {
            // DB::transaction ya hizo rollback antes de relanzar la excepción
            // original; nada queda a medias.
            $this->addError('archivo', $errorDeFila->getMessage().' '.__('Corrígelo (agregando justificación en Equipos u otro código) antes de confirmar.'));

            return;
        }

        session()->flash('status', __('La importación fue confirmada correctamente.'));
        $this->reset(['archivo', 'filas', 'equivalencias', 'advertencias', 'kpis']);
        $this->pasoActual = 1;
    }

    public function advertenciasPorFila(): array
    {
        $conteoEnLote = $this->conteoCodigosEnLote();

        $advertencias = [];
        foreach ($this->filas as $fila) {
            foreach ($fila['equipos'] as $equipo) {
                if (! $equipo['codigo']) {
                    $advertencias[] = ['fila' => $fila['numero'], 'valor' => $equipo['tipo'], 'tipo' => 'sin_codigo', 'detalle' => __('Equipo sin código de activo.')];
                }
                if (! $equipo['serial']) {
                    $advertencias[] = ['fila' => $fila['numero'], 'valor' => $equipo['tipo'], 'tipo' => 'sin_serial', 'detalle' => __('Equipo sin serial; queda pendiente de verificar.')];
                }
                if ($equipo['codigo']) {
                    $codigo = $this->normalizarCodigo($equipo['codigo']);
                    // RN-03: un código repetido se advierte tanto si ya choca con algo
                    // persistido como si se repite DENTRO del mismo lote que se está
                    // previsualizando (todavía no hay nada guardado en ese segundo caso).
                    $repetidoEnLote = $codigo && ($conteoEnLote[$codigo] ?? 0) > 1;
                    $repetidoEnBase = $codigo && Equipo::where('codigo_activo', $codigo)->exists();
                    if ($repetidoEnLote || $repetidoEnBase) {
                        $advertencias[] = ['fila' => $fila['numero'], 'valor' => $codigo, 'tipo' => 'repetido', 'detalle' => __('Código de activo ya registrado.')];
                    }
                }
            }
        }

        return $advertencias;
    }

    /**
     * Cuenta cuántas veces aparece cada código de activo (ya normalizado) entre todos
     * los equipos detectados en el archivo actual, sin importar la fila.
     *
     * @return array<string, int>
     */
    private function conteoCodigosEnLote(): array
    {
        $conteo = [];
        foreach ($this->filas as $fila) {
            foreach ($fila['equipos'] as $equipo) {
                if ($equipo['codigo'] && $codigo = $this->normalizarCodigo($equipo['codigo'])) {
                    $conteo[$codigo] = ($conteo[$codigo] ?? 0) + 1;
                }
            }
        }

        return $conteo;
    }

    /**
     * Códigos de activo (normalizados) que se repiten dos o más veces dentro del
     * archivo que se está importando. No distingue All in One: esa excepción de
     * RN-03 todavía no está implementada (ver nota en la clase).
     *
     * @return array<int, string>
     */
    private function codigosRepetidosEnLote(): array
    {
        return array_keys(array_filter($this->conteoCodigosEnLote(), fn (int $veces) => $veces > 1));
    }

    public function irAPaso(int $paso): void
    {
        if ($paso > 1 && $this->archivo === null) {
            $this->addError('archivo', __('Debes cargar un archivo antes de continuar.'));
            return;
        }
        $this->pasoActual = max(1, min(4, $paso));
        $this->advertencias = $this->advertenciasPorFila();
    }

    public function volver(): void
    {
        $this->irAPaso($this->pasoActual - 1);
    }

    public function continuar(): void
    {
        if ($this->pasoActual === 3) {
            $this->advertencias = $this->advertenciasPorFila();
            $this->kpis['con_advertencias'] = count($this->advertencias);
        }
        $this->irAPaso($this->pasoActual + 1);
    }

    public function render()
    {
        $pasos = [1 => __('Subir archivo'), 2 => __('Equivalencias'), 3 => __('Vista previa'), 4 => __('Confirmar')];

        return view('livewire.importacion.index', [
            'pasos' => $pasos,
            'kpis' => $this->kpis,
            'advertencias' => $this->advertencias,
            'tiposAdvertencia' => [
                'sin_codigo' => ['variant' => 'warning', 'label' => __('Sin código')],
                'sin_serial' => ['variant' => 'warning', 'label' => __('Sin serial')],
                'repetido' => ['variant' => 'danger', 'label' => __('Repetido')],
            ],
            'dependencias' => Dependencia::orderBy('nombre')->pluck('nombre'),
        ]);
    }

    private function encabezados(array $fila): array
    {
        return array_map(fn ($valor, $indice) => $this->clave($valor) ?: 'columna_'.$indice, $fila, array_keys($fila));
    }

    private function filaAsociativa(array $encabezados, array $fila): array
    {
        $resultado = [];
        foreach ($encabezados as $indice => $encabezado) {
            $resultado[$encabezado] = $this->limpiar($fila[$indice] ?? null);
        }
        return $resultado;
    }

    /**
     * Categorías de dispositivo reconocidas por fila, con las palabras clave que
     * identifican su columna de presencia en el encabezado. Cubre tanto el
     * formato "corto" (fixtures de prueba: "PC", "Monitor"...) como el real del
     * inventario 2026 (exportación de Google Forms: "Dispositivo de
     * procesamiento", "Dispositivo de video"...), que no tiene ninguna columna
     * cuyo encabezado sea exactamente una de esas palabras.
     */
    private const CATEGORIAS = [
        'pc' => ['pc', 'computador', 'equipo principal', 'desktop', 'portatil', 'laptop', 'procesamiento'],
        'monitor' => ['monitor', 'pantalla', 'video'],
        'impresora' => ['impresora', 'printer', 'impresion'],
        'conectividad' => ['conectividad'],
        'escaner' => ['escaner', 'scanner', 'digitalizacion'],
        'otro' => ['otro', 'otros'],
        'ups' => ['ups'],
    ];

    private function equiposDeFila(array $fila, int $numero): array
    {
        $equipos = [];
        foreach (self::CATEGORIAS as $tipo => $palabrasClave) {
            $presencia = $this->valorCategoria($fila, $palabrasClave);
            $codigo = $this->valorModificador($fila, $palabrasClave, ['codigo', 'activo']);
            $serial = $this->valorModificador($fila, $palabrasClave, ['serial']);
            $marca = $this->valorModificador($fila, $palabrasClave, ['marca']);
            $modelo = $this->valorModificador($fila, $palabrasClave, ['modelo']);

            if ($tipo === 'pc') {
                // Antes esto forzaba "pc" aunque la fila no tuviera ningún
                // dato de procesamiento (461 filas reales, 49 sin ninguno):
                // inventaba un equipo de la nada. Ahora basta cualquier dato
                // propio de pc, sea la columna de presencia o un modificador.
                if ($presencia === null && $codigo === null && $serial === null && $marca === null && $modelo === null) {
                    continue;
                }
            } elseif ($presencia === null) {
                continue;
            }

            $equipos[] = [
                'tipo' => $tipo === 'pc' ? ($presencia ?: 'pc de escritorio') : ($presencia ?: $tipo),
                'codigo' => $codigo,
                'serial' => $serial,
                'marca' => $marca,
                'modelo' => $modelo,
                // La mayoría de los formularios reales no preguntan el estado por
                // dispositivo: hay una sola columna de estado para toda la fila.
                'estado' => $this->valorModificador($fila, $palabrasClave, ['estado'])
                    ?? $this->valorConTokens($fila, ['estado', 'funcionamiento']),
                'tercero' => $this->esTercero($fila),
                'propietario' => $this->valorConTokens($fila, ['propietario']),
            ];
        }
        return $equipos;
    }

    /**
     * Columna de presencia de una categoría: su encabezado contiene alguna de
     * las $palabrasClave pero ninguna palabra de modificador (código, marca,
     * modelo, serial...), para no confundirla con la columna de "Marca del
     * dispositivo de video" cuando se busca "Dispositivo de video".
     */
    private function valorCategoria(array $fila, array $palabrasClave): ?string
    {
        foreach ($fila as $clave => $valor) {
            if ($valor === null || ! $this->contieneAlguno($clave, $palabrasClave)) {
                continue;
            }
            if ($this->contieneAlguno($clave, ['codigo', 'activo', 'marca', 'modelo', 'serial', 'estado', 'sistema', 'version', 'antivirus'])) {
                continue;
            }
            return $valor;
        }
        return null;
    }

    /**
     * Columna de un dato propio de la categoría (código, marca, modelo o
     * serial): su encabezado tiene a la vez alguna palabra de la categoría y
     * alguna del modificador buscado. Limpia valores "basura" típicos de
     * formularios ("No se ve", "N/A") que de lo contrario quedarían guardados
     * como si fueran el dato real.
     */
    private function valorModificador(array $fila, array $palabrasClave, array $modificador): ?string
    {
        foreach ($fila as $clave => $valor) {
            if ($valor !== null && $this->contieneAlguno($clave, $palabrasClave) && $this->contieneAlguno($clave, $modificador)) {
                return $this->limpiarTextoLibre($valor);
            }
        }
        return null;
    }

    /**
     * Columna identificada únicamente por un conjunto de palabras que deben
     * estar TODAS presentes en el encabezado (p. ej. ["sistema", "operativo"]),
     * sin importar el resto de la pregunta ("Versión Sistema operativo
     * dispositivo de procesamiento (responda N/A si...)").
     */
    private function valorConTokens(array $fila, array $tokens, bool $limpiar = true): ?string
    {
        foreach ($fila as $clave => $valor) {
            if ($valor !== null && $this->contieneTodos($clave, $tokens)) {
                return $limpiar ? $this->limpiarTextoLibre($valor) : $valor;
            }
        }
        return null;
    }

    private function contieneTodos(string $clave, array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (! str_contains($clave, $token)) {
                return false;
            }
        }
        return true;
    }

    private function contieneAlguno(string $clave, array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (str_contains($clave, $token)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Respuestas tipo "no sé qué poner aquí" de formularios con preguntas que
     * no aplican a ese equipo (p. ej. "Serial del Teclado" cuando no se ve o
     * el equipo no es un PC). Se tratan como vacío, no como el dato real.
     */
    private function limpiarTextoLibre(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $limpio = trim($valor);
        $basura = ['N/A', 'NA', 'NO SE VE', 'NO LEGIBLE', 'SIN DATO', 'NO APLICA', '-'];
        return $limpio === '' || in_array(Str::upper($limpio), $basura, true) ? null : $limpio;
    }

    /**
     * Palabras que deben estar TODAS en el encabezado para ubicar cada campo
     * "de fila" (no por dispositivo): tolera que el formulario real pregunte
     * "Dependencia responsable del equipo" en vez de solo "Dependencia".
     */
    private const TOKENS_CAMPO = [
        'dependencia' => ['dependencia'],
        'sede' => ['sede'],
        'piso' => ['piso'],
        'marca' => ['marca'],
        'sistema operativo' => ['sistema', 'operativo'],
    ];

    private function detectarEquivalencias(): array
    {
        $resultado = [];
        foreach ($this->filas as $fila) {
            foreach (self::TOKENS_CAMPO as $campo => $tokens) {
                $valor = $this->valorConTokens($fila['datos'], $tokens);
                if ($valor && ! isset($resultado[$campo.'|'.$valor])) {
                    $resultado[$campo.'|'.$valor] = [
                        'campo' => $campo,
                        'origen' => $valor,
                        'sugerido' => $this->sugerencia($campo, $valor),
                    ];
                }
            }
        }
        return array_values($resultado);
    }

    private function sugerencia(string $campo, string $valor): string
    {
        $modelo = match ($campo) {
            'dependencia' => Dependencia::class,
            'sede' => Sede::class,
            'piso' => Piso::class,
            'marca' => Marca::class,
            default => SistemaOperativo::class,
        };
        $nombre = $modelo === Piso::class ? (string) ((int) preg_replace('/\D+/', '', $valor)) : $valor;
        $item = $modelo::query()->get()->sortBy(fn ($item) => levenshtein(Str::lower($nombre), Str::lower((string) ($item->nombre ?? $item->numero))))->first();
        return $item ? (string) ($item->nombre ?? $item->numero) : $nombre;
    }

    private function resolverPuesto(array $fila): ?PuestoTrabajo
    {
        $sede = $this->buscarCatalogo(Sede::class, $this->valorEquivalente('sede', $fila));
        $pisoValor = $this->valorEquivalente('piso', $fila);
        $piso = $pisoValor ? Piso::where('numero', (int) $pisoValor)->first() : null;
        $dependencia = $this->buscarCatalogo(Dependencia::class, $this->valorEquivalente('dependencia', $fila));
        if (! $sede || ! $piso) {
            return null;
        }
        return PuestoTrabajo::firstOrCreate([
            'nombre_o_codigo' => $this->valorPorAlias($fila, ['puesto', 'puesto de trabajo']) ?: 'IMPORT-'.$sede->id.'-'.$piso->id.'-'.($dependencia?->id ?? 0),
            'sede_id' => $sede->id,
            'piso_id' => $piso->id,
            'dependencia_id' => $dependencia?->id,
        ]);
    }

    /**
     * Responsable de la fila (nombre, cédula, vinculación), igual que hace el
     * registro manual (`Equipos\Crear::guardarUbicacionResponsable`). Sin
     * ubicación resuelta no hay dónde asignarlo (RF-19: la asignación siempre
     * lleva sede y piso), así que en ese caso no se crea nada.
     */
    private function resolverPersona(?PuestoTrabajo $puesto, array $fila): ?Persona
    {
        if (! $puesto) {
            return null;
        }

        $nombre = $this->valorConTokens($fila, ['nombre', 'responsable']);
        if (! $nombre) {
            return null;
        }

        $cedula = $this->valorConTokens($fila, ['cedula']);
        $cedula = $cedula ? preg_replace('/\D+/', '', $cedula) : null;
        $datosPersona = [
            'nombre' => $nombre,
            'dependencia_id' => $puesto->dependencia_id,
            'tipo_vinculacion' => $this->normalizarVinculacion($this->valorConTokens($fila, ['vinculacion'])),
        ];

        return $cedula !== null && $cedula !== ''
            ? Persona::firstOrCreate(['cedula' => $cedula], $datosPersona)
            : Persona::create($datosPersona);
    }

    private function crearAsignacionInicial(Equipo $equipo, ?PuestoTrabajo $puesto, ?Persona $persona, Evento $alta): void
    {
        if (! $puesto) {
            return;
        }

        Asignacion::create([
            'equipo_id' => $equipo->id,
            'persona_id' => $persona?->id,
            'sede_id' => $puesto->sede_id,
            'piso_id' => $puesto->piso_id,
            'dependencia_id' => $puesto->dependencia_id,
            'fecha_inicio' => now(),
            'evento_origen_id' => $alta->id,
        ]);
    }

    /**
     * `personas.tipo_vinculacion` es un enum estricto (planta|contratista); el
     * formulario real trae texto libre ("Funcionario de planta", "Contratista
     * OPS"...). Se detecta por palabra clave y, si no coincide con ninguna, se
     * deja null en vez de reventar el guardado con un valor de enum inválido.
     */
    private function normalizarVinculacion(?string $valor): ?string
    {
        if (! $valor) {
            return null;
        }
        $texto = Str::lower($valor);
        if (Str::contains($texto, 'contratista')) {
            return 'contratista';
        }
        if (Str::contains($texto, 'planta')) {
            return 'planta';
        }
        return null;
    }

    /**
     * Teclado, mouse y sonido. Dos formatos posibles de origen:
     * - Real (Google Forms): cuatro columnas separadas por periférico
     *   ("¿Equipo cuenta con Teclado?" SI/NO/N\A, estado, marca, serial). La
     *   presencia la decide la primera, no si el serial vino con algo escrito
     *   ("No se ve" en marca o serial no significa que no haya teclado).
     * - Simple (pruebas/ejemplos): una sola columna con el serial directo
     *   bajo el nombre del periférico ("Teclado" => "TEC-0001").
     */
    private function crearComponentes(Equipo $equipo, array $fila): void
    {
        // Teclado/mouse/sonido son del PC, no de cada equipo que haya en la
        // fila: sin este filtro, el monitor o la impresora de la misma fila
        // terminaban con su propio "mouse" pegado solo por compartir fila.
        if ($equipo->tipoEquipo?->familia !== 'computo') {
            return;
        }

        foreach (['teclado', 'mouse', 'sonido'] as $nombre) {
            // Sin limpiar: distingue "la columna no existe" (null) de "existe
            // pero dice N\/A o NO" (valor presente, igual de concluyente).
            $cuenta = $this->valorConTokens($fila, ['cuenta', $nombre], limpiar: false);
            $marca = $this->valorConTokens($fila, ['marca', $nombre]);
            $serial = $this->valorConTokens($fila, ['serial', $nombre]);

            if ($cuenta !== null) {
                if (Str::upper(trim($cuenta)) !== 'SI') {
                    continue;
                }
            } else {
                // Sin columna "cuenta con": el valor bajo el nombre simple del
                // periférico ES el serial (formato de las pruebas/ejemplos).
                $serial ??= $this->valorConTokens($fila, [$nombre]);
                if ($serial === null) {
                    continue;
                }
            }

            $tipo = TipoComponente::firstOrCreate(['nombre' => ucfirst($nombre)], ['es_periferico' => true]);
            Componente::create([
                'equipo_id' => $equipo->id,
                'tipo_componente_id' => $tipo->id,
                'marca' => $marca,
                'serial' => $serial,
            ]);
        }
    }

    private function crearConfiguracion(Equipo $equipo, array $fila): void
    {
        $sistema = $this->buscarCatalogo(SistemaOperativo::class, $this->valorEquivalente('sistema operativo', $fila));
        if ($sistema || $equipo->tipoEquipo?->familia === 'computo') {
            ConfiguracionComputo::create(['equipo_id' => $equipo->id, 'sistema_operativo_id' => $sistema?->id]);
        }
    }

    private function tipoId(string $valor): int
    {
        $tipo = $this->mejorCoincidencia(TipoEquipo::all(), $valor, fn ($item) => $item->nombre);
        return $tipo?->id ?? TipoEquipo::where('nombre', 'Otro')->value('id');
    }

    private function marcaId(?string $valor): int
    {
        return $this->buscarCatalogo(Marca::class, $valor)?->id ?? Marca::query()->value('id');
    }

    private function buscarCatalogo(string $modelo, ?string $valor): mixed
    {
        if (! $valor) {
            return null;
        }
        return $this->mejorCoincidencia($modelo::all(), $valor, fn ($item) => $item->nombre ?? $item->numero);
    }

    /**
     * Elige el elemento de $candidatos cuya etiqueta se parece más a $valor:
     * primero por palabras completas en común, para que una frase larga del
     * formulario ("Estabilizadores y UPS") no termine, por pura distancia de
     * edición, emparejada con un catálogo sin relación ("Servidor") en vez
     * del correcto ("UPS"). Si no comparten ninguna palabra completa, se
     * decide por distancia de edición (cubre variantes de ortografía como
     * "Scanner" vs "Escáner").
     */
    private function mejorCoincidencia(iterable $candidatos, string $valor, Closure $etiqueta): mixed
    {
        $palabrasValor = $this->palabras($valor);

        return collect($candidatos)->sortByDesc(function ($item) use ($valor, $palabrasValor, $etiqueta) {
            $texto = (string) $etiqueta($item);
            $comunes = count(array_intersect($palabrasValor, $this->palabras($texto)));

            return $comunes * 1000 - levenshtein(Str::lower($valor), Str::lower($texto));
        })->first();
    }

    /** @return array<int, string> */
    private function palabras(string $texto): array
    {
        return array_values(array_filter(explode(
            ' ',
            Str::of($texto)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString()
        )));
    }

    private function normalizarCodigo(?string $codigo): ?string
    {
        if (! $codigo || in_array(Str::upper(trim($codigo)), ['N/A', 'NA', 'SIN CODIGO', 'NO SE VE', 'NO LEGIBLE'], true)) {
            return null;
        }
        $codigo = Str::upper(preg_replace('/\s+/', '', trim($codigo)));
        $codigo = preg_replace('/^L1/', 'I1', $codigo);
        if (preg_match('/^I1-?(\d{5,6})$/', $codigo, $coincidencia)) {
            return 'I1-'.str_pad($coincidencia[1], 6, '0', STR_PAD_LEFT);
        }
        return $codigo;
    }

    private function serialPendiente(int $fila, string $tipo): string
    {
        return 'PENDIENTE-'.$fila.'-'.Str::upper(Str::slug($tipo, '-'));
    }

    private function valorPorAlias(array $fila, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $clave = $this->clave($alias);
            if (! empty($fila[$clave])) {
                return $fila[$clave];
            }
        }
        return null;
    }

    private function valorEquivalente(string $campo, array $fila): ?string
    {
        $valor = $this->valorConTokens($fila, self::TOKENS_CAMPO[$campo] ?? [$campo]);
        if (! $valor) {
            return null;
        }

        foreach ($this->equivalencias as $equivalencia) {
            if ($equivalencia['origen'] === $valor && ($equivalencia['campo'] ?? $campo) === $campo) {
                return $equivalencia['sugerido'] ?: $valor;
            }
        }

        return $valor;
    }

    private function clave(mixed $valor): string
    {
        return Str::of((string) $valor)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->replace(' ', ' ')->toString();
    }

    private function limpiar(mixed $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' || in_array(Str::upper($valor), ['N/A', 'NA', '-'], true) ? null : $valor;
    }

    private function filaVacia(array $fila): bool
    {
        return count(array_filter($fila, fn ($valor) => $valor !== null && $valor !== '')) === 0;
    }

    private function esPersonal(array $fila): bool
    {
        $texto = Str::lower(implode(' ', array_filter($fila)));
        return Str::contains($texto, ['personal', 'no es de la gobernacion', 'no es de la gobernación']);
    }

    private function esTercero(array $fila): bool
    {
        $texto = Str::lower(implode(' ', array_filter($fila)));
        return Str::contains($texto, ['tercero', 'externo', 'propio']);
    }
}
