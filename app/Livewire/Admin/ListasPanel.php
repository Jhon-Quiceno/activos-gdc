<?php

namespace App\Livewire\Admin;

use App\Models\Marca;
use App\Models\MotivoBaja;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Listas" (Manuel, bloque de Administración): patrón maestro-detalle
 * sobre los catálogos planos de la app: tipos de equipo y de componente, marcas,
 * sistemas operativos y motivos de baja.
 *
 * Modelo v4: sedes, pisos y dependencias salieron de este panel genérico. En v4
 * son catálogos relacionales (la sede necesita municipio, el piso necesita sede
 * y número único por sede, la dependencia es un árbol con tipo y padre), así que
 * tienen sus propias pantallas en el bloque de Administración.
 *
 * Además del nombre, tres catálogos tienen campos propios de v4 que también se
 * mantienen aquí (RF-45): el tipo de equipo (familia, tipo de activo GT-M4,
 * configuración de cómputo, vida útil y activo), el sistema operativo (fin de
 * soporte, para la obsolescencia) y el tipo de componente (obligatorio en cómputo,
 * RF-14). Los campos extra viven en `$extra` (agregar) y `$editandoExtra` (editar).
 *
 * "Tipos de evento" sigue fuera: es el enum fijo `Evento::tipo`, no una tabla.
 */
class ListasPanel extends Component
{
    public string $listaActiva = 'tipos_equipo';

    public string $nuevoValor = '';

    /** Campos propios del catálogo activo al agregar. */
    public array $extra = [];

    public ?int $editandoId = null;

    public string $editandoValor = '';

    /** Campos propios del catálogo activo al editar. */
    public array $editandoExtra = [];

    /**
     * @var array<string, array{label: string, singular: string, model: class-string}>
     */
    private const CATALOGOS = [
        'tipos_equipo' => ['label' => 'Tipos de equipo', 'singular' => 'Tipo de equipo', 'model' => TipoEquipo::class],
        'tipos_componente' => ['label' => 'Tipos de componente', 'singular' => 'Tipo de componente', 'model' => TipoComponente::class],
        'marcas' => ['label' => 'Marcas', 'singular' => 'Marca', 'model' => Marca::class],
        'sistemas_operativos' => ['label' => 'Sistemas operativos', 'singular' => 'Sistema operativo', 'model' => SistemaOperativo::class],
        'motivos_baja' => ['label' => 'Motivos de baja', 'singular' => 'Motivo de baja', 'model' => MotivoBaja::class],
    ];

    /**
     * Etiquetas de `tipos_equipo.familia`: los 10 valores del enum de v4
     * (migración 2026_10_18_100009).
     *
     * @var array<string, string>
     */
    private const FAMILIAS = [
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

    /** Tipo de activo del GT-M4 (tabla 2): el soporte no se valora (RN-25). */
    private const TIPOS_ACTIVO = [
        'hardware' => 'Hardware',
        'soporte' => 'Soporte',
    ];

    /**
     * Etiqueta de la columna de "uso" por catálogo: Tipos de equipo y Marcas
     * miden equipos; el resto usa la etiqueta genérica "Uso".
     *
     * @var array<string, string>
     */
    private const USO_LABELS = [
        'tipos_equipo' => 'Equipos',
        'marcas' => 'Equipos',
        'tipos_componente' => 'Uso',
        'sistemas_operativos' => 'Uso',
        'motivos_baja' => 'Uso',
    ];

    public function mount(): void
    {
        $this->extra = $this->extraPorDefecto();
    }

    public function seleccionarLista(string $clave): void
    {
        if (! array_key_exists($clave, self::CATALOGOS)) {
            return;
        }

        $this->listaActiva = $clave;
        $this->reset(['nuevoValor']);
        $this->extra = $this->extraPorDefecto();
        $this->resetErrorBag();
    }

    /**
     * @return array{label: string, singular: string, model: class-string}
     */
    private function config(): array
    {
        return self::CATALOGOS[$this->listaActiva];
    }

    /** Valores iniciales de los campos propios del catálogo activo. */
    private function extraPorDefecto(): array
    {
        return match ($this->listaActiva) {
            'tipos_equipo' => ['familia' => '', 'tipo_activo' => 'hardware', 'usa_configuracion_computo' => false, 'vida_util_meses' => '', 'activo' => true],
            'sistemas_operativos' => ['fin_soporte' => ''],
            'tipos_componente' => ['obligatorio_en_computo' => false],
            default => [],
        };
    }

    /** Reglas de los campos propios, con el prefijo de la propiedad que los guarda. */
    private function reglasExtra(string $prefijo): array
    {
        $reglas = match ($this->listaActiva) {
            'tipos_equipo' => [
                'familia' => ['required', Rule::in(array_keys(self::FAMILIAS))],
                'tipo_activo' => ['required', Rule::in(array_keys(self::TIPOS_ACTIVO))],
                'usa_configuracion_computo' => ['boolean'],
                'vida_util_meses' => ['nullable', 'integer', 'min:1', 'max:600'],
                'activo' => ['boolean'],
            ],
            'sistemas_operativos' => [
                'fin_soporte' => ['nullable', 'date'],
            ],
            'tipos_componente' => [
                'obligatorio_en_computo' => ['boolean'],
            ],
            default => [],
        };

        return collect($reglas)->mapWithKeys(fn ($r, $campo) => ["{$prefijo}.{$campo}" => $r])->all();
    }

    private function atributosExtra(string $prefijo): array
    {
        return [
            "{$prefijo}.familia" => __('familia'),
            "{$prefijo}.tipo_activo" => __('tipo de activo'),
            "{$prefijo}.vida_util_meses" => __('vida útil'),
            "{$prefijo}.fin_soporte" => __('fin de soporte'),
        ];
    }

    /** Columnas listas para guardar a partir de los campos propios. */
    private function datosExtra(array $extra): array
    {
        return match ($this->listaActiva) {
            'tipos_equipo' => [
                'familia' => $extra['familia'],
                'tipo_activo' => $extra['tipo_activo'],
                'usa_configuracion_computo' => (bool) ($extra['usa_configuracion_computo'] ?? false),
                'vida_util_meses' => ($extra['vida_util_meses'] ?? '') !== '' ? (int) $extra['vida_util_meses'] : null,
                'activo' => (bool) ($extra['activo'] ?? true),
            ],
            'sistemas_operativos' => [
                'fin_soporte' => ($extra['fin_soporte'] ?? '') !== '' ? $extra['fin_soporte'] : null,
            ],
            'tipos_componente' => [
                'obligatorio_en_computo' => (bool) ($extra['obligatorio_en_computo'] ?? false),
            ],
            default => [],
        };
    }

    public function agregar(): void
    {
        $config = $this->config();
        $tabla = (new $config['model'])->getTable();

        $this->validate(
            ['nuevoValor' => ['required', 'string', 'max:255', Rule::unique($tabla, 'nombre')]] + $this->reglasExtra('extra'),
            [],
            ['nuevoValor' => __('nombre')] + $this->atributosExtra('extra'),
        );

        $config['model']::create(['nombre' => $this->nuevoValor] + $this->datosExtra($this->extra));

        $this->reset(['nuevoValor']);
        $this->extra = $this->extraPorDefecto();

        session()->flash('status', __(':singular agregado correctamente.', ['singular' => $config['singular']]));
    }

    public function abrirEdicion(int $id): void
    {
        $config = $this->config();
        $registro = $config['model']::findOrFail($id);

        $this->editandoId = $registro->id;
        $this->editandoValor = (string) $registro->nombre;
        $this->editandoExtra = match ($this->listaActiva) {
            'tipos_equipo' => [
                'familia' => (string) $registro->familia,
                'tipo_activo' => (string) ($registro->tipo_activo ?? 'hardware'),
                'usa_configuracion_computo' => (bool) $registro->usa_configuracion_computo,
                'vida_util_meses' => (string) ($registro->vida_util_meses ?? ''),
                'activo' => (bool) $registro->activo,
            ],
            'sistemas_operativos' => ['fin_soporte' => (string) $registro->fin_soporte?->toDateString()],
            'tipos_componente' => ['obligatorio_en_computo' => (bool) $registro->obligatorio_en_computo],
            default => [],
        };
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'editar-item');
    }

    public function guardarEdicion(): void
    {
        $config = $this->config();
        $tabla = (new $config['model'])->getTable();

        $this->validate(
            ['editandoValor' => ['required', 'string', 'max:255', Rule::unique($tabla, 'nombre')->ignore($this->editandoId)]] + $this->reglasExtra('editandoExtra'),
            [],
            ['editandoValor' => __('nombre')] + $this->atributosExtra('editandoExtra'),
        );

        $registro = $config['model']::findOrFail($this->editandoId);
        $registro->update(['nombre' => $this->editandoValor] + $this->datosExtra($this->editandoExtra));

        $this->dispatch('close-modal', 'editar-item');

        session()->flash('status', __(':singular actualizado correctamente.', ['singular' => $config['singular']]));
    }

    /**
     * Conteo real de "uso" por fila, según el catálogo activo. Cada rama usa
     * la relación que mejor representa el uso de ese registro en el inventario.
     */
    private function usoDe(mixed $registro): int
    {
        return match ($this->listaActiva) {
            'tipos_equipo', 'marcas' => $registro->equipos()->count(),
            'tipos_componente' => $registro->componentes()->count(),
            'sistemas_operativos' => $registro->configuracionesComputo()->count(),
            'motivos_baja' => $registro->diagnosticos()->count(),
            default => 0,
        };
    }

    /** Columna "Detalle": los campos propios del catálogo, en una línea. */
    private function detalleDe(mixed $registro): string
    {
        return match ($this->listaActiva) {
            'tipos_equipo' => collect([
                self::FAMILIAS[$registro->familia] ?? $registro->familia,
                self::TIPOS_ACTIVO[$registro->tipo_activo] ?? null,
                $registro->usa_configuracion_computo ? __('con configuración de cómputo') : null,
                $registro->vida_util_meses ? __('vida útil :n meses', ['n' => $registro->vida_util_meses]) : null,
            ])->filter()->implode(' · '),
            'sistemas_operativos' => $registro->fin_soporte
                ? __('Fin de soporte: :fecha', ['fecha' => $registro->fin_soporte->format('d/m/Y')])
                : __('Con soporte vigente'),
            'tipos_componente' => $registro->obligatorio_en_computo ? __('Obligatorio en cómputo (RF-14)') : '—',
            default => '—',
        };
    }

    /** Solo los tipos de equipo tienen columna de estado (`activo`). */
    private function tieneEstado(): bool
    {
        return $this->listaActiva === 'tipos_equipo';
    }

    /**
     * Los tipos de equipo se desactivan con su columna `activo` (no se borran:
     * los equipos los referencian). Los demás catálogos planos todavía no tienen
     * columna de estado.
     */
    public function desactivar(int $id): void
    {
        if (! $this->tieneEstado()) {
            session()->flash('status', __('Este catálogo no tiene columna de estado: sus elementos no se desactivan.'));

            return;
        }

        $tipo = TipoEquipo::findOrFail($id);
        $tipo->update(['activo' => ! $tipo->activo]);

        session()->flash('status', $tipo->activo ? __('Tipo de equipo reactivado.') : __('Tipo de equipo desactivado.'));
    }

    public function render()
    {
        $conteos = collect(self::CATALOGOS)->map(fn (array $cfg) => $cfg['model']::count());

        $config = $this->config();

        $registros = $config['model']::orderBy('nombre')->get();

        $filas = $registros->map(fn ($registro) => [
            'registro' => $registro,
            'detalle' => $this->detalleDe($registro),
            'uso' => $this->usoDe($registro),
            'activo' => $this->tieneEstado() ? (bool) $registro->activo : true,
        ]);

        return view('livewire.admin.listas-panel', [
            'catalogos' => self::CATALOGOS,
            'conteos' => $conteos,
            'config' => $config,
            'filas' => $filas,
            'familias' => self::FAMILIAS,
            'tiposActivo' => self::TIPOS_ACTIVO,
            'tieneEstado' => $this->tieneEstado(),
            'usoLabel' => self::USO_LABELS[$this->listaActiva] ?? __('Uso'),
        ]);
    }
}
