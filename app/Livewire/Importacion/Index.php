<?php

namespace App\Livewire\Importacion;

use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Importacion;
use App\Models\Marca;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Services\HistorialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                foreach ($fila['equipos'] as $datos) {
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

                    app(HistorialService::class)->registrar(
                        equipo: $equipo,
                        tipo: 'alta',
                        usuario: $usuario,
                        descripcion: 'Alta por importación del inventario 2026, fila '.$fila['numero'].'.',
                    );

                    $this->crearComponentes($equipo, $fila['datos']);
                    $this->crearConfiguracion($equipo, $fila['datos']);
                }
            }
        });

        session()->flash('status', __('La importación fue confirmada correctamente.'));
        $this->reset(['archivo', 'filas', 'equivalencias', 'advertencias', 'kpis']);
        $this->pasoActual = 1;
    }

    public function advertenciasPorFila(): array
    {
        $advertencias = [];
        foreach ($this->filas as $fila) {
            foreach ($fila['equipos'] as $equipo) {
                if (! $equipo['codigo']) {
                    $advertencias[] = ['fila' => $fila['numero'], 'tipo' => 'sin_codigo', 'detalle' => __('Equipo sin código de activo.')];
                }
                if (! $equipo['serial']) {
                    $advertencias[] = ['fila' => $fila['numero'], 'tipo' => 'sin_serial', 'detalle' => __('Equipo sin serial; queda pendiente de verificar.')];
                }
                if ($equipo['codigo'] && Equipo::where('codigo_activo', $this->normalizarCodigo($equipo['codigo']))->exists()) {
                    $advertencias[] = ['fila' => $fila['numero'], 'tipo' => 'repetido', 'detalle' => __('Código de activo ya registrado.')];
                }
            }
        }

        return $advertencias;
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

    private function equiposDeFila(array $fila, int $numero): array
    {
        $equipos = [];
        foreach ([
            'pc' => ['pc', 'computador', 'equipo principal', 'desktop', 'portatil', 'laptop'],
            'monitor' => ['monitor', 'pantalla'],
            'impresora' => ['impresora', 'printer'],
            'escaner' => ['escaner', 'scanner'],
            'ups' => ['ups'],
        ] as $tipo => $aliases) {
            $valor = $this->valorPorAlias($fila, $aliases);
            if ($valor === null && $tipo !== 'pc') {
                continue;
            }
            $equipos[] = [
                'tipo' => $tipo === 'pc' ? ($valor ?: 'pc de escritorio') : $tipo,
                'codigo' => $this->valorPorAlias($fila, [$tipo.' codigo', $tipo.' activo', 'codigo '.$tipo, 'codigo activo']),
                'serial' => $this->valorPorAlias($fila, [$tipo.' serial', 'serial '.$tipo, 'serial']),
                'marca' => $this->valorPorAlias($fila, [$tipo.' marca', 'marca '.$tipo, 'marca']),
                'modelo' => $this->valorPorAlias($fila, [$tipo.' modelo', 'modelo '.$tipo, 'modelo']),
                'estado' => $this->valorPorAlias($fila, [$tipo.' estado', 'estado '.$tipo, 'estado funcionamiento']),
                'tercero' => $this->esTercero($fila),
                'propietario' => $this->valorPorAlias($fila, ['propietario tercero', 'propietario']),
            ];
        }
        return $equipos;
    }

    private function detectarEquivalencias(): array
    {
        $resultado = [];
        foreach ($this->filas as $fila) {
            foreach (['dependencia', 'sede', 'piso', 'marca', 'sistema operativo'] as $campo) {
                $valor = $this->valorPorAlias($fila['datos'], [$campo]);
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

    private function crearComponentes(Equipo $equipo, array $fila): void
    {
        foreach (['teclado', 'mouse', 'sonido'] as $nombre) {
            $valor = $this->valorPorAlias($fila, [$nombre, $nombre.' serial']);
            if (! $valor) {
                continue;
            }
            $tipo = TipoComponente::firstOrCreate(['nombre' => ucfirst($nombre)], ['es_periferico' => true]);
            Componente::create(['equipo_id' => $equipo->id, 'tipo_componente_id' => $tipo->id, 'serial' => $valor]);
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
        $tipo = TipoEquipo::query()->get()->sortBy(fn ($item) => levenshtein(Str::lower($valor), Str::lower($item->nombre)))->first();
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
        return $modelo::query()->get()->sortBy(fn ($item) => levenshtein(Str::lower($valor), Str::lower((string) ($item->nombre ?? $item->numero))))->first();
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
        $valor = $this->valorPorAlias($fila, [$campo]);
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
