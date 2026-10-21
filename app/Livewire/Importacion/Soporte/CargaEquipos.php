<?php

namespace App\Livewire\Importacion\Soporte;

use App\Models\Asignacion;
use App\Models\Cargo;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\DependenciaEquivalencia;
use App\Models\Equipo;
use App\Models\Importacion;
use App\Models\Marca;
use App\Models\ModeloEquipo;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Carga de equipos desde la plantilla oficial (RF-38 a RF-41).
 *
 * analizar() revisa cada fila contra los catálogos sin guardar nada:
 * - error: la fila no se carga (tipo, marca, sede o dependencia que no
 *   existen, piso que no es de la sede, serial repetido, código inválido…);
 * - advertencia: se carga, pero queda «Pendiente de verificar» (RF-41).
 *
 * guardar() crea los equipos sin error con su alta histórica (es_historico,
 * exenta de RN-13), su asignación y su configuración de cómputo.
 */
final class CargaEquipos
{
    /**
     * Largo máximo de las columnas de texto que van a la base (el de su
     * columna en la migración). Más largo es error de la fila, no de la carga.
     */
    private const LARGOS = [
        'marca' => 255,
        'modelo' => 100,
        'serial' => 255,
        'propietario_tercero' => 160,
        'proveedor' => 160,
        'numero_contrato' => 40,
        'numero_factura' => 40,
        'responsable_nombres' => 80,
        'responsable_apellidos' => 80,
        'procesador' => 255,
        'memoria_ram' => 255,
        'disco' => 255,
        'antivirus' => 255,
        'nombre_red' => 255,
        'ofimatica' => 80,
        'dominio' => 60,
    ];

    /** @var array<string, Collection<string, mixed>> */
    private array $catalogos = [];

    /** Nombre del archivo que se analiza, para reconocer las filas que ya se cargaron de él. */
    private ?string $archivo = null;

    /**
     * @param  array<int, array{numero: int, datos: array<string, ?string>}>  $filas
     * @return array<int, array<string, mixed>>
     */
    public function analizar(array $filas, ?string $archivo = null): array
    {
        $this->cargarCatalogos();
        $this->archivo = $archivo;

        // Para detectar repetidos dentro del mismo archivo.
        $seriales = [];
        $codigos = [];
        foreach ($filas as $fila) {
            try {
                if ($s = Normalizar::serial($fila['datos']['serial'] ?? null)) {
                    $seriales[$s] = ($seriales[$s] ?? 0) + 1;
                }
                if ($c = Normalizar::codigoActivo($fila['datos']['codigo_activo'] ?? null)) {
                    $codigos[$c] = ($codigos[$c] ?? 0) + 1;
                }
            } catch (InvalidArgumentException) {
                // El error se reporta en la fila.
            }
        }

        return array_map(fn (array $fila) => $this->analizarFila($fila['numero'], $fila['datos'], $seriales, $codigos), $filas);
    }

    /**
     * @param  array<int, array<string, mixed>>  $resultados
     * @return array{creados: int, omitidos: int, personas_nuevas: int, modelos_nuevos: int, proveedores_nuevos: int}
     */
    public function guardar(array $resultados, User $usuario, Importacion $importacion): array
    {
        $resumen = ['creados' => 0, 'omitidos' => 0, 'personas_nuevas' => 0, 'marcas_nuevas' => 0, 'modelos_nuevos' => 0, 'proveedores_nuevos' => 0];
        $personas = [];

        foreach ($resultados as $resultado) {
            if ($resultado['errores'] !== []) {
                $resumen['omitidos']++;

                continue;
            }

            $v = $resultado['valores'];

            if ($v['marca_id'] === null) {
                // Varias filas pueden traer la misma marca nueva: se crea una sola vez.
                $marca = Marca::all()->first(fn (Marca $m) => Normalizar::clave($m->nombre) === Normalizar::clave($v['marca_nueva']))
                    ?? Marca::create(['nombre' => $v['marca_nueva']]);
                $resumen['marcas_nuevas'] += (int) $marca->wasRecentlyCreated;
                $v['marca_id'] = $marca->id;
            }

            $modeloId = null;
            if ($v['modelo'] !== null) {
                $modelo = ModeloEquipo::firstOrCreate(['marca_id' => $v['marca_id'], 'tipo_equipo_id' => $v['tipo_equipo_id'], 'nombre' => $v['modelo']]);
                $resumen['modelos_nuevos'] += (int) $modelo->wasRecentlyCreated;
                $modeloId = $modelo->id;
            }

            $equipo = Equipo::create([
                'serial' => $v['serial'] ?? $this->serialProvisional($importacion, $resultado['numero']),
                'codigo_activo' => $v['codigo_activo'],
                'codigo_activo_justificacion' => $v['justificacion_codigo'],
                'tipo_equipo_id' => $v['tipo_equipo_id'],
                'marca_id' => $v['marca_id'],
                'modelo_id' => $modeloId,
                'descripcion' => $v['descripcion'],
                'proceso_id' => $v['proceso_id'],
                'propiedad' => $v['propiedad'],
                'proveedor_tercero_id' => $this->proveedorId($v['propietario_tercero'], $resumen),
                'figura_tercero' => $v['figura_tercero'],
                'forma_ingreso' => $v['forma_ingreso'],
                'fecha_adquisicion' => $v['fecha_adquisicion'],
                'valor_adquisicion' => $v['valor_adquisicion'],
                'proveedor_id' => $this->proveedorId($v['proveedor'], $resumen),
                'numero_contrato' => $v['numero_contrato'],
                'numero_factura' => $v['numero_factura'],
                'fecha_fin_garantia' => $v['fecha_fin_garantia'],
                'vida_util_meses' => $v['vida_util_meses'],
                'estado_funcionamiento' => $v['estado_funcionamiento'],
                'observaciones' => $v['observaciones'],
                'estado_ciclo_vida' => $v['persona'] !== null ? 'en_servicio' : 'sin_asignar',
                // RF-41: sin serial o con datos dudosos queda por verificar.
                'verificacion' => $v['serial'] === null || $resultado['dudosa'] ? 'pendiente_de_verificar' : 'verificado',
                'importacion_id' => $importacion->id,
                'fila_origen_importacion' => $resultado['numero'],
            ]);

            $alta = app(HistorialService::class)->registrar(
                equipo: $equipo,
                tipo: 'alta',
                usuario: $usuario,
                datos: ['es_historico' => true],
                descripcion: "Alta por carga de datos ({$importacion->archivo}, fila {$resultado['numero']}).",
            );

            $persona = $this->persona($v['persona'], $v['dependencia_id'], $personas, $resumen);

            Asignacion::create([
                'equipo_id' => $equipo->id,
                'persona_id' => $persona?->id,
                'piso_id' => $v['piso_id'],
                'dependencia_id' => $v['dependencia_id'],
                'fecha_inicio' => now(),
                'evento_origen_id' => $alta->id,
            ]);

            if ($v['usa_computo']) {
                $this->configuracionComputo($equipo, $v);
            }

            foreach ($v['perifericos'] ?? [] as $tipoComponenteId) {
                Componente::create([
                    'equipo_id' => $equipo->id,
                    'tipo_componente_id' => $tipoComponenteId,
                    'fecha_instalacion' => now(),
                ]);
            }

            $resumen['creados']++;
        }

        return $resumen;
    }

    /**
     * @param  array<string, ?string>  $d
     * @param  array<string, int>  $seriales
     * @param  array<string, int>  $codigos
     * @return array<string, mixed>
     */
    private function analizarFila(int $numero, array $d, array $seriales, array $codigos): array
    {
        $errores = [];
        $advertencias = [];
        // Advertencias que vuelven dudoso el dato (RF-41). Las demás son solo
        // informativas: un modelo, proveedor o persona nuevos no hacen dudoso al equipo.
        $dudosas = 0;
        $txt = fn (string $clave) => Normalizar::texto($d[$clave] ?? null);
        $intentar = function (callable $paso) use (&$errores) {
            try {
                return $paso();
            } catch (InvalidArgumentException $e) {
                $errores[] = $e->getMessage();

                return null;
            }
        };

        foreach (self::LARGOS as $clave => $maximo) {
            if (($texto = $txt($clave)) !== null && mb_strlen($texto) > $maximo) {
                $errores[] = PlantillaEquipos::COLUMNAS[$clave][0].' tiene '.mb_strlen($texto)." caracteres: el máximo es {$maximo}.";
            }
        }

        // --- Catálogos obligatorios ---
        $tipo = $this->buscar('tipos', $txt('tipo_equipo'));
        if (! $tipo) {
            $errores[] = $txt('tipo_equipo') ? "El tipo «{$txt('tipo_equipo')}» no está en el catálogo." : 'Falta el tipo de equipo.';
        }

        // Una marca que no está se agrega al catálogo, pero el equipo queda por
        // verificar (RF-41): así un error de escritura («Acee») se revisa.
        $marca = $this->buscar('marcas', $txt('marca'));
        if (! $marca && ! $txt('marca')) {
            $errores[] = 'Falta la marca.';
        } elseif (! $marca) {
            $advertencias[] = "Marca nueva: se agrega «{$txt('marca')}» al catálogo (revisa que esté bien escrita).";
            $dudosas++;
        }

        $estado = $this->estado($txt('estado_funcionamiento'));
        if (! $estado) {
            $errores[] = $txt('estado_funcionamiento')
                ? "El estado «{$txt('estado_funcionamiento')}» no es uno de: ".implode(', ', PlantillaEquipos::ESTADOS).'.'
                : 'Falta el estado de funcionamiento.';
        }

        // --- Ubicación: sede → piso de esa sede (RN-21) → dependencia ---
        $sede = $this->sede($txt('sede'));
        $piso = null;
        if (! $sede) {
            $errores[] = $txt('sede') ? "La sede «{$txt('sede')}» no está en el catálogo." : 'Falta la sede.';
        } else {
            $piso = $this->piso($sede->id, $txt('piso'));
            if (! $piso) {
                $errores[] = $txt('piso')
                    ? "El piso «{$txt('piso')}» no existe en la sede {$sede->nombre} (RN-21)."
                    : 'Falta el piso.';
            }
        }

        $dependenciaTexto = $txt('dependencia');
        $dependencia = $dependenciaTexto ? DependenciaEquivalencia::resolver($dependenciaTexto) : null;
        if (! $dependencia) {
            $errores[] = $dependenciaTexto
                ? "La dependencia «{$dependenciaTexto}» no coincide con ninguna oficial ni con una equivalencia (RF-39)."
                : 'Falta la dependencia.';
        }

        $proceso = null;
        if ($txt('proceso')) {
            $proceso = $this->buscar('procesos', $txt('proceso'));
            if (! $proceso) {
                $advertencias[] = "El proceso «{$txt('proceso')}» no está en el catálogo: se usa el de la dependencia.";
                $dudosas++;
            }
        }

        // --- Identificación ---
        $serial = $intentar(fn () => Normalizar::serial($d['serial'] ?? null));
        if ($serial === null) {
            $advertencias[] = 'Sin serial: se guarda uno provisional (RN-02).';
        } elseif (($seriales[$serial] ?? 0) > 1) {
            $errores[] = "El serial {$serial} está repetido en el archivo.";
        } elseif (Equipo::where('serial', $serial)->exists()) {
            $errores[] = "El serial {$serial} ya está registrado.";
        }

        $codigo = $intentar(fn () => Normalizar::codigoActivo($d['codigo_activo'] ?? null));
        $justificacion = $txt('justificacion_codigo');
        if ($codigo === null && ! in_array("El código de activo «{$txt('codigo_activo')}» no tiene el formato I1-######.", $errores, true)) {
            $advertencias[] = 'Sin código de activo.';
        } elseif ($codigo !== null && (($codigos[$codigo] ?? 0) > 1 || Equipo::where('codigo_activo', $codigo)->exists()) && $justificacion === null) {
            $errores[] = "El código {$codigo} ya está en otro equipo: escribe la justificación (RN-03).";
        }

        // Sin serial ni código no hay con qué reconocer un repetido: si esta misma
        // fila de este mismo archivo ya se cargó, volver a subirlo la duplicaría.
        if ($serial === null && $codigo === null && $tipo && $previo = $this->yaCargado($numero, $tipo->id)) {
            $errores[] = "Esta fila ya se cargó de «{$this->archivo}» (equipo N.º {$previo->numero_activo}): no se vuelve a crear.";
        }

        // --- Modelo (se crea si no existe) ---
        $modelo = $txt('modelo');
        if ($modelo && $tipo && (! $marca || ! ModeloEquipo::where(['marca_id' => $marca->id, 'tipo_equipo_id' => $tipo->id, 'nombre' => $modelo])->exists())) {
            $advertencias[] = "Modelo nuevo: se agrega «{$modelo}» al catálogo.";
        }

        // --- Propiedad (RF-04, RN-16) ---
        $propiedad = Normalizar::clave($txt('propiedad')) === 'tercero' ? 'tercero' : 'gobernacion';
        $propietario = $txt('propietario_tercero');
        $figura = null;
        if ($propiedad === 'tercero') {
            if (! $propietario) {
                $errores[] = 'Un equipo de tercero necesita el propietario (RN-16).';
            } elseif (! $this->buscar('proveedores', $propietario, 'razon_social')) {
                $advertencias[] = "Proveedor nuevo: se agrega «{$propietario}».";
            }

            $figura = collect(PlantillaEquipos::FIGURAS)->first(fn ($f) => Normalizar::clave($f) === Normalizar::clave($txt('figura_tercero')));
            if (! $figura) {
                $errores[] = 'La figura del tercero debe ser una de: '.implode(', ', PlantillaEquipos::FIGURAS).'.';
            }
        }

        // --- Responsable (RF-18) ---
        $persona = $this->datosPersona($d, $errores, $advertencias, $intentar, $dudosas);

        // --- Cómputo ---
        $usaComputo = (bool) $tipo?->usa_configuracion_computo;
        $sistema = null;
        if ($txt('sistema_operativo')) {
            $sistema = $this->buscar('sistemas', $txt('sistema_operativo'));
            if (! $sistema) {
                $advertencias[] = "El sistema operativo «{$txt('sistema_operativo')}» no está en el catálogo: queda vacío.";
                $dudosas++;
            }
        }
        $ip = $txt('direccion_ip');
        if ($ip !== null && ! filter_var($ip, FILTER_VALIDATE_IP)) {
            $errores[] = "La IP «{$ip}» no es válida.";
        }
        $antivirus = $txt('antivirus');
        $tieneAntivirus = $antivirus !== null && ! in_array(Normalizar::clave($antivirus), ['no', 'ninguno'], true);
        $perifericos = $this->perifericos($txt('perifericos'), $usaComputo, $advertencias);

        // --- Adquisición (RF-52) ---
        $formaIngreso = 'carga_inicial';
        if ($txt('forma_ingreso')) {
            $formaIngreso = collect(Equipo::FORMAS_INGRESO)->search(fn ($etiqueta, $clave) => in_array(Normalizar::clave($txt('forma_ingreso')), [Normalizar::clave($etiqueta), Normalizar::clave($clave)], true));
            if ($formaIngreso === false) {
                $errores[] = "La forma de ingreso «{$txt('forma_ingreso')}» no es válida.";
                $formaIngreso = null;
            }
        }
        $proveedor = $txt('proveedor');
        if ($proveedor && ! $this->buscar('proveedores', $proveedor, 'razon_social') && Normalizar::clave($proveedor) !== Normalizar::clave($propietario)) {
            $advertencias[] = "Proveedor nuevo: se agrega «{$proveedor}».";
        }
        $fechaAdquisicion = $intentar(fn () => Normalizar::fecha($d['fecha_adquisicion'] ?? null));
        $finGarantia = $intentar(fn () => Normalizar::fecha($d['fecha_fin_garantia'] ?? null));
        if ($fechaAdquisicion && $finGarantia && $finGarantia < $fechaAdquisicion) {
            $errores[] = 'La garantía no puede terminar antes de la fecha de adquisición.';
        }

        $valores = [
            'tipo_equipo_id' => $tipo?->id,
            'usa_computo' => $usaComputo,
            'marca_id' => $marca?->id,
            'marca_nueva' => $marca ? null : $txt('marca'),
            'modelo' => $modelo,
            'serial' => $serial,
            'codigo_activo' => $codigo,
            'justificacion_codigo' => $codigo !== null ? $justificacion : null,
            'descripcion' => $txt('descripcion') !== null ? mb_substr($txt('descripcion'), 0, 255) : null,
            'estado_funcionamiento' => $estado,
            'propiedad' => $propiedad,
            'propietario_tercero' => $propiedad === 'tercero' ? $propietario : null,
            'figura_tercero' => $figura ? Normalizar::clave($figura) : null,
            'piso_id' => $piso?->id,
            'dependencia_id' => $dependencia?->id,
            'proceso_id' => $proceso?->id ?? $dependencia?->proceso_id,
            'persona' => $persona,
            'sistema_operativo_id' => $sistema?->id,
            'procesador' => $txt('procesador'),
            'memoria_ram' => $txt('memoria_ram'),
            'disco' => $txt('disco'),
            'tiene_antivirus' => $tieneAntivirus,
            'antivirus_producto' => $tieneAntivirus && ! in_array(Normalizar::clave($antivirus), ['si', 'sí'], true) ? $antivirus : null,
            'nombre_red' => $txt('nombre_red'),
            'ofimatica' => $txt('ofimatica'),
            'dominio' => $txt('dominio'),
            'direccion_ip' => $ip,
            'direccion_mac' => $intentar(fn () => Normalizar::mac($d['direccion_mac'] ?? null)),
            'perifericos' => $perifericos,
            'forma_ingreso' => $formaIngreso,
            'fecha_adquisicion' => $fechaAdquisicion,
            'valor_adquisicion' => $intentar(fn () => Normalizar::valor($d['valor_adquisicion'] ?? null)),
            'proveedor' => $proveedor,
            'numero_contrato' => $txt('numero_contrato'),
            'numero_factura' => $txt('numero_factura'),
            'fecha_fin_garantia' => $finGarantia,
            'vida_util_meses' => $intentar(fn () => Normalizar::entero($d['vida_util_meses'] ?? null)),
            'observaciones' => $txt('observaciones'),
        ];

        return [
            'numero' => $numero,
            'resumen' => trim(implode(' · ', array_filter([$txt('tipo_equipo'), $txt('marca'), $serial, $codigo]))),
            'errores' => array_values(array_unique($errores)),
            'advertencias' => array_values(array_unique($advertencias)),
            'dudosa' => $dudosas > 0,
            // Para ofrecer crear la equivalencia desde la pantalla (RF-39).
            'dependencia_sin_resolver' => $dependencia === null ? $dependenciaTexto : null,
            'valores' => $valores,
        ];
    }

    /**
     * Datos de la persona responsable (o null si la fila no trae responsable).
     *
     * @return array<string, mixed>|null
     */
    private function datosPersona(array $d, array &$errores, array &$advertencias, callable $intentar, int &$dudosas): ?array
    {
        $documento = $intentar(fn () => Normalizar::documento($d['responsable_documento'] ?? null));
        $nombres = Normalizar::texto($d['responsable_nombres'] ?? null);

        if ($documento === null && $nombres === null) {
            return null;
        }

        if ($documento !== null && mb_strlen($documento) > 20) {
            $errores[] = 'El documento del responsable tiene más de 20 caracteres.';
        }

        // Sin documento, la persona se reconoce por su nombre completo: así no se
        // crea una nueva por cada equipo de la fila ni en cada carga.
        $existente = $documento
            ? Persona::where('numero_documento', $documento)->first()
            : $this->personaSinDocumento($nombres, Normalizar::texto($d['responsable_apellidos'] ?? null));
        if (! $existente && $nombres === null) {
            $errores[] = "No hay ninguna persona con el documento {$documento}: escribe sus nombres para crearla.";

            return null;
        }

        $tipoDocumento = mb_strtoupper(Normalizar::texto($d['responsable_tipo_documento'] ?? null) ?? 'CC');
        if (! array_key_exists($tipoDocumento, Persona::TIPOS_DOCUMENTO)) {
            $errores[] = "El tipo de documento «{$tipoDocumento}» no es válido.";
        }

        $cargoId = null;
        if ($cargoTexto = Normalizar::texto($d['responsable_cargo'] ?? null)) {
            $cargoId = $this->cargo($cargoTexto)?->id;
            if (! $cargoId) {
                $advertencias[] = "El cargo «{$cargoTexto}» no está en el Decreto 607: la persona queda sin cargo.";
                $dudosas++;
            }
        }

        $vinculacion = null;
        if ($vinculacionTexto = Normalizar::texto($d['responsable_vinculacion'] ?? null)) {
            $vinculacion = collect(Persona::VINCULACIONES)->search(fn ($etiqueta, $clave) => in_array(Normalizar::clave($vinculacionTexto), [Normalizar::clave($etiqueta), Normalizar::clave($clave)], true)) ?: null;
            if (! $vinculacion) {
                $advertencias[] = "La vinculación «{$vinculacionTexto}» no es planta, contratista ni pasante.";
                $dudosas++;
            }
        }

        if (! $existente) {
            $advertencias[] = 'Persona nueva: se crea el responsable.';
        }

        return [
            'existente_id' => $existente?->id,
            'documento' => $documento,
            'tipo_documento' => $tipoDocumento,
            'nombres' => $nombres,
            'apellidos' => Normalizar::texto($d['responsable_apellidos'] ?? null),
            'cargo_id' => $cargoId,
            'vinculacion' => $vinculacion,
        ];
    }

    /**
     * @param  array<string, Persona>  $personas  Personas ya creadas en esta carga, por documento.
     */
    private function persona(?array $datos, ?int $dependenciaId, array &$personas, array &$resumen): ?Persona
    {
        if ($datos === null) {
            return null;
        }

        if ($datos['existente_id']) {
            return Persona::find($datos['existente_id']);
        }

        $clave = $datos['documento'] ?? 'nombre:'.Normalizar::clave($datos['nombres'].' '.$datos['apellidos']);
        if (isset($personas[$clave])) {
            return $personas[$clave];
        }

        $persona = Persona::create([
            'tipo_documento' => $datos['tipo_documento'],
            'numero_documento' => $datos['documento'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'cargo_id' => $datos['cargo_id'],
            'dependencia_id' => $dependenciaId,
            'tipo_vinculacion' => $datos['vinculacion'],
        ]);
        $resumen['personas_nuevas']++;
        $personas[$clave] = $persona;

        return $persona;
    }

    private function personaSinDocumento(?string $nombres, ?string $apellidos): ?Persona
    {
        if ($nombres === null) {
            return null;
        }

        $buscado = Normalizar::clave($nombres.' '.$apellidos);

        return Persona::whereNull('numero_documento')->get()
            ->first(fn (Persona $p) => Normalizar::clave($p->nombreCompleto()) === $buscado);
    }

    /** Equipo creado antes desde esta misma fila de un archivo con el mismo nombre. */
    private function yaCargado(int $numero, int $tipoId): ?Equipo
    {
        if ($this->archivo === null) {
            return null;
        }

        return Equipo::where('fila_origen_importacion', $numero)
            ->where('tipo_equipo_id', $tipoId)
            ->whereHas('importacion', fn ($q) => $q->where('archivo', $this->archivo))
            ->first();
    }

    /**
     * «Teclado, Mouse y Sonido» → ids de los tipos de componente periféricos.
     *
     * @return array<int, int>
     */
    private function perifericos(?string $texto, bool $usaComputo, array &$advertencias): array
    {
        if ($texto === null) {
            return [];
        }

        if (! $usaComputo) {
            $advertencias[] = 'Los periféricos solo se registran en equipos de cómputo: se omiten.';

            return [];
        }

        $ids = [];
        foreach (preg_split('/\s*(?:[,;\/]|\by\b)\s*/u', $texto) as $nombre) {
            if (trim($nombre) === '') {
                continue;
            }

            $tipo = $this->buscar('perifericos', $nombre);
            if ($tipo) {
                $ids[] = $tipo->id;
            } else {
                $advertencias[] = "El periférico «{$nombre}» no está en el catálogo: se omite.";
            }
        }

        return array_values(array_unique($ids));
    }

    private function configuracionComputo(Equipo $equipo, array $v): void
    {
        ConfiguracionComputo::create([
            'equipo_id' => $equipo->id,
            'sistema_operativo_id' => $v['sistema_operativo_id'],
            'tiene_antivirus' => $v['tiene_antivirus'],
            'antivirus_producto' => $v['antivirus_producto'],
            'nombre_red' => $v['nombre_red'],
            'ofimatica' => $v['ofimatica'],
            'dominio' => $v['dominio'],
            'direccion_ip' => $v['direccion_ip'],
            'direccion_mac' => $v['direccion_mac'],
        ]);

        foreach (['Procesador' => 'procesador', 'RAM' => 'memoria_ram', 'Disco' => 'disco'] as $nombre => $clave) {
            if ($v[$clave] !== null && $tipo = TipoComponente::where('nombre', $nombre)->first()) {
                Componente::create([
                    'equipo_id' => $equipo->id,
                    'tipo_componente_id' => $tipo->id,
                    'capacidad_caracteristica' => $v[$clave],
                    'fecha_instalacion' => now(),
                ]);
            }
        }
    }

    private function proveedorId(?string $razonSocial, array &$resumen): ?int
    {
        if ($razonSocial === null) {
            return null;
        }

        $existente = Proveedor::all()->first(fn (Proveedor $p) => Normalizar::clave($p->razon_social) === Normalizar::clave($razonSocial));
        if ($existente) {
            return $existente->id;
        }

        $resumen['proveedores_nuevos']++;

        return Proveedor::create(['razon_social' => $razonSocial])->id;
    }

    private function serialProvisional(Importacion $importacion, int $fila): string
    {
        // Mismo prefijo que reconoce el filtro «Sin serial» del listado de equipos.
        return "PENDIENTE-{$importacion->id}-{$fila}";
    }

    private function estado(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }

        $clave = Normalizar::clave($texto);

        // Se empareja contra las etiquetas del formato (PlantillaEquipos::ESTADOS)
        // y se guarda la clave del enum (bueno/regular/malo/no_funcional).
        $etiqueta = collect(PlantillaEquipos::ESTADOS)->first(fn ($estado) => Normalizar::clave($estado) === $clave || str_starts_with(Normalizar::clave($estado), $clave.' ') || ($clave === 'malo' && str_starts_with(Normalizar::clave($estado), 'malo')));

        return $etiqueta === null ? null : (array_search($etiqueta, Equipo::ESTADOS_FUNCIONAMIENTO, true) ?: null);
    }

    /**
     * Sede por su nombre, sin contar palabras de relleno: «Palacio Nain» es
     * «Palacio de Naín» y «Morindo» es «Edificio Morindó». No es parecido:
     * después de quitar esas palabras, el nombre tiene que ser igual.
     */
    private function sede(?string $texto): ?Sede
    {
        if ($texto === null) {
            return null;
        }

        $sinRelleno = fn (?string $nombre) => trim(preg_replace('/\s+/', ' ', preg_replace('/\b(de|del|la|el|edificio|sede)\b/', ' ', Normalizar::clave($nombre))));

        return $this->buscar('sedes', $texto)
            ?? $this->catalogos['sedes']->first(fn (Sede $s) => $sinRelleno($s->nombre) === $sinRelleno($texto));
    }

    private function piso(int $sedeId, ?string $texto): ?Piso
    {
        if ($texto === null) {
            return null;
        }

        $pisos = Piso::where('sede_id', $sedeId)->get();
        $numero = preg_match('/^(piso\s*)?(\d+)$/i', trim($texto), $m) ? (int) $m[2] : null;

        return $numero !== null
            ? $pisos->firstWhere('numero', $numero)
            : $pisos->first(fn (Piso $p) => Normalizar::clave($p->etiqueta()) === Normalizar::clave($texto));
    }

    private function cargo(string $texto): ?Cargo
    {
        // «219», «219 · Profesional Universitario» o «Profesional Universitario».
        if (preg_match('/^(\d{3})\b/', $texto, $m)) {
            return Cargo::where('codigo', $m[1])->first();
        }

        $clave = Normalizar::clave(preg_replace('/\(\s*e\s*\)/i', '', $texto));

        return Cargo::all()->first(fn (Cargo $c) => Normalizar::clave($c->denominacion) === $clave);
    }

    private function buscar(string $catalogo, ?string $texto, string $campo = 'nombre'): mixed
    {
        if ($texto === null) {
            return null;
        }

        return $this->catalogos[$catalogo]->first(fn ($item) => Normalizar::clave($item->{$campo}) === Normalizar::clave($texto));
    }

    private function cargarCatalogos(): void
    {
        $this->catalogos = [
            'tipos' => TipoEquipo::all(),
            'marcas' => Marca::all(),
            'sedes' => Sede::all(),
            'procesos' => Proceso::all(),
            'proveedores' => Proveedor::all(),
            'sistemas' => SistemaOperativo::all(),
            'perifericos' => TipoComponente::where('es_periferico', true)->get(),
        ];
    }
}
