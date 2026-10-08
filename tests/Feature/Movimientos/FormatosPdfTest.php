<?php

use App\Livewire\Movimientos\Index;
use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Models\Dependencia;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);

    $this->entrega = Apoyo::persona(['nombre' => 'Quien Entrega', 'cedula' => '1067111222']);
    $this->recibe = Apoyo::persona(['nombre' => 'Quien Recibe', 'cedula' => '1067333444']);
    $this->equipo = Apoyo::equipo($this->entrega);

    $this->traslado = app(RegistroMovimientos::class)->trasladar($this->equipo, $this->usuario, [
        'persona' => $this->recibe,
        'sede_id' => Sede::query()->value('id'),
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $this->recibe->dependencia_id,
        'fecha' => now()->toDateString(),
        'motivo' => 'Reasignación',
    ]);
});

it('el formato de baja de un traslado lo firma quien entrega y el de entrega quien recibe', function () {
    $formatos = app(FormatosPdf::class);

    expect($formatos->datos($this->traslado, 'formato_baja')['persona']->id)->toBe($this->entrega->id)
        ->and($formatos->datos($this->traslado, 'formato_entrega')['persona']->id)->toBe($this->recibe->id);
});

it('genera los PDF en tamaño carta (RNF-13)', function () {
    $pdf = app(FormatosPdf::class)->pdf($this->traslado, 'formato_entrega');
    $contenido = $pdf->output();

    expect($contenido)->toStartWith('%PDF')
        // Carta = 612 x 792 puntos.
        ->and($contenido)->toMatch('/MediaBox\s*\[\s*0(\.0+)? 0(\.0+)? 612(\.0+)? 792(\.0+)?\s*\]/');
});

it('la vista previa imprimible usa los mismos datos que el PDF', function () {
    $this->withoutVite();

    $this->get(route('movimientos.formato-entrega', $this->traslado))
        ->assertOk()
        ->assertSee('Quien Recibe')
        ->assertSee('1067333444')
        ->assertSee('FE-'.str_pad((string) $this->traslado->id, 6, '0', STR_PAD_LEFT));

    $this->get(route('movimientos.formato-baja', $this->traslado))
        ->assertOk()
        ->assertSee('Quien Entrega')
        ->assertSee('Ingeniero de soporte técnico');
});

it('genera el formato de entrega consolidado con todos los equipos a cargo (RF-22)', function () {
    Apoyo::equipo($this->recibe);
    Apoyo::equipo($this->recibe);

    $datos = app(FormatosPdf::class)->datosConsolidado($this->recibe);
    expect($datos['equipos'])->toHaveCount(3);

    expect(app(FormatosPdf::class)->pdfConsolidado($this->recibe)->output())->toStartWith('%PDF');
});

it('la pantalla de responsables lista los equipos a cargo y descarga el consolidado', function () {
    $this->withoutVite();
    $this->get(route('movimientos.index'))->assertOk();

    Livewire::test(Index::class)
        ->set('busqueda', 'Quien Recibe')
        ->assertSee('****3444')
        ->assertDontSee('1067333444')
        ->call('seleccionar', $this->recibe->id)
        ->assertSee($this->equipo->serial)
        ->call('descargarConsolidado', $this->recibe->id)
        ->assertFileDownloaded();
});

it('registra una persona responsable con sus datos completos (RF-18)', function () {
    Livewire::test(Index::class)
        ->set('mostrarFormulario', true)
        ->set('nombre', 'Carlos Pérez')
        ->set('cedula', '78123456')
        ->set('cargo', 'Técnico Administrativo')
        ->set('dependenciaId', Dependencia::query()->value('id'))
        ->set('tipoVinculacion', 'planta')
        ->call('crearPersona')
        ->assertHasNoErrors();

    expect(Persona::where('cedula', '78123456')->first())
        ->nombre->toBe('Carlos Pérez')
        ->tipo_vinculacion->toBe('planta');
});
