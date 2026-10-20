<?php

namespace App\Livewire\Importacion;

use App\Livewire\Importacion\Soporte\CargaAdquisicion;
use App\Livewire\Importacion\Soporte\CargaEquipos;
use App\Livewire\Importacion\Soporte\CargaPersonas;
use App\Livewire\Importacion\Soporte\CargaValoraciones;
use App\Livewire\Importacion\Soporte\ConversorInventario2026;
use App\Livewire\Importacion\Soporte\LectorHoja;
use App\Livewire\Importacion\Soporte\PlantillaEquipos;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Importacion;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Carga de datos al modelo oficial (RF-38 a RF-41, RF-55 y §12 del análisis v4.1).
 *
 * Cinco cargas con el mismo flujo: subir el archivo → vista previa con errores
 * y advertencias por fila (RF-40) → crear las equivalencias de dependencia que
 * falten (RF-39) → simular (RF-38) → confirmar. Nada se guarda hasta confirmar,
 * y la simulación corre todo dentro de una transacción que se deshace.
 */
class Index extends Component
{
    use WithFileUploads;

    public const TIPOS = [
        'equipos' => 'Equipos (plantilla oficial)',
        'personas' => 'Personas (planta de personal)',
        'valoraciones' => 'Valoraciones (matriz FOR.01)',
        'adquisicion' => 'Adquisición (archivo de Almacén)',
        'inventario' => 'Inventario 2026 (Forms)',
    ];

    #[Url]
    public string $tipo = 'equipos';

    public $archivo;

    /** Personas: planta de origen y orden del nombre cuando viene en una sola columna. */
    public string $planta = 'central';

    public string $ordenNombre = 'nombres_apellidos';

    /** @var array<int, array<string, mixed>> Resultado de analizar cada fila (sin columnas descartadas). */
    public array $resultados = [];

    /** @var array<int, array{texto: string, dependencia_id: ?int}> Dependencias sin coincidencia, para crear su equivalencia. */
    public array $pendientes = [];

    public array $lectura = ['leidas' => 0, 'excluidas' => 0, 'faltantes' => []];

    /** '' | errores | advertencias */
    public string $vista = '';

    public ?array $resumen = null;

    public bool $simulado = false;

    public function updatedTipo(): void
    {
        if (! array_key_exists($this->tipo, self::TIPOS)) {
            $this->tipo = 'equipos';
        }

        $this->reiniciar();
    }

    public function updatedArchivo(): void
    {
        $this->validate(['archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480']]);

        $this->analizar();
    }

    /** Cambiar la planta o el orden del nombre vuelve a analizar el mismo archivo. */
    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['planta', 'ordenNombre'], true) && $this->archivo) {
            $this->analizar();
        }
    }

    public function analizar(): void
    {
        $this->resumen = null;
        $this->resetErrorBag();
        $ruta = $this->archivo->getRealPath();

        if ($this->tipo === 'inventario') {
            $conversion = ConversorInventario2026::convertir($ruta);
            $filas = array_map(fn ($datos, $i) => ['numero' => $i + 2, 'datos' => $datos], $conversion['filas'], array_keys($conversion['filas']));
            $this->lectura = ['leidas' => $conversion['leidas'], 'excluidas' => $conversion['excluidas'], 'faltantes' => []];
            $this->resultados = (new CargaEquipos)->analizar($filas);
        } else {
            $lectura = match ($this->tipo) {
                'equipos' => LectorHoja::leer($ruta, PlantillaEquipos::anclas(), PlantillaEquipos::encabezadosAceptados()),
                'personas' => CargaPersonas::leer($ruta),
                'valoraciones' => CargaValoraciones::leer($ruta),
                'adquisicion' => CargaAdquisicion::leer($ruta),
            };

            if (! $lectura['encontrado']) {
                $this->resultados = [];
                $this->addError('archivo', __('No se reconoce el archivo: no tiene la fila de encabezados esperada (:columnas).', ['columnas' => implode(', ', $lectura['faltantes'])]));

                return;
            }

            $this->lectura = ['leidas' => count($lectura['filas']), 'excluidas' => 0, 'faltantes' => $lectura['faltantes']];
            $this->resultados = match ($this->tipo) {
                'equipos' => (new CargaEquipos)->analizar($lectura['filas']),
                'personas' => (new CargaPersonas)->analizar($lectura['filas'], $this->planta, $this->ordenNombre),
                'valoraciones' => (new CargaValoraciones)->analizar($lectura['filas']),
                'adquisicion' => (new CargaAdquisicion)->analizar($lectura['filas']),
            };
        }

        $this->pendientes = collect($this->resultados)
            ->pluck('dependencia_sin_resolver')
            ->filter()
            ->unique(fn ($texto) => DependenciaEquivalencia::normalizar($texto))
            ->map(fn ($texto) => ['texto' => $texto, 'dependencia_id' => null])
            ->values()
            ->all();
    }

    /**
     * RF-39: guarda la equivalencia «texto del archivo → dependencia oficial»
     * y vuelve a analizar, así las filas que dependían de ella se corrigen.
     */
    public function guardarEquivalencia(int $indice): void
    {
        $pendiente = $this->pendientes[$indice] ?? null;
        $this->validate(["pendientes.{$indice}.dependencia_id" => ['required', 'exists:dependencias,id']], [], ["pendientes.{$indice}.dependencia_id" => __('dependencia')]);

        DependenciaEquivalencia::firstOrCreate(
            ['texto' => $pendiente['texto']],
            ['dependencia_id' => $pendiente['dependencia_id'], 'origen' => self::TIPOS[$this->tipo]],
        );

        $this->analizar();
        session()->flash('status', __('Equivalencia guardada: «:texto» ahora se reconoce.', ['texto' => $pendiente['texto']]));
    }

    public function simular(): void
    {
        $this->ejecutar(simular: true);
    }

    public function confirmar(): void
    {
        $this->ejecutar(simular: false);
    }

    /**
     * Guarda las filas sin error. En la simulación todo ocurre igual, pero
     * dentro de una transacción que al final se deshace (RF-38).
     */
    private function ejecutar(bool $simular): void
    {
        if ($this->resultados === [] || ! $this->archivo) {
            $this->addError('archivo', __('Sube un archivo antes de continuar.'));

            return;
        }

        // Se vuelve a analizar justo antes de guardar: la base pudo cambiar desde la vista previa.
        $this->analizar();
        $usuario = auth()->user();
        $nombre = $this->archivo->getClientOriginalName();

        DB::beginTransaction();

        try {
            $importacion = Importacion::create([
                'tipo' => $this->tipo === 'inventario' ? 'equipos' : $this->tipo,
                'archivo' => $nombre,
                'fecha' => now(),
                'usuario_id' => $usuario->id,
                'filas' => count($this->resultados),
                // Solo el número de fila y los mensajes: nunca los datos de la fila.
                'errores' => collect($this->resultados)
                    ->filter(fn ($r) => $r['errores'] !== [])
                    ->map(fn ($r) => ['fila' => $r['numero'], 'errores' => $r['errores']])
                    ->values()
                    ->all(),
            ]);

            $resumen = match ($this->tipo) {
                'equipos', 'inventario' => (new CargaEquipos)->guardar($this->resultados, $usuario, $importacion),
                'personas' => (new CargaPersonas)->guardar($this->resultados),
                'valoraciones' => (new CargaValoraciones)->guardar($this->resultados, $usuario, $nombre),
                'adquisicion' => (new CargaAdquisicion)->guardar($this->resultados, $usuario, $nombre),
            };

            $simular ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            $this->addError('archivo', __('La carga no se pudo completar y no se guardó nada: :mensaje', ['mensaje' => $e->getMessage()]));

            return;
        }

        $this->resumen = $resumen;
        $this->simulado = $simular;

        if (! $simular) {
            $this->reset(['archivo', 'resultados', 'pendientes', 'lectura']);
        }
    }

    public function reiniciar(): void
    {
        $this->reset(['archivo', 'resultados', 'pendientes', 'lectura', 'vista', 'resumen', 'simulado']);
        $this->resetErrorBag();
    }

    public function descargarPlantilla(): StreamedResponse
    {
        $ruta = PlantillaEquipos::generar();

        return response()->streamDownload(function () use ($ruta) {
            readfile($ruta);
            @unlink($ruta);
        }, 'plantilla-carga-equipos.xlsx');
    }

    /**
     * Día 7: el inventario 2026 convertido a la plantilla, para revisarlo y
     * corregirlo en Excel antes de cargarlo como «Equipos».
     */
    public function descargarInventarioConvertido(): ?StreamedResponse
    {
        if (! $this->archivo) {
            $this->addError('archivo', __('Sube primero el Excel del inventario 2026.'));

            return null;
        }

        $ruta = PlantillaEquipos::generar(ConversorInventario2026::convertir($this->archivo->getRealPath())['filas']);

        return response()->streamDownload(function () use ($ruta) {
            readfile($ruta);
            @unlink($ruta);
        }, 'inventario-2026-en-plantilla.xlsx');
    }

    public function render()
    {
        $resultados = collect($this->resultados);
        $visibles = match ($this->vista) {
            'errores' => $resultados->filter(fn ($r) => $r['errores'] !== []),
            'advertencias' => $resultados->filter(fn ($r) => $r['errores'] === [] && $r['advertencias'] !== []),
            default => $resultados,
        };

        return view('livewire.importacion.index', [
            'tipos' => self::TIPOS,
            'kpis' => [
                'filas' => $resultados->count(),
                'listas' => $resultados->filter(fn ($r) => $r['errores'] === [] && ! ($r['omitida'] ?? false))->count(),
                'advertencias' => $resultados->filter(fn ($r) => $r['errores'] === [] && $r['advertencias'] !== [])->count(),
                'errores' => $resultados->filter(fn ($r) => $r['errores'] !== [])->count(),
            ],
            'visibles' => $visibles->take(300),
            'totalVisibles' => $visibles->count(),
            'dependencias' => $this->pendientes !== [] ? Dependencia::arbol() : collect(),
            'ordenesNombre' => CargaPersonas::ORDENES_NOMBRE,
        ]);
    }
}
