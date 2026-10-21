<?php

use App\Livewire\Reportes\Index;
use App\Models\Asignacion;
use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\SistemaOperativo;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->tipo = TipoEquipo::forceCreate(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'tipo_activo' => 'hardware', 'vida_util_meses' => 60]);
    Marca::forceCreate(['nombre' => 'HP']);
});

it('garantías por vencer lista solo las que vencen en los próximos 60 días', function () {
    $porVencer = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id, 'fecha_fin_garantia' => now()->addDays(30)]);
    $vigente = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id, 'fecha_fin_garantia' => now()->addDays(200)]);
    $vencida = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id, 'fecha_fin_garantia' => now()->subDays(5)]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'garantias_por_vencer')
        ->assertSee($porVencer->serial)
        ->assertDontSee($vigente->serial)
        ->assertDontSee($vencida->serial);
});

it('vida útil superada usa la vida útil del equipo o la de su tipo', function () {
    $vieja = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id, 'fecha_adquisicion' => now()->subYears(7), 'vida_util_meses' => null]);
    $nueva = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id, 'fecha_adquisicion' => now()->subYear(), 'vida_util_meses' => null]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'vida_util_superada')
        ->assertSee($vieja->serial)
        ->assertDontSee($nueva->serial);
});

it('obsolescencia por SO usa el fin de soporte real, no el nombre', function () {
    $win7 = SistemaOperativo::forceCreate(['nombre' => 'Windows 7', 'fin_soporte' => now()->subYears(2)]);
    $win11 = SistemaOperativo::forceCreate(['nombre' => 'Windows 11', 'fin_soporte' => now()->addYears(5)]);

    $sinSoporte = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    ConfiguracionComputo::create(['equipo_id' => $sinSoporte->id, 'sistema_operativo_id' => $win7->id]);
    $conSoporte = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    ConfiguracionComputo::create(['equipo_id' => $conSoporte->id, 'sistema_operativo_id' => $win11->id]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'obsolescencia_so')
        ->assertSee($sinSoporte->serial)
        ->assertDontSee($conSoporte->serial);
});

it('personas por retirarse lista equipos de quien tiene vinculación por terminar', function () {
    $porRetirarse = Persona::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'numero_documento' => '111', 'fecha_fin_vinculacion' => now()->addDays(30)]);
    $estable = Persona::create(['nombres' => 'Luis', 'apellidos' => 'Gómez', 'numero_documento' => '222', 'fecha_fin_vinculacion' => null]);

    $muni = \App\Models\Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería']);
    $piso = \App\Models\Piso::create(['sede_id' => \App\Models\Sede::create(['nombre' => 'Palacio', 'tipo' => 'administrativa', 'municipio_id' => $muni->id])->id, 'numero' => 1]);

    $equipoAna = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    Asignacion::create(['equipo_id' => $equipoAna->id, 'persona_id' => $porRetirarse->id, 'piso_id' => $piso->id, 'fecha_inicio' => now()]);
    $equipoLuis = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    Asignacion::create(['equipo_id' => $equipoLuis->id, 'persona_id' => $estable->id, 'piso_id' => $piso->id, 'fecha_inicio' => now()]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'personas_por_retirarse')
        ->assertSee($equipoAna->serial)
        ->assertDontSee($equipoLuis->serial);
});
