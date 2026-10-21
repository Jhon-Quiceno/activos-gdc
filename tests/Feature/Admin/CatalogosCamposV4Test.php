<?php

use App\Livewire\Admin\CatalogosPanel;
use App\Livewire\Admin\ListasPanel;
use App\Models\Dependencia;
use App\Models\Municipio;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\UbicacionDependencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/*
 * RF-45 (v4): los catálogos se mantienen con sus campos nuevos (tipo de activo,
 * configuración de cómputo, vida útil, fin de soporte, obligatorio en cómputo) y
 * la ubicación de dependencias por piso se edita desde Administración.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

// ---------------------------------------------------------------------------
// Listas: campos v4
// ---------------------------------------------------------------------------

it('agrega un tipo de equipo de soporte con vida útil y sin configuración de cómputo', function () {
    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'tipos_equipo')
        ->set('nuevoValor', 'Aire acondicionado de cuarto técnico')
        ->set('extra.familia', 'climatizacion')
        ->set('extra.tipo_activo', 'soporte')
        ->set('extra.vida_util_meses', '96')
        ->call('agregar')
        ->assertHasNoErrors();

    expect(TipoEquipo::where('nombre', 'Aire acondicionado de cuarto técnico')->first())
        ->familia->toBe('climatizacion')
        ->tipo_activo->toBe('soporte')
        ->vida_util_meses->toBe(96)
        ->usa_configuracion_computo->toBeFalse()
        ->activo->toBeTrue();
});

it('edita los campos v4 de un tipo de equipo y valida la vida útil', function () {
    $tipo = TipoEquipo::forceCreate(['nombre' => 'Portátil', 'familia' => 'computo', 'tipo_activo' => 'hardware']);

    Livewire::test(ListasPanel::class)
        ->call('abrirEdicion', $tipo->id)
        ->assertSet('editandoExtra.familia', 'computo')
        ->set('editandoExtra.vida_util_meses', '0')
        ->call('guardarEdicion')
        ->assertHasErrors(['editandoExtra.vida_util_meses'])
        ->set('editandoExtra.vida_util_meses', '60')
        ->set('editandoExtra.usa_configuracion_computo', true)
        ->call('guardarEdicion')
        ->assertHasNoErrors();

    expect($tipo->fresh())
        ->vida_util_meses->toBe(60)
        ->usa_configuracion_computo->toBeTrue();
});

it('desactiva y reactiva un tipo de equipo sin borrarlo', function () {
    $tipo = TipoEquipo::forceCreate(['nombre' => 'Fax', 'familia' => 'telefonia', 'tipo_activo' => 'hardware']);

    $pantalla = Livewire::test(ListasPanel::class)->call('desactivar', $tipo->id);
    expect($tipo->fresh()->activo)->toBeFalse();

    $pantalla->assertSee('Inactivo')->call('desactivar', $tipo->id);
    expect($tipo->fresh()->activo)->toBeTrue();
});

it('guarda el fin de soporte de un sistema operativo y lo muestra', function () {
    $so = SistemaOperativo::forceCreate(['nombre' => 'Windows 10 Pro']);

    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'sistemas_operativos')
        ->call('abrirEdicion', $so->id)
        ->set('editandoExtra.fin_soporte', '2025-10-14')
        ->call('guardarEdicion')
        ->assertHasNoErrors()
        ->assertSee('Fin de soporte: 14/10/2025');

    expect($so->fresh()->fin_soporte->toDateString())->toBe('2025-10-14');
});

it('marca un tipo de componente como obligatorio en cómputo (RF-14)', function () {
    Livewire::test(ListasPanel::class)
        ->call('seleccionarLista', 'tipos_componente')
        ->set('nuevoValor', 'Procesador')
        ->set('extra.obligatorio_en_computo', true)
        ->call('agregar')
        ->assertHasNoErrors();

    expect(TipoComponente::where('nombre', 'Procesador')->value('obligatorio_en_computo'))->toBeTruthy();
});

// ---------------------------------------------------------------------------
// Catálogos: ubicación de dependencias por piso
// ---------------------------------------------------------------------------

function sedeConPiso(string $nombre, int $numero): array
{
    $muni = Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería']);
    $sede = Sede::create(['nombre' => $nombre, 'tipo' => 'administrativa', 'municipio_id' => $muni->id]);
    $piso = Piso::create(['sede_id' => $sede->id, 'numero' => $numero]);

    return [$sede, $piso];
}

it('ubica una dependencia en un piso de la sede y la quita', function () {
    [$sede, $piso] = sedeConPiso('Palacio de Naín', 3);
    $dependencia = Dependencia::forceCreate(['nombre' => 'Dirección TICS', 'tipo' => 'direccion', 'activa' => true]);

    $pantalla = Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'ubicaciones')
        ->assertSee('Dirección TICS') // sin piso todavía
        ->set('ubSedeId', $sede->id)
        ->set('ubPisoId', $piso->id)
        ->set('ubDependenciaId', $dependencia->id)
        ->call('agregarUbicacion')
        ->assertHasNoErrors();

    $ubicacion = UbicacionDependencia::where('piso_id', $piso->id)->where('dependencia_id', $dependencia->id)->first();
    expect($ubicacion)->not->toBeNull();

    $pantalla->call('quitarUbicacion', $ubicacion->id);
    expect(UbicacionDependencia::count())->toBe(0);
});

it('no acepta un piso de otra sede (RN-21) ni repetir la misma ubicación', function () {
    [$sede] = sedeConPiso('Palacio de Naín', 3);
    [, $pisoAjeno] = sedeConPiso('Places Mall', 2);
    $dependencia = Dependencia::forceCreate(['nombre' => 'Rentas', 'tipo' => 'direccion', 'activa' => true]);

    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'ubicaciones')
        ->set('ubSedeId', $sede->id)
        ->set('ubPisoId', $pisoAjeno->id)
        ->set('ubDependenciaId', $dependencia->id)
        ->call('agregarUbicacion')
        ->assertHasErrors(['ubPisoId']);

    UbicacionDependencia::create(['piso_id' => $pisoAjeno->id, 'dependencia_id' => $dependencia->id]);

    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'ubicaciones')
        ->set('ubSedeId', $pisoAjeno->sede_id)
        ->set('ubPisoId', $pisoAjeno->id)
        ->set('ubDependenciaId', $dependencia->id)
        ->call('agregarUbicacion')
        ->assertHasErrors(['ubDependenciaId']);
});

it('al cambiar la sede se vacía el piso elegido', function () {
    [$sede, $piso] = sedeConPiso('Palacio de Naín', 3);
    [$otra] = sedeConPiso('Places Mall', 2);

    Livewire::test(CatalogosPanel::class)
        ->call('seleccionar', 'ubicaciones')
        ->set('ubSedeId', $sede->id)
        ->set('ubPisoId', $piso->id)
        ->set('ubSedeId', $otra->id)
        ->assertSet('ubPisoId', null);
});
