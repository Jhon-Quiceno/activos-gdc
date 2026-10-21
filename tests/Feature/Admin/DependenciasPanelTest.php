<?php

use App\Livewire\Admin\DependenciasPanel;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->secretaria = Dependencia::create(['nombre' => 'Secretaría de Hacienda', 'tipo' => 'secretaria']);
    $this->direccion = Dependencia::create(['nombre' => 'Dirección de Rentas', 'tipo' => 'direccion', 'padre_id' => $this->secretaria->id]);
});

it('crea una dependencia bajo un padre', function () {
    Livewire::test(DependenciasPanel::class)
        ->call('nuevo')
        ->set('nombre', 'Área de Cartera')
        ->set('tipo', 'area')
        ->set('padreId', $this->direccion->id)
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Dependencia::where('nombre', 'Área de Cartera')->first()?->padre_id)->toBe($this->direccion->id);
});

it('bloquea mover una dependencia dentro de una de sus hijas (RN-20)', function () {
    Livewire::test(DependenciasPanel::class)
        ->call('abrirEdicion', $this->secretaria->id)
        ->set('padreId', $this->direccion->id) // la secretaría bajo su propia dirección
        ->call('guardar')
        ->assertHasErrors('padreId');

    expect($this->secretaria->fresh()->padre_id)->toBeNull();
});

it('desactiva sin borrar y permite reactivar (RN-20)', function () {
    Livewire::test(DependenciasPanel::class)
        ->call('alternarActiva', $this->direccion->id);

    expect($this->direccion->fresh()->activa)->toBeFalse()
        ->and(Dependencia::whereKey($this->direccion->id)->exists())->toBeTrue();

    Livewire::test(DependenciasPanel::class)->call('alternarActiva', $this->direccion->id);
    expect($this->direccion->fresh()->activa)->toBeTrue();
});

it('agrega y reasigna una variante de texto (RF-39)', function () {
    $comp = Livewire::test(DependenciasPanel::class)
        ->call('abrirEquivalencias', $this->direccion->id)
        ->set('nuevaEquivalencia', 'DIRECION DE RENTAS')
        ->call('agregarEquivalencia')
        ->assertHasNoErrors();

    $equivalencia = DependenciaEquivalencia::where('texto', 'DIRECION DE RENTAS')->sole();
    expect($equivalencia->dependencia_id)->toBe($this->direccion->id)
        ->and(DependenciaEquivalencia::resolver('direccion de rentas')?->id)->toBe($this->direccion->id);

    $comp->call('reasignarEquivalencia', $equivalencia->id, $this->secretaria->id);
    expect($equivalencia->fresh()->dependencia_id)->toBe($this->secretaria->id);
});

it('no permite dos variantes con el mismo texto', function () {
    DependenciaEquivalencia::create(['dependencia_id' => $this->secretaria->id, 'texto' => 'HACIENDA', 'origen' => 'seed']);

    Livewire::test(DependenciasPanel::class)
        ->call('abrirEquivalencias', $this->direccion->id)
        ->set('nuevaEquivalencia', 'HACIENDA')
        ->call('agregarEquivalencia')
        ->assertHasErrors('nuevaEquivalencia');
});

it('el buscador filtra el árbol', function () {
    Livewire::test(DependenciasPanel::class)
        ->set('busqueda', 'rentas')
        ->assertSee('Dirección de Rentas')
        ->set('busqueda', 'zzz-no-existe')
        ->assertSee('No hay dependencias para ese criterio.');
});
