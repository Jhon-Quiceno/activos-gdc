<?php

use App\Livewire\Admin\ListasPanel;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('arranca en un catálogo plano y no ofrece sedes, pisos ni dependencias', function () {
    Livewire::test(ListasPanel::class)
        ->assertSet('listaActiva', 'tipos_equipo')
        ->assertSee('Tipos de equipo')
        ->assertSee('Marcas')
        ->assertDontSee('>Sedes<')
        ->assertDontSee('>Pisos<')
        ->assertDontSee('>Dependencias<');
});

it('ignora seleccionar un catálogo que ya no existe en el panel', function () {
    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'sedes')
        ->assertSet('listaActiva', 'tipos_equipo'); // no cambió
});

it('agrega una marca al catálogo plano', function () {
    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'marcas')
        ->set('nuevoValor', 'Lenovo')
        ->call('agregar')
        ->assertHasNoErrors();

    expect(Marca::where('nombre', 'Lenovo')->exists())->toBeTrue();
});

it('agrega un tipo de equipo con su familia y la valida', function () {
    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'tipos_equipo')
        ->set('nuevoValor', 'Portátil')
        ->call('agregar')
        ->assertHasErrors(['nuevaFamilia']) // la familia es obligatoria
        ->set('nuevaFamilia', 'computo')
        ->call('agregar')
        ->assertHasNoErrors();

    expect(TipoEquipo::where('nombre', 'Portátil')->where('familia', 'computo')->exists())->toBeTrue();
});

it('edita el nombre de una marca', function () {
    $marca = Marca::forceCreate(['nombre' => 'Dell']);

    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'marcas')
        ->call('abrirEdicion', $marca->id)
        ->set('editandoValor', 'Dell Technologies')
        ->call('guardarEdicion')
        ->assertHasNoErrors();

    expect($marca->fresh()->nombre)->toBe('Dell Technologies');
});
