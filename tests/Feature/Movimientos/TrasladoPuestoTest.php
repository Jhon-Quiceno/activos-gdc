<?php

use App\Livewire\Movimientos\DocumentosEvento;
use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\TrasladoForm;
use App\Models\Asignacion;
use App\Models\Diagnostico;
use App\Models\Documento;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\MotivoBaja;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Services\HistorialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/*
 * RF-05 (parte de Movimientos): traslado del puesto de trabajo completo.
 * La asignación de un equipo a un puesto es la asignación abierta con
 * puesto_trabajo_id (F3-1); aquí se arma directamente sobre esa fila.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);
});

/**
 * Puesto en el piso de Apoyo::equipo() con $cantidad equipos de la misma persona.
 *
 * @return array{0: PuestoTrabajo, 1: Collection<int, Equipo>, 2: Persona}
 */
function puestoConEquipos(int $cantidad = 3, ?Persona $persona = null): array
{
    $persona ??= Apoyo::persona(['nombres' => 'Ana', 'apellidos' => 'Origen']);
    $puesto = PuestoTrabajo::create([
        'nombre_o_codigo' => 'PT-PRUEBA-'.PuestoTrabajo::count(),
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $persona->dependencia_id,
    ]);

    $equipos = collect(range(1, $cantidad))->map(function () use ($persona, $puesto) {
        $equipo = Apoyo::equipo($persona);
        Asignacion::where('equipo_id', $equipo->id)->whereNull('fecha_fin')->update(['puesto_trabajo_id' => $puesto->id]);

        return $equipo->fresh();
    });

    return [$puesto, $equipos, $persona];
}

function datosTrasladoPuesto(?Persona $persona, array $extra = []): array
{
    return array_merge([
        'persona' => $persona,
        'piso_id' => Piso::query()->orderByDesc('id')->value('id'),
        'dependencia_id' => $persona?->dependencia_id,
        'puesto_destino_id' => null,
        'fecha' => now()->toDateString(),
        'motivo' => 'Cambio de funcionario del puesto',
    ], $extra);
}

/** Páginas de un PDF de dompdf (objetos /Type /Page, sin contar /Pages). */
function paginasPdf(string $contenido): int
{
    return preg_match_all('#/Type\s*/Page(?!s)#', $contenido);
}

it('trasladar un puesto genera un solo juego de formatos para todos sus equipos, no uno por equipo', function () {
    [$puesto, $equipos] = puestoConEquipos(3);
    $nueva = Apoyo::persona(['nombres' => 'Nicolás', 'apellidos' => 'Destino']);

    $eventos = app(RegistroMovimientos::class)->trasladarPuesto($puesto, $this->usuario, datosTrasladoPuesto($nueva));

    // Un evento por equipo, cada uno en su hoja de vida.
    expect($eventos)->toHaveCount(3)
        ->and($eventos->pluck('equipo_id')->sort()->values()->all())->toBe($equipos->pluck('id')->sort()->values()->all())
        ->and($eventos->every(fn (Evento $e) => $e->tipo === 'traslado_responsable' && $e->estado_firma === 'pendiente_de_firma'))->toBeTrue();

    // Pero un solo juego: dos archivos (retiro y entrega), no seis.
    $archivos = Storage::disk('local')->allFiles();
    expect($archivos)->toHaveCount(2);

    $documentos = Documento::whereIn('evento_id', $eventos->pluck('id'))->get();
    expect($documentos)->toHaveCount(6)
        ->and($documentos->pluck('archivo_path')->unique()->values()->all())->toEqualCanonicalizing($archivos)
        ->and($documentos->pluck('consecutivo')->unique()->sort()->values()->all())->toBe([
            FormatosPdf::consecutivo($eventos->first(), 'formato_baja'),
            FormatosPdf::consecutivo($eventos->first(), 'formato_entrega'),
        ]);

    // Cada formato trae una hoja por equipo del puesto.
    foreach ($archivos as $archivo) {
        $pdf = Storage::disk('local')->get($archivo);
        expect($pdf)->toStartWith('%PDF')
            ->and(paginasPdf($pdf))->toBe(3);
    }
});

it('reasigna todos los equipos del puesto al nuevo responsable y deja intactos los de otros puestos', function () {
    [$puesto, $equipos] = puestoConEquipos(2);
    [, $otros] = puestoConEquipos(1);
    $nueva = Apoyo::persona();
    $piso = Piso::query()->orderByDesc('id')->first();
    $destino = PuestoTrabajo::create(['nombre_o_codigo' => 'PT-DESTINO', 'piso_id' => $piso->id, 'dependencia_id' => $nueva->dependencia_id]);

    $eventos = app(RegistroMovimientos::class)->trasladarPuesto($puesto, $this->usuario, datosTrasladoPuesto($nueva, [
        'puesto_destino_id' => $destino->id,
    ]));

    foreach ($equipos as $equipo) {
        $abiertas = Asignacion::where('equipo_id', $equipo->id)->whereNull('fecha_fin')->get();
        expect($abiertas)->toHaveCount(1)
            ->and($abiertas->first()->persona_id)->toBe($nueva->id)
            ->and($abiertas->first()->piso_id)->toBe($piso->id)
            ->and($abiertas->first()->puesto_trabajo_id)->toBe($destino->id)
            ->and($abiertas->first()->evento_origen_id)->toBe($eventos->firstWhere('equipo_id', $equipo->id)->id);
    }

    expect($otros->first()->fresh()->asignacionActual->puesto_trabajo_id)->not->toBe($destino->id)
        ->and(Evento::where('equipo_id', $otros->first()->id)->where('tipo', 'traslado_responsable')->count())->toBe(0)
        ->and($eventos->first()->descripcion)->toContain($puesto->nombre_o_codigo)
        ->and($eventos->first()->descripcion)->toContain('Motivo: Cambio de funcionario del puesto');
});

it('subir los firmados una sola vez completa el traslado de todos los equipos del puesto', function () {
    [$puesto] = puestoConEquipos(3);
    $eventos = app(RegistroMovimientos::class)->trasladarPuesto($puesto, $this->usuario, datosTrasladoPuesto(Apoyo::persona()));
    $firmas = app(GestorFirmas::class);

    $firmas->subirFirmado($eventos->first(), 'formato_baja', Apoyo::pdfFirmado('retiro.pdf'));

    expect(Evento::whereIn('id', $eventos->pluck('id'))->pluck('estado_firma')->unique()->all())->toBe(['pendiente_de_firma']);

    $firmas->subirFirmado($eventos->last(), 'formato_entrega', Apoyo::pdfFirmado('entrega.pdf'));

    expect(Evento::whereIn('id', $eventos->pluck('id'))->pluck('estado_firma')->unique()->all())->toBe(['completo'])
        // Cada firmado se subió una vez: un archivo por formato, un documento por evento.
        ->and(Documento::whereIn('evento_id', $eventos->pluck('id'))->where('firmado', true)->count())->toBe(6)
        ->and(Documento::whereIn('evento_id', $eventos->pluck('id'))->where('firmado', true)->distinct()->pluck('archivo_path'))->toHaveCount(2);
});

it('si un equipo del puesto tiene una baja en trámite no se traslada ninguno', function () {
    [$puesto, $equipos] = puestoConEquipos(2);
    $conBaja = $equipos->last();

    $baja = app(HistorialService::class)->registrar($conBaja, 'baja', $this->usuario, [
        'estado_firma' => 'pendiente_de_firma',
    ], 'Baja en trámite');
    Diagnostico::create([
        'evento_id' => $baja->id,
        'estado_encontrado' => 'No enciende',
        'causa' => 'Tarjeta madre',
        'es_baja' => true,
        'motivo_baja_id' => MotivoBaja::query()->value('id'),
    ]);
    Documento::create(['evento_id' => $baja->id, 'tipo' => 'formato_baja', 'archivo_path' => 'x.pdf', 'firmado' => false, 'fecha' => now()]);

    expect(fn () => app(RegistroMovimientos::class)->trasladarPuesto($puesto, $this->usuario, datosTrasladoPuesto(Apoyo::persona())))
        ->toThrow(ValidationException::class);

    expect(Evento::whereIn('equipo_id', $equipos->pluck('id'))->where('tipo', 'traslado_responsable')->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('la pantalla ofrece trasladar el puesto completo y lo registra con un solo juego de formatos', function () {
    [$puesto, $equipos] = puestoConEquipos(2);
    $nueva = Apoyo::persona();

    Livewire::test(TrasladoForm::class, ['equipo' => $equipos->first()])
        ->assertSee('Trasladar el puesto completo (2 equipos)')
        ->assertSee($puesto->nombre_o_codigo)
        ->set('trasladarPuesto', true)
        ->set('personaId', $nueva->id)
        ->set('dependenciaId', $nueva->dependencia_id)
        ->set('puestoDestinoId', $puesto->id)
        ->set('motivo', 'Nuevo funcionario en el puesto')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSet('equiposTrasladados', 2)
        ->assertSee('Traslado del puesto registrado');

    foreach ($equipos as $equipo) {
        expect($equipo->fresh()->asignacionActual->persona_id)->toBe($nueva->id)
            ->and($equipo->fresh()->asignacionActual->puesto_trabajo_id)->toBe($puesto->id);
    }

    expect(Storage::disk('local')->allFiles())->toHaveCount(2);
});

it('el puesto destino tiene que ser del piso destino', function () {
    [$puesto, $equipos] = puestoConEquipos(1);
    $otroPiso = Piso::query()->where('id', '!=', $puesto->piso_id)->first();
    $nueva = Apoyo::persona();

    Livewire::test(TrasladoForm::class, ['equipo' => $equipos->first()])
        ->set('trasladarPuesto', true)
        ->set('personaId', $nueva->id)
        ->set('sedeId', $otroPiso->sede_id)
        ->set('pisoId', $otroPiso->id)
        ->set('dependenciaId', $nueva->dependencia_id)
        ->set('puestoDestinoId', $puesto->id)
        ->set('motivo', 'Prueba')
        ->call('guardar')
        ->assertHasErrors(['puestoDestinoId' => 'exists']);
});

it('un equipo sin puesto no ofrece el traslado del puesto y su traslado sigue siendo individual', function () {
    $equipo = Apoyo::equipo();

    Livewire::test(TrasladoForm::class, ['equipo' => $equipo])
        ->assertDontSee('Trasladar el puesto completo');

    $evento = app(RegistroMovimientos::class)->trasladar($equipo, $this->usuario, datosTrasladoPuesto(Apoyo::persona()));

    expect(Documento::where('evento_id', $evento->id)->count())->toBe(2)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(2)
        ->and(FormatosPdf::consecutivo($evento, 'formato_baja'))->toBe('FB-'.str_pad((string) $evento->id, 6, '0', STR_PAD_LEFT));
});

it('el panel de firmas avisa que el formato es del puesto completo', function () {
    [$puesto] = puestoConEquipos(2);
    $eventos = app(RegistroMovimientos::class)->trasladarPuesto($puesto, $this->usuario, datosTrasladoPuesto(Apoyo::persona()));

    Livewire::test(DocumentosEvento::class, ['eventoId' => $eventos->last()->id])
        ->assertSee('Traslado del puesto '.$puesto->nombre_o_codigo.' completo (2 equipos)')
        ->assertSee('Cada documento firmado que subas se aplica al traslado de todos los equipos del puesto');
});
