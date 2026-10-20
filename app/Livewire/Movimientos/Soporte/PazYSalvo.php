<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Persona;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Support\Collection;

/**
 * Paz y salvo de equipos (RF-23): constancia de los equipos que una persona
 * tiene a cargo, para tramitar su retiro o el fin de su contrato. Si no tiene
 * ninguno, el documento dice que está a paz y salvo; si tiene, los lista para
 * que se trasladen antes de firmar.
 *
 * Además avisa de quienes terminan su vinculación pronto y todavía tienen
 * equipos (personas.fecha_fin_vinculacion).
 */
class PazYSalvo
{
    /** Días de anticipación del aviso. */
    public const DIAS_AVISO = 30;

    public function __construct(private GestorAsignaciones $asignaciones) {}

    public function pdf(Persona $persona): DocumentoPdf
    {
        $persona->loadMissing(['dependencia', 'cargoOficial']);
        $equipos = $this->asignaciones->equiposACargo($persona);

        return Pdf::loadView('livewire.movimientos.pdf.paz-y-salvo', [
            'persona' => $persona,
            'equipos' => $equipos,
            'consecutivo' => 'PS-'.str_pad((string) $persona->id, 5, '0', STR_PAD_LEFT).'-'.now()->format('Ymd'),
            'fecha' => now(),
            'tecnico' => auth()->user(),
        ])->setPaper(FormatosPdf::PAPEL_OFICIO, 'portrait')->setOption(['defaultFont' => 'DejaVu Sans']);
    }

    /**
     * Personas cuya vinculación termina en los próximos DIAS_AVISO días (o ya
     * terminó) y que todavía tienen equipos a cargo.
     *
     * @return Collection<int, Persona>
     */
    public function porTerminarConEquipos(): Collection
    {
        return Persona::query()
            ->with(['dependencia', 'cargoOficial'])
            ->whereNotNull('fecha_fin_vinculacion')
            ->whereDate('fecha_fin_vinculacion', '<=', now()->addDays(self::DIAS_AVISO))
            ->whereHas('asignaciones', fn ($a) => $a->whereNull('fecha_fin')
                ->whereHas('equipo', fn ($e) => $e->where('estado_ciclo_vida', '!=', 'dado_de_baja')))
            ->withCount(['asignaciones as equipos_a_cargo' => fn ($a) => $a->whereNull('fecha_fin')
                ->whereHas('equipo', fn ($e) => $e->where('estado_ciclo_vida', '!=', 'dado_de_baja'))])
            ->orderBy('fecha_fin_vinculacion')
            ->get();
    }
}
