<?php

use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Livewire\Reportes\Index;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Municipio;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\ValoracionActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function datosVal(array $cambios = []): array
{
    return array_merge([
        'contiene_datos_personales' => 'no',
        'clasificacion_datos' => 'no_aplica',
        'infraestructura_critica' => 'no',
        'continuidad_negocio' => 'no',
        'propietario_cargo' => 'Director TIC',
        'custodio_cargo' => 'Ingeniero de Soporte',
        'confidencialidad' => 2,
        'integridad' => 2,
        'disponibilidad' => 2,
    ], $cambios);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->tipo = TipoEquipo::forceCreate(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'tipo_activo' => 'hardware']);
    Marca::forceCreate(['nombre' => 'HP']);
});

// ---------------------------------------------------------------------------
// RF-53: matriz en el formato oficial FOR.01
// ---------------------------------------------------------------------------

it('la matriz FOR.01 trae las mismas columnas y en el mismo orden del formato', function () {
    expect(MatrizFor01::columnas())->toBe([
        'ID ACTIVO', 'Código de activo', 'Sucursal', 'Proceso', 'Área o Dependencia',
        'Nombre del Activo', 'Descripción del Activo', 'Tipo de Activo',
        'Contiene datos personales', 'Clasificación datos personales', 'Infraestructura crítica',
        'Continuidad del negocio', 'Propietario del activo (cargo)', 'Custodio técnico del activo (cargo)',
        'Tipo ubicación del activo', 'Tiempo de retención de los datos',
        'Confidencialidad', 'Integridad', 'Disponibilidad', 'Total', 'Importancia', 'Requiere gestión de riesgos',
    ]);
});

it('el Excel de la matriz lleva el encabezado oficial, la fila de grupos y la de columnas', function () {
    $filas = (new MatrizFor01(collect()))->array();

    expect($filas[0][0])->toContain('FOR.01')
        ->and($filas[1][0])->toBe('Identificación')
        ->and($filas[2])->toBe(MatrizFor01::columnas());
});

it('la matriz muestra la valoración vigente del equipo', function () {
    $equipo = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id, 'codigo_activo' => 'I1-024147']);
    ValoracionActivo::registrar($equipo, auth()->user(), datosVal()); // total 6 → media

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'matriz_for01')
        ->assertSee('I1-024147')
        ->assertSee('Media');
});

// ---------------------------------------------------------------------------
// RF-54: activos que requieren gestión de riesgos
// ---------------------------------------------------------------------------

it('un equipo con un criterio CID = 4 aparece en riesgos aunque la importancia sea media (RN-23)', function () {
    $riesgo = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    ValoracionActivo::registrar($riesgo, auth()->user(), datosVal(['confidencialidad' => 4, 'integridad' => 1, 'disponibilidad' => 1]));

    $tranquilo = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    ValoracionActivo::registrar($tranquilo, auth()->user(), datosVal(['confidencialidad' => 1, 'integridad' => 1, 'disponibilidad' => 1]));

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'requiere_gestion_riesgo')
        ->assertSee($riesgo->serial)
        ->assertDontSee($tranquilo->serial);
});

// ---------------------------------------------------------------------------
// RF-34: filtro por dependencia con o sin subdependencias
// ---------------------------------------------------------------------------

it('filtrar por una secretaría incluye los equipos de sus direcciones, y «solo esta» los excluye', function () {
    $muni = Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería']);
    $sede = Sede::create(['nombre' => 'Palacio', 'tipo' => 'administrativa', 'municipio_id' => $muni->id]);
    $piso = Piso::create(['sede_id' => $sede->id, 'numero' => 1]);
    $secretaria = Dependencia::create(['nombre' => 'Secretaría de Hacienda', 'tipo' => 'secretaria']);
    $direccion = Dependencia::create(['nombre' => 'Dirección de Rentas', 'tipo' => 'direccion', 'padre_id' => $secretaria->id]);

    $equipo = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    Asignacion::create(['equipo_id' => $equipo->id, 'piso_id' => $piso->id, 'dependencia_id' => $direccion->id, 'fecha_inicio' => now()]);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'inventario_general')
        ->set('filtroDependencia', (string) $secretaria->id)
        ->assertSee($equipo->serial)
        ->set('filtroSoloDependencia', true)
        ->assertDontSee($equipo->serial);
});
