<?php

use App\Livewire\Reportes\Definiciones\InventarioGeneral;
use App\Livewire\Reportes\Index;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
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

// ---------------------------------------------------------------------------
// Inventario general y filtros
// ---------------------------------------------------------------------------

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

it('filtra por tipo de equipo y por marca', function () {
    $otroTipo = TipoEquipo::forceCreate(['nombre' => 'Impresora']);
    $otraMarca = Marca::forceCreate(['nombre' => 'HP']);

    $portatil = Equipo::factory()->create([
        'tipo_equipo_id' => TipoEquipo::first()->id,
        'marca_id' => Marca::first()->id,
    ]);
    $impresora = Equipo::factory()->create([
        'tipo_equipo_id' => $otroTipo->id,
        'marca_id' => $otraMarca->id,
    ]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'inventario_general')
        ->set('filtroTipo', (string) $otroTipo->id)
        ->assertSee($impresora->serial)
        ->assertDontSee($portatil->serial)
        ->set('filtroTipo', '')
        ->set('filtroMarca', (string) $otraMarca->id)
        ->assertSee($impresora->serial)
        ->assertDontSee($portatil->serial);
});

it('filtra por propiedad', function () {
    $propio = Equipo::factory()->create(['propiedad' => 'gobernacion', 'propietario_tercero' => null]);
    $tercero = Equipo::factory()->deTercero()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'inventario_general')
        ->set('filtroPropiedad', 'tercero')
        ->assertSee($tercero->serial)
        ->assertDontSee($propio->serial);
});

// ---------------------------------------------------------------------------
// RN-12: cédula enmascarada
// ---------------------------------------------------------------------------

it('enmascara la cédula', function () {
    $reporte = new InventarioGeneralPrueba();

    $resultado = $reporte->probar('1067896226');

    expect($resultado)->toBe('****6226');
});

// ---------------------------------------------------------------------------
// Los 15 reportes cargan sin errores
// ---------------------------------------------------------------------------

it('carga sin errores cada uno de los reportes', function (string $clave) {
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
    'dados_de_baja',
    'obsolescencia_so',
    'sin_antivirus',
    'historial_equipo',
    'cambios_componentes',
    'traslados',
    'pendientes_firma',
    'calidad_inventario',
]);

// ---------------------------------------------------------------------------
// Reportes de inventario
// ---------------------------------------------------------------------------

it('el reporte de terceros solo muestra equipos de terceros', function () {
    $propio = Equipo::factory()->create(['propiedad' => 'gobernacion', 'propietario_tercero' => null]);
    $tercero = Equipo::factory()->deTercero()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'terceros')
        ->assertSee($tercero->serial)
        ->assertDontSee($propio->serial);
});

it('los reportes por estado y por tipo listan equipos sin asignación', function (string $clave) {
    $equipo = Equipo::factory()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', $clave)
        ->assertSee($equipo->serial);
})->with(['por_estado', 'por_tipo_marca_modelo']);

// ---------------------------------------------------------------------------
// Calidad del inventario (RF-37)
// ---------------------------------------------------------------------------

it('calidad señala solo los equipos sin código de activo', function () {
    $sinCodigo = Equipo::factory()->create(['codigo_activo' => null]);
    $conCodigo = Equipo::factory()->create();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'sin_codigo') // el filtro propio de este reporte
        ->assertSee($sinCodigo->serial)
        ->assertDontSee($conCodigo->serial);
});

it('calidad señala los equipos con serial provisional, no con el serial en blanco (nunca lo está)', function () {
    // El serial es NOT NULL en la base: un equipo "sin serial" real es el que
    // quedó con el provisional PENDIENTE-... del registro o la importación.
    $provisional = Equipo::factory()->create(['serial' => 'PENDIENTE-REG-000123']);
    $conSerial = Equipo::factory()->create(['serial' => 'SN-0001']);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'sin_serial')
        ->assertSee($provisional->serial)
        ->assertDontSee($conSerial->serial);
});

it('calidad señala los equipos pendientes de verificar', function () {
    $pendiente = Equipo::factory()->create(['verificacion' => 'pendiente_de_verificar']);
    $verificado = Equipo::factory()->create(['verificacion' => 'verificado']);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'pendiente_verificar')
        ->assertSee($pendiente->serial)
        ->assertDontSee($verificado->serial);
});

// ---------------------------------------------------------------------------
// Reportes de movimientos (leen eventos creados por HistorialService)
// ---------------------------------------------------------------------------

it('traslados lista los eventos de traslado y el historial todos los eventos', function () {
    $equipo = Equipo::factory()->create();
    $usuario = User::factory()->create();

    // Los eventos solo se crean por el servicio, nunca a mano.
    app(HistorialService::class)->registrar($equipo, 'traslado_responsable', $usuario, [], 'Traslado de prueba');
    app(HistorialService::class)->registrar($equipo, 'actualizacion_datos', $usuario, [], 'Cambio de dato de prueba');

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'traslados')
        ->assertSee('Traslado de prueba')
        ->assertDontSee('Cambio de dato de prueba');

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'historial_equipo')
        ->assertSee('Traslado de prueba')
        ->assertSee('Cambio de dato de prueba');
});

it('dados de baja solo lista equipos con evento de baja', function () {
    $conBaja = Equipo::factory()->dadoDeBaja()->create();
    $sinBaja = Equipo::factory()->create();
    $usuario = User::factory()->create();

    app(HistorialService::class)->registrar($conBaja, 'baja', $usuario, [], 'Baja de prueba');

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'dados_de_baja')
        ->assertSee($conBaja->serial)
        ->assertDontSee($sinBaja->serial);
});
// ---------------------------------------------------------------------------
// Modelo v4: la sede sale del piso y el responsable de nombres/apellidos
// (adaptación del paso 3; estas columnas viejas se borran en el cierre)
// ---------------------------------------------------------------------------

it('filtra por sede a través del piso y muestra el nombre completo del responsable', function () {
    $sede = App\Models\Sede::forceCreate(['nombre' => 'Palacio de Naín', 'tipo' => 'administrativa']);
    $otraSede = App\Models\Sede::forceCreate(['nombre' => 'Edificio Anexo', 'tipo' => 'administrativa']);
    $piso = App\Models\Piso::forceCreate(['sede_id' => $sede->id, 'numero' => 2]);
    $pisoOtra = App\Models\Piso::forceCreate(['sede_id' => $otraSede->id, 'numero' => 1]);
    $dependencia = App\Models\Dependencia::forceCreate(['tipo' => 'direccion', 'nombre' => 'Dirección TIC', 'activa' => true]);

    $persona = App\Models\Persona::factory()->create([
        'nombres' => 'Marcela', 'apellidos' => 'Pérez Gómez',
        'numero_documento' => '1067896226',
    ]);

    $aqui = App\Models\Equipo::factory()->create();
    App\Models\Asignacion::create([
        'equipo_id' => $aqui->id, 'persona_id' => $persona->id,
        'piso_id' => $piso->id, 'dependencia_id' => $dependencia->id,
        'fecha_inicio' => now(),
    ]);

    $alla = App\Models\Equipo::factory()->create();
    App\Models\Asignacion::create([
        'equipo_id' => $alla->id, 'piso_id' => $pisoOtra->id,
        'dependencia_id' => $dependencia->id, 'fecha_inicio' => now(),
    ]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'inventario_general')
        ->set('filtroSede', (string) $sede->id)
        ->assertSee($aqui->serial)
        ->assertDontSee($alla->serial)
        ->assertSee('Marcela Pérez Gómez')      // nombreCompleto()
        ->assertSee('Palacio de Naín')          // sede vía piso
        ->assertSee('****6226');                // documento enmascarado (RN-12)
});
