<?php

use App\Livewire\Reportes\Index;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\ValoracionActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/*
 * RF-37 (v4): la calidad del inventario suma «sin valoración» (solo hardware
 * en uso, RN-25) y «sin datos de adquisición», en el reporte y en el tablero.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->hardware = TipoEquipo::forceCreate(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'tipo_activo' => 'hardware']);
    $this->soporte = TipoEquipo::forceCreate(['nombre' => 'UPS', 'familia' => 'energia', 'tipo_activo' => 'soporte']);
    Marca::forceCreate(['nombre' => 'HP']);
    $this->usuario = User::factory()->create(['debe_cambiar_contrasena' => false]);
});

function valorar(Equipo $equipo, User $usuario): void
{
    ValoracionActivo::registrar($equipo, $usuario, [
        'contiene_datos_personales' => 'no', 'clasificacion_datos' => 'no_aplica',
        'infraestructura_critica' => 'no', 'continuidad_negocio' => 'no',
        'propietario_cargo' => 'Director TIC', 'custodio_cargo' => 'Técnico',
        'confidencialidad' => 2, 'integridad' => 2, 'disponibilidad' => 2,
    ]);
}

it('«sin valoración» trae solo el hardware en uso sin valoración vigente (RN-25)', function () {
    $sinValorar = Equipo::factory()->create(['tipo_equipo_id' => $this->hardware->id, 'serial' => 'HW-SIN-VALORAR']);
    $valorado = Equipo::factory()->create(['tipo_equipo_id' => $this->hardware->id, 'serial' => 'HW-VALORADO']);
    valorar($valorado, $this->usuario);
    Equipo::factory()->create(['tipo_equipo_id' => $this->soporte->id, 'serial' => 'UPS-SOPORTE']);
    Equipo::factory()->dadoDeBaja()->create(['tipo_equipo_id' => $this->hardware->id, 'serial' => 'HW-DE-BAJA']);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'sin_valoracion')
        ->assertSee($sinValorar->serial)
        ->assertDontSee('HW-VALORADO')
        ->assertDontSee('UPS-SOPORTE')
        ->assertDontSee('HW-DE-BAJA');
});

it('«sin datos de adquisición» trae los equipos sin fecha ni valor de adquisición', function () {
    Equipo::factory()->create(['serial' => 'SIN-ADQ', 'fecha_adquisicion' => null, 'valor_adquisicion' => null]);
    Equipo::factory()->create(['serial' => 'CON-ADQ', 'fecha_adquisicion' => '2024-03-01', 'valor_adquisicion' => 3500000]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'sin_adquisicion')
        ->assertSee('SIN-ADQ')
        ->assertDontSee('CON-ADQ');
});

it('la columna de problemas nombra los dos indicadores nuevos', function () {
    Equipo::factory()->create(['tipo_equipo_id' => $this->hardware->id, 'fecha_adquisicion' => null, 'valor_adquisicion' => null]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->assertSee('Sin valoración')
        ->assertSee('Sin datos de adquisición');
});

it('la tarjeta de calidad del tablero muestra «sin valoración» y «sin datos de adquisición»', function () {
    Equipo::factory()->create(['tipo_equipo_id' => $this->hardware->id, 'fecha_adquisicion' => null, 'valor_adquisicion' => null]);

    $this->actingAs($this->usuario)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Sin valoración (hardware)')
        ->assertSee('Sin datos de adquisición');
});
