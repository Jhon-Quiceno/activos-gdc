<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Equipos\Concerns\FiltrosDeEquipos;
use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\MotivoBaja;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Primera vista de Bajas, con dos pestañas (igual que TrasladosListado):
 *
 * - «Equipos»: elegir qué equipo dar de baja, con la búsqueda y los filtros del
 *   listado de Equipos (trait FiltrosDeEquipos). Nunca muestra los equipos ya
 *   dados de baja.
 * - «Bajas registradas»: historial de bajas con su estado; al elegir una se abre
 *   su panel para descargar el formato de baja y subirlo firmado (RF-25, RF-30).
 *   Con el período (desde / hasta) y los filtros, se descarga el listado en PDF
 *   y en Excel para la dependencia que tramita la baja administrativa (RF-27).
 */
class BajasListado extends Component
{
    use FiltrosDeEquipos;
    use WithPagination;

    /** 'equipos' | 'registradas' */
    #[Url]
    public string $vista = 'equipos';

    /** Búsqueda en las bajas registradas: serial, código o texto del diagnóstico. */
    #[Url(as: 'qb')]
    public string $busquedaBajas = '';

    /** '' | pendiente_de_firma | completo | anulada */
    #[Url]
    public string $estadoBaja = '';

    #[Url]
    public string $motivo = '';

    /** Período de las bajas registradas (RF-27): AAAA-MM-DD o vacío. */
    #[Url]
    public string $desde = '';

    #[Url]
    public string $hasta = '';

    /** Baja elegida en la pestaña «Bajas registradas». */
    public ?int $bajaSeleccionada = null;

    public function updated(string $propiedad): void
    {
        $this->updatedFiltrosDeEquipos($propiedad);

        if (in_array($propiedad, ['vista', 'busquedaBajas', 'estadoBaja', 'motivo', 'desde', 'hasta'], true)) {
            $this->resetPage();
            $this->bajaSeleccionada = null;
        }
    }

    public function seleccionarBaja(int $eventoId): void
    {
        $this->bajaSeleccionada = $this->bajaSeleccionada === $eventoId ? null : $eventoId;
    }

    public function render()
    {
        if ($this->vista === 'registradas') {
            return view('livewire.movimientos.bajas-listado', [
                'bajas' => $this->bajasRegistradas(),
                'motivos' => MotivoBaja::orderBy('nombre')->get(['id', 'nombre']),
            ] + $this->opcionesDeFiltros());
        }

        $equipos = Equipo::query()
            ->with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'modeloEquipo', 'asignacionActual.piso.sede', 'asignacionActual.dependencia'])
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->tap(fn (Builder $query) => $this->aplicarBusqueda($query))
            ->tap(fn (Builder $query) => $this->aplicarFiltros($query))
            ->latest('id')
            ->paginate(10);

        return view('livewire.movimientos.bajas-listado', ['equipos' => $equipos] + $this->opcionesDeFiltros());
    }

    /**
     * RF-27: listado de bajas del período y los filtros, en PDF.
     */
    public function exportarPdf(): StreamedResponse
    {
        $this->validarPeriodo();
        $bajas = $this->consultaBajas()->get();

        $pdf = Pdf::loadView('livewire.movimientos.pdf.listado-bajas', [
            'bajas' => $bajas,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'generadoPor' => auth()->user()->name,
            'generadoEl' => now(),
        ])->setPaper(FormatosPdf::PAPEL_OFICIO, 'landscape')->setOption(['defaultFont' => 'DejaVu Sans']);

        return response()->streamDownload(fn () => print ($pdf->output()), $this->nombreListado('pdf'), ['Content-Type' => 'application/pdf']);
    }

    /**
     * RF-27: el mismo listado en Excel, para trabajarlo en la dependencia que tramita la baja.
     */
    public function exportarExcel(): StreamedResponse
    {
        $this->validarPeriodo();

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet()->setTitle('Bajas');
        $hoja->fromArray(['Fecha', 'N.º de activo', 'Tipo', 'Marca y modelo', 'Serial', 'Código de activo', 'Motivo', 'Diagnóstico', 'Estado', 'Registró'], null, 'A1');
        $hoja->getStyle('A1:J1')->getFont()->setBold(true);

        $fila = 2;
        foreach ($this->consultaBajas()->get() as $baja) {
            $hoja->fromArray([
                $baja->fecha?->format('d/m/Y'),
                $baja->equipo?->numero_activo,
                $baja->equipo?->tipoEquipo?->nombre,
                trim($baja->equipo?->marca?->nombre.' '.$baja->equipo?->modeloEquipo?->nombre),
                $baja->equipo?->serial,
                $baja->equipo?->codigo_activo,
                $baja->diagnostico?->motivoBaja?->nombre,
                $baja->diagnostico?->causa,
                self::textoEstado($baja),
                $baja->usuario?->name,
            ], null, 'A'.$fila++);
        }

        foreach (range('A', 'J') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return response()->streamDownload(fn () => (new Xlsx($libro))->save('php://output'), $this->nombreListado('xlsx'));
    }

    public static function textoEstado(Evento $baja): string
    {
        return match (true) {
            $baja->anulaciones->isNotEmpty() => __('Anulada'),
            $baja->estado_firma === 'completo' => __('Dado de baja (firmada)'),
            default => __('Pendiente de firma'),
        };
    }

    private function validarPeriodo(): void
    {
        $this->validate(
            ['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', ...($this->desde !== '' ? ['after_or_equal:desde'] : [])]],
            ['hasta.after_or_equal' => __('El fin del período no puede ser antes del inicio.')],
        );
    }

    private function nombreListado(string $extension): string
    {
        $periodo = trim(($this->desde ?: 'inicio').'-a-'.($this->hasta ?: now()->toDateString()));

        return "listado-bajas-{$periodo}.{$extension}";
    }

    private function bajasRegistradas()
    {
        return $this->consultaBajas()->paginate(10);
    }

    private function consultaBajas(): Builder
    {
        $termino = trim($this->busquedaBajas);
        $normalizado = str_replace([' ', '-'], '', $termino);

        return Evento::query()
            ->where('tipo', 'baja')
            ->with(['usuario', 'equipo.tipoEquipo', 'equipo.marca', 'equipo.modeloEquipo', 'anulaciones', 'diagnostico.motivoBaja'])
            ->when($this->desde !== '', fn ($q) => $q->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta !== '', fn ($q) => $q->whereDate('fecha', '<=', $this->hasta))
            ->when($this->estadoBaja === 'anulada', fn ($q) => $q->whereHas('anulaciones'))
            ->when(in_array($this->estadoBaja, ['pendiente_de_firma', 'completo'], true), fn ($q) => $q
                ->where('estado_firma', $this->estadoBaja)
                ->whereDoesntHave('anulaciones'))
            ->when($this->motivo !== '', fn ($q) => $q->whereHas('diagnostico', fn ($d) => $d->where('motivo_baja_id', $this->motivo)))
            ->when($termino !== '', function (Builder $query) use ($termino, $normalizado) {
                $query->where(function (Builder $q) use ($termino, $normalizado) {
                    $q->where('descripcion', 'like', "%{$termino}%");

                    if ($normalizado !== '') {
                        $q->orWhereHas('equipo', fn ($e) => $e
                            ->whereRaw("REPLACE(REPLACE(serial, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"])
                            ->orWhereRaw("REPLACE(REPLACE(codigo_activo, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"]));
                    }
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }
}
