<?php

namespace App\Livewire\Reportes;

use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function render()
    {
        $equipos = null;

        if ($this->reporteActivo === 'obsolescencia_so') {
            $equipos = Equipo::query()
                ->with([
                    'tipoEquipo',
                    'configuracionComputo.sistemaOperativo',
                    'asignacionActual.persona',
                    'asignacionActual.dependencia',
                ])
                ->whereHas('configuracionComputo.sistemaOperativo', function ($query) {
                    $query->where('nombre', 'like', '%7%')
                        ->orWhere('nombre', 'like', '%8%');
                })
                ->when($this->filtroSistemaOperativo !== '', function ($query) {
                    $query->whereHas('configuracionComputo.sistemaOperativo', function ($so) {
                        $so->where('id', $this->filtroSistemaOperativo);
                    });
                })
                ->when($this->filtroSede !== '', function ($query) {
                    $query->whereHas('asignacionActual', function ($asignacion) {
                        $asignacion->where('sede_id', $this->filtroSede);
                    });
                })
                ->when($this->filtroDependencia !== '', function ($query) {
                    $query->whereHas('asignacionActual', function ($asignacion) {
                        $asignacion->where('dependencia_id', $this->filtroDependencia);
                    });
                })
                ->when($this->filtroEstado !== '', function ($query) {
                    $query->where('estado_ciclo_vida', $this->filtroEstado);
                })
                ->latest('id')
                ->paginate(10);
        }

        return view('livewire.reportes.index', [
            'equipos' => $equipos,
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
