<?php

use App\Livewire\Reportes\Definiciones\InventarioGeneral;
use App\Livewire\Reportes\Index;
use App\Models\Asignacion;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Municipio;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\ValoracionActivo;
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
    $propio = Equipo::factory()->create(['propiedad' => 'gobernacion']);
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
// Todos los reportes cargan sin errores
//

// ---------------------------------------------------------------------------
// Todos los reportes cargan sin errores
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
    'matriz_for01',
    'requiere_gestion_riesgo',
    'por_importancia',
    'garantias_por_vencer',
    'vida_util_superada',
]);

// ---------------------------------------------------------------------------
// Reportes de inventario
// ---------------------------------------------------------------------------

it('el reporte de terceros solo muestra equipos de terceros', function () {
    $propio = Equipo::factory()->create(['propiedad' => 'gobernacion']);
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

it('calidad señala los equipos sin valoración', function () {
    $sinValorar = Equipo::factory()->create();
    $valorado = Equipo::factory()->create();

    ValoracionActivo::registrar($valorado, User::factory()->create(), [
        'contiene_datos_personales' => 'no',
        'clasificacion_datos' => 'no_aplica',
        'infraestructura_critica' => 'no',
        'continuidad_negocio' => 'no',
        'propietario_cargo' => 'Director TIC',
        'custodio_cargo' => 'Ingeniero de Soporte',
        'confidencialidad' => 2,
        'integridad' => 2,
        'disponibilidad' => 2,
    ]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'sin_valoracion')
        ->assertSee($sinValorar->serial)
        ->assertDontSee($valorado->serial);
});

it('calidad señala los equipos sin datos de adquisición', function () {
    $sinDatos = Equipo::factory()->create(['fecha_adquisicion' => null]);
    $conDatos = Equipo::factory()->create(['fecha_adquisicion' => today()->subYear()]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'calidad_inventario')
        ->set('filtroSistemaOperativo', 'sin_adquisicion')
        ->assertSee($sinDatos->serial)
        ->assertDontSee($conDatos->serial);
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
// ---------------------------------------------------------------------------

it('filtra por sede a través del piso y muestra el nombre completo del responsable', function () {
    $municipio = Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería']);
    $sede = Sede::forceCreate(['municipio_id' => $municipio->id, 'nombre' => 'Palacio de Naín', 'tipo' => 'administrativa']);
    $otraSede = Sede::forceCreate(['municipio_id' => $municipio->id, 'nombre' => 'Edificio Anexo', 'tipo' => 'administrativa']);
    $piso = Piso::forceCreate(['sede_id' => $sede->id, 'numero' => 2]);
    $pisoOtra = Piso::forceCreate(['sede_id' => $otraSede->id, 'numero' => 1]);
    $dependencia = Dependencia::forceCreate(['tipo' => 'direccion', 'nombre' => 'Dirección TIC', 'activa' => true]);

    $persona = Persona::factory()->create([
        'nombres' => 'Marcela', 'apellidos' => 'Pérez Gómez',
        'numero_documento' => '1067896226',
    ]);

    $aqui = Equipo::factory()->create();
    Asignacion::create([
        'equipo_id' => $aqui->id, 'persona_id' => $persona->id,
        'piso_id' => $piso->id, 'dependencia_id' => $dependencia->id,
        'fecha_inicio' => now(),
    ]);

    $alla = Equipo::factory()->create();
    Asignacion::create([
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

// ---------------------------------------------------------------------------
// Fase 2: obsolescencia por fin de soporte, garantías y vida útil
// ---------------------------------------------------------------------------

it('obsolescencia usa la fecha real de fin de soporte, no el nombre del sistema', function () {
    $viejo = SistemaOperativo::create(['nombre' => 'Windows 7', 'fin_soporte' => '2020-01-14']);
    $vigente = SistemaOperativo::create(['nombre' => 'Windows 11', 'fin_soporte' => today()->addYears(3)]);

    $sinSoporte = Equipo::factory()->create();
    $conSoporte = Equipo::factory()->create();
    ConfiguracionComputo::create(['equipo_id' => $sinSoporte->id, 'sistema_operativo_id' => $viejo->id, 'tiene_antivirus' => true]);
    ConfiguracionComputo::create(['equipo_id' => $conSoporte->id, 'sistema_operativo_id' => $vigente->id, 'tiene_antivirus' => true]);

    expect($viejo->sinSoporte())->toBeTrue()
        ->and($vigente->sinSoporte())->toBeFalse();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'obsolescencia_so')
        ->assertSee($sinSoporte->serial)
        ->assertDontSee($conSoporte->serial);
});

it('garantías por vencer lista solo las que vencen dentro de la ventana', function () {
    $pronto = Equipo::factory()->create(['fecha_fin_garantia' => today()->addDays(30)]);
    $lejos = Equipo::factory()->create(['fecha_fin_garantia' => today()->addDays(200)]);
    $vencida = Equipo::factory()->create(['fecha_fin_garantia' => today()->subDay()]);

    // El reporte y el modelo aplican la misma regla (Equipo::garantiaPorVencer).
    expect($pronto->garantiaPorVencer())->toBeTrue()
        ->and($lejos->garantiaPorVencer())->toBeFalse()
        ->and($vencida->garantiaPorVencer())->toBeFalse();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'garantias_por_vencer')
        ->assertSee($pronto->serial)
        ->assertDontSee($lejos->serial)
        ->assertDontSee($vencida->serial);
});

it('vida útil superada lista los equipos que ya cumplieron su vida útil', function () {
    $viejo = Equipo::factory()->create(['fecha_adquisicion' => today()->subYears(5), 'vida_util_meses' => 36]);
    $nuevo = Equipo::factory()->create(['fecha_adquisicion' => today()->subYear(), 'vida_util_meses' => 36]);

    expect($viejo->vidaUtilSuperada())->toBeTrue()
        ->and($nuevo->vidaUtilSuperada())->toBeFalse();

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'vida_util_superada')
        ->assertSee($viejo->serial)
        ->assertDontSee($nuevo->serial);
});