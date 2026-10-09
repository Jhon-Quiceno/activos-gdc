<?php

use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\TrasladosListado;
use App\Models\MotivoBaja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
    Apoyo::catalogos();
    $this->actingAs(Apoyo::usuario());
    $this->equipo = Apoyo::equipo();
});

it('abre todas las pantallas de Movimientos sin errores', function (string $ruta, bool $conEquipo) {
    $url = $conEquipo ? route($ruta, $this->equipo) : route($ruta);

    $this->get($url)->assertOk();
})->with([
    'responsables' => ['movimientos.index', false],
    'traslados' => ['movimientos.traslados.index', false],
    'traslado' => ['movimientos.traslado', true],
    'bajas' => ['movimientos.bajas.index', false],
    'baja' => ['movimientos.baja', true],
    'diagnóstico' => ['movimientos.diagnostico', true],
    'componente' => ['movimientos.componente', true],
    'pendientes' => ['movimientos.pendientes', false],
]);

it('el listado de traslados filtra los equipos sin asignar (RF-21)', function () {
    $bodega = Apoyo::equipo(conResponsable: false);

    Livewire::test(TrasladosListado::class)
        ->set('estado', 'sin_asignar')
        ->assertSee($bodega->serial)
        ->assertDontSee($this->equipo->serial);
});

it('las pantallas muestran el panel de firmas de una baja en trámite', function () {
    app(RegistroMovimientos::class)->darDeBaja($this->equipo, auth()->user(), [
        'motivo_baja_id' => MotivoBaja::query()->value('id'),
        'fecha' => now()->toDateString(),
        'estado_encontrado' => 'No enciende',
        'diagnostico' => 'Fuente quemada',
        'recomendaciones' => null,
    ]);

    $this->get(route('movimientos.baja', $this->equipo))
        ->assertOk()
        ->assertSee('Baja pendiente de firma')
        ->assertSee('Descargar PDF prellenado');
});
