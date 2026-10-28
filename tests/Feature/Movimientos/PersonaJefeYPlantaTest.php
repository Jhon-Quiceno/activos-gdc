<?php

use App\Livewire\Movimientos\Index;
use App\Models\Dependencia;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/*
 * RF-18: la persona responsable lleva su jefe inmediato y la planta de la que
 * viene (Central o Educación) también cuando se registra a mano, no solo en la
 * carga de planta.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Apoyo::catalogos();
    $this->actingAs(Apoyo::usuario());

    $this->jefa = Apoyo::persona(['nombres' => 'Directora', 'apellidos' => 'Técnica', 'numero_documento' => '1067555666']);
    $this->persona = Apoyo::persona(['nombres' => 'Analista', 'apellidos' => 'Soporte', 'numero_documento' => '1067777888']);
});

it('registra una persona con su jefe inmediato y su planta', function () {
    Livewire::test(Index::class)
        ->call('nuevaPersona')
        ->set('nombres', 'Nueva')
        ->set('apellidos', 'Persona')
        ->set('numeroDocumento', '1067999000')
        ->set('dependenciaId', Dependencia::query()->value('id'))
        ->set('tipoVinculacion', 'planta')
        ->set('planta', 'central')
        ->call('elegirJefe', $this->jefa->id)
        ->call('guardarPersona')
        ->assertHasNoErrors();

    expect(Persona::where('numero_documento', '1067999000')->first())
        ->jefe_inmediato_id->toBe($this->jefa->id)
        ->planta->toBe('central');
});

it('el buscador de jefe encuentra por nombre o documento y no se ofrece a sí misma', function () {
    $this->persona->update(['jefe_inmediato_id' => null]);

    $pantalla = Livewire::test(Index::class)
        ->call('editarPersona', $this->persona->id)
        ->set('jefeBusqueda', 'Analista')
        ->assertDontSee('Analista Soporte ·')
        ->set('jefeBusqueda', '1067555')
        ->assertSee('Directora Técnica');

    expect($pantalla->viewData('jefeCandidatos')->pluck('id'))->not->toContain($this->persona->id);
});

it('una persona no puede ser su propio jefe inmediato', function () {
    Livewire::test(Index::class)
        ->call('editarPersona', $this->persona->id)
        ->set('jefeInmediatoId', $this->persona->id)
        ->call('guardarPersona')
        ->assertHasErrors(['jefeInmediatoId' => 'not_in']);
});

it('editar conserva el jefe y la planta, y se pueden quitar', function () {
    $this->persona->update(['jefe_inmediato_id' => $this->jefa->id, 'planta' => 'educacion']);

    Livewire::test(Index::class)
        ->call('editarPersona', $this->persona->id)
        ->assertSet('jefeInmediatoId', $this->jefa->id)
        ->assertSet('planta', 'educacion')
        ->set('apellidos', 'Soporte TIC')
        ->call('guardarPersona')
        ->assertHasNoErrors();

    expect($this->persona->fresh())
        ->jefe_inmediato_id->toBe($this->jefa->id)
        ->planta->toBe('educacion');

    Livewire::test(Index::class)
        ->call('editarPersona', $this->persona->id)
        ->call('quitarJefe')
        ->set('planta', '')
        ->call('guardarPersona')
        ->assertHasNoErrors();

    expect($this->persona->fresh())
        ->jefe_inmediato_id->toBeNull()
        ->planta->toBeNull();
});

it('la planta solo acepta Central o Educación', function () {
    Livewire::test(Index::class)
        ->call('editarPersona', $this->persona->id)
        ->set('planta', 'otra')
        ->call('guardarPersona')
        ->assertHasErrors(['planta']);
});
