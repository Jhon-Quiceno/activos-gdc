<?php

use App\Livewire\Reportes\Definiciones\InventarioGeneral;
use App\Livewire\Reportes\Index;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class InventarioGeneralPrueba extends InventarioGeneral
{
    public function probar(string $cedula): string
    {
        return $this->enmascararCedula($cedula);
    }
}

beforeEach(function () {
    // EquipoFactory toma un tipo y una marca que ya existan; en un test la BD está vacía.
    TipoEquipo::forceCreate(['nombre' => 'Portátil']);
    Marca::forceCreate(['nombre' => 'Lenovo']);
});

it('muestra el inventario general', function () {
    $equipo = Equipo::factory()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'inventario_general')
        ->assertOk()
        ->assertSee($equipo->serial);
});

it('filtra por estado del ciclo de vida', function () {
    $enServicio = Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']);
    $dadoDeBaja = Equipo::factory()->dadoDeBaja()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'inventario_general')
        ->set('filtroEstado', 'dado_de_baja')
        ->assertSee($dadoDeBaja->serial)
        ->assertDontSee($enServicio->serial);
});

it('enmascara la cédula', function () {
    $reporte = new InventarioGeneralPrueba();

    $resultado = $reporte->probar('1067896226');

    expect($resultado)->toBe('****6226');
});

it('carga sin errores los reportes de inventario', function (string $clave) {
    Equipo::factory()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', $clave)
        ->assertOk();
})->with([
    'inventario_general',
    'por_dependencia',
    'por_sede_piso',
    'por_funcionario',
    'por_tipo_marca_modelo',
    'por_estado',
    'terceros',
]);

it('el reporte de terceros solo muestra equipos de terceros', function () {
    $propio = Equipo::factory()->create(['propiedad' => 'gobernacion', 'propietario_tercero' => null]);
    $tercero = Equipo::factory()->deTercero()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'terceros')
        ->assertSee($tercero->serial)
        ->assertDontSee($propio->serial);
});

it('el reporte por estado y por tipo listan equipos sin asignación', function (string $clave) {
    $equipo = Equipo::factory()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', $clave)
        ->assertSee($equipo->serial);
})->with(['por_estado', 'por_tipo_marca_modelo']);