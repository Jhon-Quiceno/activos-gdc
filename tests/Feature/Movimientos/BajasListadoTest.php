<?php

use App\Livewire\Movimientos\BajasListado;
use App\Livewire\Movimientos\DocumentosEvento;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Models\MotivoBaja;
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

    $this->obsolescencia = MotivoBaja::query()->orderBy('id')->first();
    $this->otroMotivo = MotivoBaja::query()->whereKeyNot($this->obsolescencia->id)->first();

    $this->equipo = Apoyo::equipo(atributos: ['serial' => 'SN-BAJA-1']);
    $this->baja = app(RegistroMovimientos::class)->darDeBaja($this->equipo, $this->usuario, [
        'motivo_baja_id' => $this->obsolescencia->id,
        'fecha' => now()->toDateString(),
        'estado_encontrado' => 'No enciende',
        'diagnostico' => 'Tarjeta madre quemada',
        'recomendaciones' => null,
    ]);
});

it('la pestaña de equipos usa los filtros del listado de equipos y no muestra dados de baja', function () {
    Apoyo::equipo(atributos: ['serial' => 'SN-YA-DE-BAJA', 'estado_ciclo_vida' => 'dado_de_baja']);
    Apoyo::equipo(conResponsable: false, atributos: ['serial' => 'SN-BODEGA-2']);
    Apoyo::equipo(atributos: ['serial' => 'SN-CON-RESP']);

    Livewire::test(BajasListado::class)
        ->assertDontSee('SN-YA-DE-BAJA')
        ->set('responsable', 'sin')
        ->assertSee('SN-BODEGA-2')
        ->assertDontSee('SN-CON-RESP')
        ->set('responsable', '')
        ->set('firmas', 'pendiente')
        ->assertSee('SN-BAJA-1')
        ->assertDontSee('SN-CON-RESP');
});

it('la pestaña de bajas registradas lista la baja y abre su formato', function () {
    Livewire::test(BajasListado::class)
        ->set('vista', 'registradas')
        ->assertSee('SN-BAJA-1')
        ->assertSee('Tarjeta madre quemada')
        ->call('seleccionarBaja', $this->baja->id)
        ->assertSeeLivewire(DocumentosEvento::class);
});

it('filtra las bajas registradas por estado y por motivo', function () {
    Livewire::test(BajasListado::class)
        ->set('vista', 'registradas')
        ->set('estadoBaja', 'pendiente_de_firma')
        ->assertSee('SN-BAJA-1')
        ->set('estadoBaja', 'anulada')
        ->assertDontSee('SN-BAJA-1')
        ->set('estadoBaja', '')
        ->set('motivo', (string) $this->otroMotivo->id)
        ->assertDontSee('SN-BAJA-1')
        ->set('motivo', (string) $this->obsolescencia->id)
        ->assertSee('SN-BAJA-1');
});

it('una baja anulada aparece como anulada', function () {
    app(RegistroMovimientos::class)->anularBaja($this->equipo, $this->usuario, 'Se registró por error en otro equipo.');

    Livewire::test(BajasListado::class)
        ->set('vista', 'registradas')
        ->set('estadoBaja', 'anulada')
        ->assertSee('SN-BAJA-1')
        ->set('estadoBaja', 'pendiente_de_firma')
        ->assertDontSee('SN-BAJA-1');
});
