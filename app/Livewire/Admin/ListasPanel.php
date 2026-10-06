<?php

namespace App\Livewire\Admin;

use App\Models\Dependencia;
use App\Models\Marca;
use App\Models\MotivoBaja;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Listas" (Manuel, bloque de Administración): patrón maestro-detalle
 * sobre los 8 catálogos simples de la app. El prototipo también menciona
 * "Tipos de evento", pero eso es el enum fijo `Evento::tipo` (no una tabla), así
 * que se omite aquí a propósito (ver CLAUDE.md de la tarea).
 *
 * Ninguno de estos catálogos tiene columna `activo` todavía, así que "Desactivar"
 * queda como TODO (RN pendiente de columna de estado); "Editar" sí persiste de
 * verdad, y "Agregar" también.
 */
class ListasPanel extends Component
{
    public string $listaActiva = 'sedes';

    public string $nuevoValor = '';

    public string $nuevaFamilia = '';

    public ?int $editandoId = null;

    public string $editandoValor = '';

    public string $editandoFamilia = '';

    /**
     * @var array<string, array{label: string, singular: string, model: class-string, campo: string}>
     */
    private const CATALOGOS = [
        'sedes' => ['label' => 'Sedes', 'singular' => 'Sede', 'model' => Sede::class, 'campo' => 'nombre'],
        'pisos' => ['label' => 'Pisos', 'singular' => 'Piso', 'model' => Piso::class, 'campo' => 'numero'],
        'dependencias' => ['label' => 'Dependencias', 'singular' => 'Dependencia', 'model' => Dependencia::class, 'campo' => 'nombre'],
        'tipos_equipo' => ['label' => 'Tipos de equipo', 'singular' => 'Tipo de equipo', 'model' => TipoEquipo::class, 'campo' => 'nombre'],
        'tipos_componente' => ['label' => 'Tipos de componente', 'singular' => 'Tipo de componente', 'model' => TipoComponente::class, 'campo' => 'nombre'],
        'marcas' => ['label' => 'Marcas', 'singular' => 'Marca', 'model' => Marca::class, 'campo' => 'nombre'],
        'sistemas_operativos' => ['label' => 'Sistemas operativos', 'singular' => 'Sistema operativo', 'model' => SistemaOperativo::class, 'campo' => 'nombre'],
        'motivos_baja' => ['label' => 'Motivos de baja', 'singular' => 'Motivo de baja', 'model' => MotivoBaja::class, 'campo' => 'nombre'],
    ];

    /**
     * Etiquetas de `tipos_equipo.familia` (mismo enum que App\Livewire\Equipos\Crear).
     * La columna es NOT NULL en BD, así que el catálogo "Tipos de equipo" es el
     * único que necesita este campo extra tanto al agregar como al editar.
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
    ];

    /**
     * Etiqueta de la columna de "uso" por catálogo, tal como aparece en
     * Listas.dc.html (prototipo real, no la paráfrasis de la tarea): Sedes,
     * Pisos y Dependencias miden puestos de inventario; Tipos de equipo mide
     * equipos; el resto usa la etiqueta genérica "Uso".
     *
     * @var array<string, string>
     */
    private const USO_LABELS = [
        'sedes' => 'Puestos (inventario)',
        'pisos' => 'Puestos (inventario)',
        'dependencias' => 'Puestos (inventario)',
        'tipos_equipo' => 'Equipos',
        'tipos_componente' => 'Uso',
        'marcas' => 'Uso',
        'sistemas_operativos' => 'Uso',
        'motivos_baja' => 'Uso',
    ];

    public function seleccionarLista(string $clave): void
    {
        if (! array_key_exists($clave, self::CATALOGOS)) {
            return;
        }

        $this->listaActiva = $clave;
        $this->reset(['nuevoValor', 'nuevaFamilia']);
        $this->resetErrorBag();
    }

    /**
     * @return array{label: string, singular: string, model: class-string, campo: string}
     */
    private function config(): array
    {
        return self::CATALOGOS[$this->listaActiva];
    }

    public function agregar(): void
    {
        $config = $this->config();
        $campo = $config['campo'];
        $tabla = (new $config['model'])->getTable();

        $rules = [
            'nuevoValor' => $campo === 'numero'
                ? ['required', 'integer', Rule::unique($tabla, 'numero')]
                : ['required', 'string', 'max:255', Rule::unique($tabla, 'nombre')],
        ];

        if ($this->listaActiva === 'tipos_equipo') {
            $rules['nuevaFamilia'] = ['required', 'in:'.implode(',', array_keys(self::FAMILIAS))];
        }

        $this->validate($rules);

        $datos = [$campo => $this->nuevoValor];

        if ($this->listaActiva === 'tipos_equipo') {
            $datos['familia'] = $this->nuevaFamilia;
        }

        $config['model']::create($datos);

        $this->reset(['nuevoValor', 'nuevaFamilia']);

        session()->flash('status', __(':singular agregado correctamente.', ['singular' => $config['singular']]));
    }

    public function abrirEdicion(int $id): void
    {
        $config = $this->config();
        $registro = $config['model']::findOrFail($id);

        $this->editandoId = $registro->id;
        $this->editandoValor = (string) $registro->{$config['campo']};
        $this->editandoFamilia = $this->listaActiva === 'tipos_equipo' ? (string) $registro->familia : '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'editar-item');
    }

    public function guardarEdicion(): void
    {
        $config = $this->config();
        $campo = $config['campo'];
        $tabla = (new $config['model'])->getTable();

        $rules = [
            'editandoValor' => $campo === 'numero'
                ? ['required', 'integer', Rule::unique($tabla, 'numero')->ignore($this->editandoId)]
                : ['required', 'string', 'max:255', Rule::unique($tabla, 'nombre')->ignore($this->editandoId)],
        ];

        if ($this->listaActiva === 'tipos_equipo') {
            $rules['editandoFamilia'] = ['required', 'in:'.implode(',', array_keys(self::FAMILIAS))];
        }

        $this->validate($rules);

        $registro = $config['model']::findOrFail($this->editandoId);
        $datos = [$campo => $this->editandoValor];

        if ($this->listaActiva === 'tipos_equipo') {
            $datos['familia'] = $this->editandoFamilia;
        }

        $registro->update($datos);

        $this->dispatch('close-modal', 'editar-item');

        session()->flash('status', __(':singular actualizado correctamente.', ['singular' => $config['singular']]));
    }

    /**
     * Conteo real de "uso" por fila, según el catálogo activo. Cada rama usa
     * la relación que mejor representa el uso de ese registro en el
     * inventario; si no hay una relación directa y confiable, no se añade
     * ninguna rama y la fila cae al '—' calculado en detalleUsoDe().
     */
    private function usoDe(mixed $registro): int
    {
        return match ($this->listaActiva) {
            'sedes', 'pisos', 'dependencias' => $registro->asignaciones()->whereNull('fecha_fin')->count(),
            'tipos_equipo', 'marcas' => $registro->equipos()->count(),
            'tipos_componente' => $registro->componentes()->count(),
            'sistemas_operativos' => $registro->configuracionesComputo()->count(),
            'motivos_baja' => $registro->diagnosticos()->count(),
            default => 0,
        };
    }

    /**
     * Columna "Detalle": solo Tipos de equipo tiene un dato real adicional
     * (la familia). Los demás catálogos solo tienen `nombre` (o `numero` en
     * Pisos), así que muestran '—' en vez de inventar variantes o notas que
     * no existen en la BD.
     */
    private function detalleDe(mixed $registro): string
    {
        if ($this->listaActiva === 'tipos_equipo') {
            return __('Familia').': '.(self::FAMILIAS[$registro->familia] ?? $registro->familia);
        }

        return '—';
    }

    /**
     * TODO: estos catálogos todavía no tienen columna `activo`/estado en BD
     * (fuera del alcance de esta tarea: no se tocan migraciones). Cuando exista,
     * este método debe alternarla igual que UsuariosPanel::alternarActivo().
     */
    public function desactivar(int $id): void
    {
        session()->flash('status', __('Función pendiente: este catálogo todavía no tiene columna de estado para desactivar elementos.'));
    }

    public function render()
    {
        $conteos = collect(self::CATALOGOS)->map(fn (array $cfg) => $cfg['model']::count());

        $config = $this->config();

        $registros = $config['model']::orderBy($config['campo'])->get();

        $filas = $registros->map(fn ($registro) => [
            'registro' => $registro,
            'detalle' => $this->detalleDe($registro),
            'uso' => $this->usoDe($registro),
        ]);

        return view('livewire.admin.listas-panel', [
            'catalogos' => self::CATALOGOS,
            'conteos' => $conteos,
            'config' => $config,
            'filas' => $filas,
            'familias' => self::FAMILIAS,
            'usoLabel' => self::USO_LABELS[$this->listaActiva] ?? __('Uso'),
        ]);
    }
}
