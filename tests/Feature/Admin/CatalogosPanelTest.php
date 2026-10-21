<?php

use App\Livewire\Admin\CatalogosPanel;
use App\Models\Marca;
use App\Models\Municipio;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\ModeloEquipo;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('crea una sede con municipio y marca de dirección reservada', function () {
    $muni = Municipio::create(['codigo_dane' => '23001', 'nombre' => 'Montería']);

    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'sedes')
        ->set('form.nombre', 'Casa Refugio')
        ->set('form.municipio_id', $muni->id)
        ->set('form.tipo', 'social')
        ->set('form.tenencia', 'comodato')
        ->set('form.direccion_reservada', true)
        ->call('guardar')
        ->assertHasNoErrors();

    $sede = Sede::where('nombre', 'Casa Refugio')->first();
    expect($sede?->municipio_id)->toBe($muni->id)
        ->and($sede?->direccion_reservada)->toBeTrue();
});

it('agrega un piso a una sede, único por sede', function () {
    $muni = Municipio::create(['codigo_dane' => '23001', 'nombre' => 'Montería']);
    $sede = Sede::create(['nombre' => 'Palacio', 'tipo' => 'administrativa', 'municipio_id' => $muni->id]);

    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'sedes')
        ->set('pisoNumero', '3')
        ->call('agregarPiso', $sede->id)
        ->assertHasNoErrors();

    expect(Piso::where('sede_id', $sede->id)->where('numero', 3)->exists())->toBeTrue();

    Piso::create(['sede_id' => $sede->id, 'numero' => 5]);
    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'sedes')
        ->set('pisoNumero', '5')
        ->call('agregarPiso', $sede->id)
        ->assertHasErrors('pisoNumero');
});

it('crea un modelo y no lo duplica por marca y tipo', function () {
    $marca = Marca::forceCreate(['nombre' => 'HP']);
    $tipo = TipoEquipo::forceCreate(['nombre' => 'Monitor', 'familia' => 'video']);

    $comp = Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'modelos')
        ->set('form.marca_id', $marca->id)
        ->set('form.tipo_equipo_id', $tipo->id)
        ->set('form.nombre', 'P24')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(ModeloEquipo::where('nombre', 'P24')->count())->toBe(1);

    $comp->set('form.marca_id', $marca->id)->set('form.tipo_equipo_id', $tipo->id)->set('form.nombre', 'P24')
        ->call('guardar')
        ->assertHasErrors('form.nombre');
});

it('crea un proceso y lo desactiva', function () {
    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'procesos')
        ->set('form.nombre', 'Gestión Tecnológica')
        ->set('form.tipo', 'apoyo')
        ->call('guardar')
        ->assertHasNoErrors();

    $proceso = Proceso::where('nombre', 'Gestión Tecnológica')->sole();
    expect($proceso->activo)->toBeTrue();

    Livewire::test(CatalogosPanel::class)->call('seleccionar', 'procesos')->call('alternarActiva', $proceso->id);
    expect($proceso->fresh()->activo)->toBeFalse();
});

it('crea un proveedor con correo válido', function () {
    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'proveedores')
        ->set('form.razon_social', 'Comodante S.A.S.')
        ->set('form.correo', 'no-es-correo')
        ->call('guardar')
        ->assertHasErrors('form.correo')
        ->set('form.correo', 'contacto@comodante.co')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Proveedor::where('razon_social', 'Comodante S.A.S.')->exists())->toBeTrue();
});
