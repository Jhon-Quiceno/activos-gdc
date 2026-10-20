<?php

use App\Livewire\Levantamientos\Detalle;
use App\Livewire\Levantamientos\Index;
use App\Livewire\Levantamientos\Soporte\GestorLevantamientos;
use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Livewire\Movimientos\Soporte\GestorAsignaciones;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\LevantamientoActivos;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\ValoracionActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/**
 * Levantamiento de activos (RF-59, CU-18, GT-M4 4.1): lista con
 * subdependencias, verificación, valoración enlazada, acta y aceptación.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);

    // Una secretaría con una dirección y un área debajo, en la misma sede.
    $this->sede = Sede::where('direccion_reservada', false)->has('pisos')->first();
    $this->piso = $this->sede->pisos()->first();
    $this->secretaria = Dependencia::create(['nombre' => 'Secretaría de Prueba', 'tipo' => 'secretaria']);
    $this->direccion = Dependencia::create(['nombre' => 'Dirección de Prueba', 'tipo' => 'direccion', 'padre_id' => $this->secretaria->id]);
    $this->area = Dependencia::create(['nombre' => 'Área de Prueba', 'tipo' => 'area', 'padre_id' => $this->direccion->id]);
    $this->otra = Dependencia::create(['nombre' => 'Otra Secretaría', 'tipo' => 'secretaria']);
});

/** Equipo asignado a la dependencia dada, en el piso dado. */
function equipoEn(Dependencia $dependencia, Piso $piso, array $atributos = []): Equipo
{
    $equipo = Apoyo::equipo(conResponsable: false, atributos: $atributos);
    Asignacion::where('equipo_id', $equipo->id)->update(['piso_id' => $piso->id, 'sede_id' => $piso->sede_id, 'dependencia_id' => $dependencia->id]);

    return $equipo->fresh();
}

function levantamiento(array $datos = []): LevantamientoActivos
{
    return app(GestorLevantamientos::class)->crear($datos + [
        'dependencia_id' => test()->secretaria->id,
        'proceso_id' => null,
        'sede_id' => test()->sede->id,
        'propietario_persona_id' => null,
        'custodio_persona_id' => null,
    ], test()->usuario);
}

it('se crea desde la pantalla con código consecutivo y el proceso de la dependencia', function () {
    Livewire::test(Index::class)
        ->set('mostrarFormulario', true)
        ->set('dependenciaId', $this->secretaria->id)
        ->set('sedeId', $this->sede->id)
        ->call('crear')
        ->assertHasNoErrors()
        ->assertRedirect();

    $levantamiento = LevantamientoActivos::sole();

    expect($levantamiento->codigo)->toBe('LEV-'.now()->year.'-001')
        ->and($levantamiento->estado)->toBe('en_curso')
        ->and(levantamiento()->codigo)->toBe('LEV-'.now()->year.'-002');
});

it('la lista de una secretaría incluye sus direcciones y áreas, solo en la sede del levantamiento', function () {
    $propio = equipoEn($this->secretaria, $this->piso, ['serial' => 'SN-SECRETARIA']);
    $deDireccion = equipoEn($this->direccion, $this->piso, ['serial' => 'SN-DIRECCION']);
    $deArea = equipoEn($this->area, $this->piso, ['serial' => 'SN-AREA']);
    equipoEn($this->otra, $this->piso, ['serial' => 'SN-OTRA']);
    $otroPiso = Piso::where('sede_id', '!=', $this->sede->id)->first();
    equipoEn($this->direccion, $otroPiso, ['serial' => 'SN-OTRA-SEDE']);

    $ids = app(GestorLevantamientos::class)->equipos(levantamiento())->pluck('id')->sort()->values()->all();

    expect($ids)->toBe(collect([$propio->id, $deDireccion->id, $deArea->id])->sort()->values()->all());
});

it('marca los equipos sin valoración y verifica en sitio con historial', function () {
    $equipo = equipoEn($this->direccion, $this->piso, ['verificacion' => 'pendiente_de_verificar']);
    $levantamiento = levantamiento();

    Livewire::test(Detalle::class, ['levantamiento' => $levantamiento])
        ->assertSee('Sin valoración')
        ->assertSee('Por verificar')
        ->call('verificar', $equipo->id)
        ->assertSee('Equipo verificado en sitio.');

    expect($equipo->fresh()->verificacion)->toBe('verificado')
        ->and($equipo->eventos()->where('tipo', 'actualizacion_datos')->latest('id')->first()->descripcion)->toContain($levantamiento->codigo);
});

it('valorar desde el levantamiento deja la valoración enlazada a él', function () {
    $equipo = equipoEn($this->direccion, $this->piso);
    $levantamiento = levantamiento();

    Livewire::test(Detalle::class, ['levantamiento' => $levantamiento])
        ->call('valorar', $equipo->id)
        ->assertSet('valorandoId', $equipo->id)
        ->assertDispatched('open-modal', 'valorar-en-levantamiento');

    ValoracionActivo::registrar($equipo, $this->usuario, [
        'contiene_datos_personales' => 'no', 'infraestructura_critica' => 'no', 'continuidad_negocio' => 'no',
        'propietario_cargo' => 'Director', 'custodio_cargo' => 'Técnico',
        'confidencialidad' => 2, 'integridad' => 2, 'disponibilidad' => 2,
    ], levantamiento: $levantamiento);

    expect($levantamiento->valoraciones()->count())->toBe(1);
});

it('no queda «Aceptado» sin acta firmada', function () {
    equipoEn($this->direccion, $this->piso);
    $levantamiento = levantamiento();
    $gestor = app(GestorLevantamientos::class);

    // Sin generar el acta, no se puede aceptar.
    expect(fn () => $gestor->aceptar($levantamiento, Apoyo::pdfFirmado(), $this->usuario))->toThrow(ValidationException::class);

    // Desde la pantalla: generar el acta y subir sin archivo da error.
    $pantalla = Livewire::test(Detalle::class, ['levantamiento' => $levantamiento])
        ->call('descargarActa')
        ->assertFileDownloaded("acta-{$levantamiento->codigo}.pdf");

    expect($levantamiento->fresh()->estado)->toBe('pendiente_de_firma');

    $pantalla->call('subirActa')->assertHasErrors(['actaFirmada' => 'required']);
    expect($levantamiento->fresh()->estado)->toBe('pendiente_de_firma');

    $pantalla->set('actaFirmada', Apoyo::pdfFirmado('acta.pdf'))->call('subirActa')->assertHasNoErrors();

    $levantamiento->refresh();
    expect($levantamiento->estado)->toBe('aceptado')
        ->and($levantamiento->fecha_aceptacion)->not->toBeNull()
        ->and($gestor->actaFirmadaExiste($levantamiento))->toBeTrue();
});

it('un levantamiento sin equipos no genera acta', function () {
    Livewire::test(Detalle::class, ['levantamiento' => levantamiento(['dependencia_id' => $this->otra->id])])
        ->call('descargarActa')
        ->assertHasErrors('acta');
});

it('la dirección de la Casa Refugio no aparece en ningún PDF (RN-28)', function () {
    $refugio = Sede::where('direccion_reservada', true)->first() ?? Sede::create(['nombre' => 'Casa Refugio', 'direccion' => 'Calle reservada 1', 'direccion_reservada' => true]);
    $refugio->update(['direccion' => 'Calle reservada 1']);
    $pisoRefugio = Piso::firstOrCreate(['sede_id' => $refugio->id, 'numero' => 1]);
    $persona = Apoyo::persona(['dependencia_id' => $this->direccion->id]);
    $equipo = equipoEn($this->direccion, $pisoRefugio);
    Asignacion::where('equipo_id', $equipo->id)->update(['persona_id' => $persona->id]);
    $levantamiento = levantamiento(['sede_id' => $refugio->id]);
    $levantamiento->load(['dependencia', 'proceso', 'sede']);

    // Se revisa el HTML de cada PDF: acta del levantamiento, paz y salvo y formato oficial de entrega.
    $acta = view('livewire.levantamientos.pdf.acta', [
        'levantamiento' => $levantamiento,
        'equipos' => app(GestorLevantamientos::class)->equipos($levantamiento)->get(),
        'tecnico' => $this->usuario,
        'fecha' => now(),
    ])->render();
    $pazYSalvo = view('livewire.movimientos.pdf.paz-y-salvo', [
        'persona' => $persona, 'equipos' => app(GestorAsignaciones::class)->equiposACargo($persona),
        'consecutivo' => 'PS-1', 'fecha' => now(), 'tecnico' => $this->usuario,
    ])->render();
    $entrega = view('livewire.movimientos.pdf.formato-entrega', app(FormatosPdf::class)->datosConsolidado($persona) + ['modoPdf' => true])->render();

    foreach ([$acta, $pazYSalvo, $entrega] as $html) {
        expect($html)->toContain($refugio->nombre === 'Casa Refugio' ? 'Casa Refugio' : $refugio->nombre)
            ->not->toContain('Calle reservada 1');
    }
});

it('verificar después de generar el acta devuelve el levantamiento a «En curso»', function () {
    $equipo = equipoEn($this->direccion, $this->piso, ['verificacion' => 'pendiente_de_verificar']);
    $levantamiento = levantamiento();
    $gestor = app(GestorLevantamientos::class);

    $gestor->acta($levantamiento, $this->usuario);
    expect($levantamiento->fresh()->estado)->toBe('pendiente_de_firma');

    $gestor->verificar($levantamiento->fresh(), $equipo, $this->usuario);

    expect($levantamiento->fresh()->estado)->toBe('en_curso');
});

it('no acepta un acta si los equipos cambiaron después de generarla', function () {
    equipoEn($this->direccion, $this->piso);
    $levantamiento = levantamiento();
    $gestor = app(GestorLevantamientos::class);
    $gestor->acta($levantamiento, $this->usuario);

    // Un equipo llega a la dependencia después de imprimir el acta.
    $this->travel(1)->minutes();
    equipoEn($this->area, $this->piso);

    expect(fn () => $gestor->aceptar($levantamiento->fresh(), Apoyo::pdfFirmado(), $this->usuario))->toThrow(ValidationException::class);
    expect($levantamiento->fresh()->estado)->toBe('en_curso');

    // Con el acta generada de nuevo, ya se acepta.
    $this->travel(1)->minutes();
    $gestor->acta($levantamiento->fresh(), $this->usuario);
    $gestor->aceptar($levantamiento->fresh(), Apoyo::pdfFirmado(), $this->usuario);

    expect($levantamiento->fresh()->estado)->toBe('aceptado');
});

it('ver el acta firmada que ya no está en el disco da 404 y no error 500', function () {
    equipoEn($this->direccion, $this->piso);
    $levantamiento = levantamiento();
    $gestor = app(GestorLevantamientos::class);
    $gestor->acta($levantamiento, $this->usuario);
    $gestor->aceptar($levantamiento->fresh(), Apoyo::pdfFirmado(), $this->usuario);
    Storage::disk('local')->delete($levantamiento->fresh()->acta_path);

    Livewire::test(Detalle::class, ['levantamiento' => $levantamiento->fresh()])
        ->call('verActaFirmada')
        ->assertStatus(404);
});
