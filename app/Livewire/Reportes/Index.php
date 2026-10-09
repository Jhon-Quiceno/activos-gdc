<?php

namespace App\Livewire\Reportes;

use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Reportes\Definiciones\InventarioGeneral;
use App\Livewire\Reportes\Definiciones\ObsolescenciaSo;
use App\Livewire\Reportes\Definiciones\ReporteDefinicion;
use App\Livewire\Reportes\Exportes\ReporteExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Livewire\Reportes\Definiciones\PorDependencia;
use App\Livewire\Reportes\Definiciones\PorEstado;
use App\Livewire\Reportes\Definiciones\PorFuncionario;
use App\Livewire\Reportes\Definiciones\PorSedePiso;
use App\Livewire\Reportes\Definiciones\PorTipoMarcaModelo;
use App\Livewire\Reportes\Definiciones\Terceros;

class Index extends Component
{
    use WithPagination;

    public string $reporteActivo = 'obsolescencia_so';

    public string $filtroSede = '';

    public string $filtroDependencia = '';

    public string $filtroEstado = '';

    public string $filtroSistemaOperativo = '';

    /**
     * Catálogo de los 15 reportes del prototipo "Hoja de Vida de Equipos".
     * Solo "obsolescencia_so" consulta datos reales por ahora; los demás
     * dejan el patrón (título + descripción + tabla vacía) para que el
     * bloque de Reportes los implemente uno a uno.
     */
    public const REPORTES = [
        'inventario_general' => [
            'titulo' => 'Inventario general',
            'descripcion' => 'Todos los equipos con sus datos',
        ],
        'por_dependencia' => [
            'titulo' => 'Por dependencia',
            'descripcion' => 'Qué tiene cada dependencia',
        ],
        'por_sede_piso' => [
            'titulo' => 'Por sede y piso',
            'descripcion' => 'Qué hay en cada edificio',
        ],
        'por_funcionario' => [
            'titulo' => 'Por funcionario',
            'descripcion' => 'Equipos a cargo de cada persona',
        ],
        'por_tipo_marca_modelo' => [
            'titulo' => 'Por tipo, marca y modelo',
            'descripcion' => 'Composición del parque',
        ],
        'por_estado' => [
            'titulo' => 'Por estado',
            'descripcion' => 'En servicio, sin asignar, dados de baja',
        ],
        'terceros' => [
            'titulo' => 'Equipos de terceros',
            'descripcion' => 'Qué no es de la Gobernación y de quién es',
        ],
        'dados_de_baja' => [
            'titulo' => 'Dados de baja',
            'descripcion' => 'Qué salió de servicio y por qué',
        ],
        'obsolescencia_so' => [
            'titulo' => 'Obsolescencia por sistema operativo',
            'descripcion' => 'Equipos con Windows 7 u 8',
        ],
        'sin_antivirus' => [
            'titulo' => 'Sin antivirus',
            'descripcion' => 'Equipos sin protección',
        ],
        'historial_equipo' => [
            'titulo' => 'Historial por equipo',
            'descripcion' => 'Todo lo que se le ha hecho',
        ],
        'cambios_componentes' => [
            'titulo' => 'Cambios de componentes',
            'descripcion' => 'Qué se agregó, cambió o quitó',
        ],
        'traslados' => [
            'titulo' => 'Traslados',
            'descripcion' => 'Qué se movió y a dónde',
        ],
        'pendientes_firma' => [
            'titulo' => 'Pendientes de firma',
            'descripcion' => 'Movimientos sin documentos firmados',
        ],
        'calidad_inventario' => [
            'titulo' => 'Calidad del inventario',
            'descripcion' => 'Sin serial, sin código, repetidos',
        ],
    ];

    public const ESTADOS = [
        'en_servicio' => 'En servicio',
        'sin_asignar' => 'Sin asignar',
        'dado_de_baja' => 'Dado de baja',
    ];

        /** Reportes con datos reales: clave => clase de definición. */
    public const DEFINICIONES = [
        'inventario_general' => InventarioGeneral::class,
        'por_dependencia' => PorDependencia::class,
        'por_sede_piso' => PorSedePiso::class,
        'por_funcionario' => PorFuncionario::class,
        'por_tipo_marca_modelo' => PorTipoMarcaModelo::class,
        'por_estado' => PorEstado::class,
        'terceros' => Terceros::class,
        'obsolescencia_so' => ObsolescenciaSo::class,
    ];

    public function seleccionarReporte(string $clave): void
    {
        if (! array_key_exists($clave, self::REPORTES)) {
            return;
        }

        $this->reporteActivo = $clave;
        $this->filtroSede = '';
        $this->filtroDependencia = '';
        $this->filtroEstado = '';
        $this->filtroSistemaOperativo = '';
        $this->resetPage();
    }

    public function updatingFiltroSede(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroDependencia(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroSistemaOperativo(): void
    {
        $this->resetPage();
    }

        protected function definicion(): ?ReporteDefinicion
    {
        $clase = self::DEFINICIONES[$this->reporteActivo] ?? null;

        return $clase ? new $clase : null;
    }

    protected function filtros(): array
    {
        return [
            'sede' => $this->filtroSede,
            'dependencia' => $this->filtroDependencia,
            'estado' => $this->filtroEstado,
            'especifico' => $this->filtroSistemaOperativo,
        ];
    }

    public function exportarExcel()
    {
        $definicion = $this->definicion();

        if (! $definicion) {
            return null;
        }

        return Excel::download(
            new ReporteExport($definicion->filas($this->filtros()), array_keys($definicion->columnas())),
            str($definicion->titulo())->slug() . '.xlsx'
        );
    }

    public function exportarPdf()
    {
        $definicion = $this->definicion();

        if (! $definicion) {
            return null;
        }

        $pdf = Pdf::loadView('livewire.reportes.pdf.reporte', [
            'titulo' => $definicion->titulo(),
            'encabezados' => array_keys($definicion->columnas()),
            'filas' => $definicion->filas($this->filtros()),
        ])->setPaper('letter', 'landscape'); // RNF-13: tamaño carta

        return response()->streamDownload(
            fn () => print($pdf->output()),
            str($definicion->titulo())->slug() . '.pdf'
        );
    }

    public function render()
    {
        $definicion = $this->definicion();

        return view('livewire.reportes.index', [
            'registros' => $definicion?->consulta($this->filtros())->paginate(10),
            'encabezados' => $definicion ? array_keys($definicion->columnas()) : [],
            'columnas' => $definicion?->columnas() ?? [],
            'reportes' => self::REPORTES,
            'estados' => self::ESTADOS,
            'sedes' => Sede::orderBy('nombre')->get(),
            'dependencias' => Dependencia::orderBy('nombre')->get(),
            'sistemasOperativosObsoletos' => SistemaOperativo::query()
                ->where('nombre', 'like', '%7%')
                ->orWhere('nombre', 'like', '%8%')
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
