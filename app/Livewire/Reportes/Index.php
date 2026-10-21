<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Definiciones\CalidadInventario;
use App\Livewire\Reportes\Definiciones\CambiosComponentes;
use App\Livewire\Reportes\Definiciones\DadosDeBaja;
use App\Livewire\Reportes\Definiciones\GarantiasPorVencer;
use App\Livewire\Reportes\Definiciones\HistorialEquipo;
use App\Livewire\Reportes\Definiciones\InventarioGeneral;
use App\Livewire\Reportes\Definiciones\MatrizInventarioFor01;
use App\Livewire\Reportes\Definiciones\ObsolescenciaSo;
use App\Livewire\Reportes\Definiciones\PendientesFirma;
use App\Livewire\Reportes\Definiciones\PorDependencia;
use App\Livewire\Reportes\Definiciones\PorEstado;
use App\Livewire\Reportes\Definiciones\PorFuncionario;
use App\Livewire\Reportes\Definiciones\PorImportancia;
use App\Livewire\Reportes\Definiciones\PorSedePiso;
use App\Livewire\Reportes\Definiciones\PorTipoMarcaModelo;
use App\Livewire\Reportes\Definiciones\ReporteDefinicion;
use App\Livewire\Reportes\Definiciones\RequiereGestionRiesgo;
use App\Livewire\Reportes\Definiciones\SinAntivirus;
use App\Livewire\Reportes\Definiciones\Terceros;
use App\Livewire\Reportes\Definiciones\Traslados;
use App\Livewire\Reportes\Definiciones\VidaUtilSuperada;
use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Livewire\Reportes\Exportes\ReporteExport;
use App\Models\Dependencia;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\ValoracionActivo;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithPagination;

    public string $reporteActivo = 'obsolescencia_so';

    public string $filtroSede = '';

    public string $filtroDependencia = '';

    public string $filtroEstado = '';

    /** Filtro propio de cada reporte (sistema operativo, problema, motivo, acción...). */
    public string $filtroSistemaOperativo = '';

    public string $filtroPiso = '';

    public string $filtroTipo = '';

    public string $filtroMarca = '';

    public string $filtroPropiedad = '';

    public string $filtroPersona = '';

    public string $filtroVinculacion = '';

    public string $filtroProceso = '';

    public string $filtroImportancia = '';

    /** Al filtrar por dependencia, incluir o no sus subdependencias (RF-34). */
    public bool $filtroSoloDependencia = false;

    public string $filtroDesde = '';

    public string $filtroHasta = '';

    /**
     * Catálogo de reportes del prototipo "Hoja de Vida de Equipos".
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
            'descripcion' => 'Equipos cuyo sistema operativo ya no tiene soporte',
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
            'descripcion' => 'Sin serial, sin código, sin valoración, repetidos',
        ],
        'matriz_for01' => [
            'titulo' => 'Matriz de inventario (FOR.01)',
            'descripcion' => 'Formato oficial de activos de información',
        ],
        'requiere_gestion_riesgo' => [
            'titulo' => 'Requieren gestión de riesgos',
            'descripcion' => 'CID = 4 o importancia alta o muy alta',
        ],
        'por_importancia' => [
            'titulo' => 'Por importancia',
            'descripcion' => 'Activos valorados, de mayor a menor',
        ],
        'garantias_por_vencer' => [
            'titulo' => 'Garantías por vencer',
            'descripcion' => 'Equipos cuya garantía termina pronto',
        ],
        'vida_util_superada' => [
            'titulo' => 'Vida útil superada',
            'descripcion' => 'Equipos que ya cumplieron su vida útil',
        ],
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
        'dados_de_baja' => DadosDeBaja::class,
        'obsolescencia_so' => ObsolescenciaSo::class,
        'sin_antivirus' => SinAntivirus::class,
        'historial_equipo' => HistorialEquipo::class,
        'cambios_componentes' => CambiosComponentes::class,
        'traslados' => Traslados::class,
        'pendientes_firma' => PendientesFirma::class,
        'calidad_inventario' => CalidadInventario::class,
        'matriz_for01' => MatrizInventarioFor01::class,
        'requiere_gestion_riesgo' => RequiereGestionRiesgo::class,
        'por_importancia' => PorImportancia::class,
        'garantias_por_vencer' => GarantiasPorVencer::class,
        'vida_util_superada' => VidaUtilSuperada::class,
    ];

    public const ESTADOS = [
        'en_servicio' => 'En servicio',
        'sin_asignar' => 'Sin asignar',
        'dado_de_baja' => 'Dado de baja',
    ];

    public const PROPIEDADES = [
        'gobernacion' => 'Gobernación',
        'tercero' => 'Tercero',
    ];

    public function seleccionarReporte(string $clave): void
    {
        if (! array_key_exists($clave, self::REPORTES)) {
            return;
        }

        $this->reporteActivo = $clave;
        $this->limpiarFiltros();
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'filtroSede', 'filtroDependencia', 'filtroEstado', 'filtroSistemaOperativo',
            'filtroPiso', 'filtroTipo', 'filtroMarca', 'filtroPropiedad',
            'filtroPersona', 'filtroVinculacion', 'filtroProceso', 'filtroImportancia',
            'filtroSoloDependencia', 'filtroDesde', 'filtroHasta',
        ]);
        $this->resetPage();
    }

    /** Al cambiar cualquier filtro, volver a la página 1. */
    public function updated(string $propiedad): void
    {
        if (str_starts_with($propiedad, 'filtro')) {
            $this->resetPage();
        }
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
            'piso' => $this->filtroPiso,
            'dependencia' => $this->filtroDependencia,
            'estado' => $this->filtroEstado,
            'tipo' => $this->filtroTipo,
            'marca' => $this->filtroMarca,
            'propiedad' => $this->filtroPropiedad,
            'persona' => $this->filtroPersona,
            'vinculacion' => $this->filtroVinculacion,
            'proceso' => $this->filtroProceso,
            'importancia' => $this->filtroImportancia,
            'solo_dependencia' => $this->filtroSoloDependencia,
            'desde' => $this->filtroDesde,
            'hasta' => $this->filtroHasta,
            'especifico' => $this->filtroSistemaOperativo,
        ];
    }

    public function exportarExcel()
    {
        $definicion = $this->definicion();

        if (! $definicion) {
            return null;
        }

        // La matriz FOR.01 se exporta en el formato oficial (encabezado y grupos),
        // no con el export genérico de reportes.
        if ($this->reporteActivo === 'matriz_for01') {
            return Excel::download(
                new MatrizFor01($definicion->consulta($this->filtros())->get()),
                'matriz-inventario-for01.xlsx'
            );
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
            'propiedades' => self::PROPIEDADES,
            'etiquetaFecha' => $definicion?->etiquetaFecha() ?? 'Registrado',
            'filtroEspecifico' => $definicion?->filtroEspecifico(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'pisos' => Piso::orderBy('numero')->get(),
            'dependencias' => Dependencia::orderBy('nombre')->get(),
            'procesos' => Proceso::orderBy('nombre')->get(),
            'importancias' => ValoracionActivo::IMPORTANCIAS,
            'tipos' => TipoEquipo::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'personas' => Persona::orderBy('apellidos')->orderBy('nombres')->get(),
            'vinculaciones' => Persona::query()
                ->whereNotNull('tipo_vinculacion')
                ->distinct()
                ->orderBy('tipo_vinculacion')
                ->pluck('tipo_vinculacion'),
            // La vista de obsolescencia lista los sistemas sin soporte por su fecha real de fin de soporte.
            'sistemasOperativosObsoletos' => ObsolescenciaSo::sistemasSinSoporte(),
        ]);
    }
}