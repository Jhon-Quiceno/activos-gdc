<?php

namespace App\Livewire\Levantamientos\Soporte;

use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\LevantamientoActivos;
use App\Models\User;
use App\Services\HistorialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Levantamiento de activos de una dependencia (RF-59, CU-18, GT-M4 4.1):
 *
 * 1. Se crea por dependencia, proceso y sede.
 * 2. Lista los equipos de la dependencia y sus subdependencias en esa sede.
 * 3. Cada equipo se verifica en sitio y se valora (ValoracionActivo, enlazada
 *    al levantamiento).
 * 4. Se genera el acta de identificación y aceptación prellenada; el
 *    levantamiento pasa a «Pendiente de firma».
 * 5. Firman el propietario y el custodio; al subir el acta firmada queda
 *    «Aceptado». Sin acta firmada nunca queda aceptado.
 */
class GestorLevantamientos
{
    public const DISCO = 'local';

    public function __construct(private HistorialService $historial) {}

    /**
     * @param  array{dependencia_id: int, proceso_id: ?int, sede_id: int, propietario_persona_id: ?int, custodio_persona_id: ?int, fecha_inicio?: string}  $datos
     */
    public function crear(array $datos, User $usuario): LevantamientoActivos
    {
        return DB::transaction(function () use ($datos, $usuario) {
            $anio = now()->year;
            // Bloqueo para que dos levantamientos creados a la vez no tomen el mismo consecutivo.
            $ultimo = LevantamientoActivos::where('codigo', 'like', "LEV-{$anio}-%")->lockForUpdate()->count();

            return LevantamientoActivos::create($datos + [
                'codigo' => sprintf('LEV-%d-%03d', $anio, $ultimo + 1),
                'proceso_id' => $datos['proceso_id'] ?? Dependencia::query()->whereKey($datos['dependencia_id'])->value('proceso_id'),
                'fecha_inicio' => $datos['fecha_inicio'] ?? now()->toDateString(),
                'estado' => 'en_curso',
                'usuario_id' => $usuario->id,
            ]);
        });
    }

    /**
     * Equipos del levantamiento: asignados hoy a la dependencia o a cualquiera
     * de sus subdependencias, en un piso de la sede del levantamiento, y que no
     * estén dados de baja.
     */
    public function equipos(LevantamientoActivos $levantamiento): Builder
    {
        $rama = Dependencia::idsDeRama($levantamiento->dependencia_id);

        return Equipo::query()
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->whereHas('asignacionActual', fn ($a) => $a
                ->whereIn('dependencia_id', $rama)
                ->whereHas('piso', fn ($p) => $p->where('pisos.sede_id', $levantamiento->sede_id)));
    }

    /**
     * Verificación en sitio (paso 3): el técnico confirma serial y estado.
     * Queda en la hoja de vida con el valor anterior y el nuevo (RF-12).
     */
    public function verificar(LevantamientoActivos $levantamiento, Equipo $equipo, User $usuario): void
    {
        $this->asegurarEnCurso($levantamiento);

        if (! $this->equipos($levantamiento)->whereKey($equipo->id)->exists()) {
            throw ValidationException::withMessages(['equipo' => __('El equipo no pertenece a este levantamiento.')]);
        }

        if ($equipo->verificacion === 'verificado') {
            return;
        }

        DB::transaction(function () use ($levantamiento, $equipo, $usuario) {
            $equipo->update(['verificacion' => 'verificado']);

            $this->historial->registrar(
                equipo: $equipo,
                tipo: 'actualizacion_datos',
                usuario: $usuario,
                datos: ['valores' => [
                    'antes' => ['verificacion' => 'Pendiente de verificar'],
                    'despues' => ['verificacion' => 'Verificado'],
                ]],
                descripcion: __('Verificado en sitio en el levantamiento :codigo.', ['codigo' => $levantamiento->codigo]),
            );
        });
    }

    /**
     * Acta de identificación y aceptación prellenada (paso 4). Generarla deja
     * el levantamiento «Pendiente de firma».
     */
    public function acta(LevantamientoActivos $levantamiento, User $usuario): DocumentoPdf
    {
        if ($levantamiento->estado === 'aceptado') {
            throw ValidationException::withMessages(['acta' => __('El levantamiento ya fue aceptado.')]);
        }

        $equipos = $this->equipos($levantamiento)
            ->with(['tipoEquipo', 'marca', 'modeloEquipo', 'valoracionVigente', 'asignacionActual.piso', 'asignacionActual.dependencia'])
            ->orderBy('numero_activo')
            ->get();

        if ($equipos->isEmpty()) {
            throw ValidationException::withMessages(['acta' => __('El levantamiento no tiene equipos: no hay nada que aceptar.')]);
        }

        $levantamiento->update(['estado' => 'pendiente_de_firma']);

        return Pdf::loadView('livewire.levantamientos.pdf.acta', [
            'levantamiento' => $levantamiento->load(['dependencia', 'proceso', 'sede', 'propietario.cargoOficial', 'custodio.cargoOficial']),
            'equipos' => $equipos,
            'tecnico' => $usuario,
            'fecha' => now(),
        ])->setPaper(FormatosPdf::PAPEL_OFICIO, 'landscape')->setOption(['defaultFont' => 'DejaVu Sans']);
    }

    /**
     * Sube el acta firmada (paso 5): el levantamiento queda «Aceptado».
     */
    public function aceptar(LevantamientoActivos $levantamiento, UploadedFile $actaFirmada, User $usuario): void
    {
        if ($levantamiento->estado !== 'pendiente_de_firma') {
            throw ValidationException::withMessages(['actaFirmada' => __('Primero genera el acta prellenada para que la firmen.')]);
        }

        $ruta = $actaFirmada->storeAs("levantamientos/{$levantamiento->id}", 'acta-firmada.pdf', self::DISCO);

        $levantamiento->update([
            'acta_path' => $ruta,
            'estado' => 'aceptado',
            'fecha_aceptacion' => now()->toDateString(),
        ]);
    }

    public function actaFirmadaExiste(LevantamientoActivos $levantamiento): bool
    {
        return $levantamiento->acta_path !== null && Storage::disk(self::DISCO)->exists($levantamiento->acta_path);
    }

    private function asegurarEnCurso(LevantamientoActivos $levantamiento): void
    {
        if ($levantamiento->estado === 'aceptado') {
            throw ValidationException::withMessages(['equipo' => __('El levantamiento ya fue aceptado: no admite cambios.')]);
        }
    }
}
