<?php

use App\Livewire\Reportes\Definiciones\EquiposPorEstructura;
use App\Livewire\Reportes\Definiciones\PersonasPorRetirarse;
use App\Livewire\Reportes\Exportes\MatrizFor01;
use App\Livewire\Reportes\Index;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Municipio;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // EquipoFactory toma un tipo y una marca que ya existan; en un test la BD está vacía.
    TipoEquipo::forceCreate(['nombre' => 'Portátil']);
    Marca::forceCreate(['nombre' => 'Lenovo']);
});

function pisoParaReportes(bool $reservada = false): Piso
{
    $municipio = Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería']);
    $sede = Sede::forceCreate([
        'municipio_id' => $municipio->id,
        'nombre' => $reservada ? 'Casa Refugio de prueba' : 'Sede de prueba',
        'tipo' => 'social',
        'direccion' => $reservada ? 'Calle Reservada 123' : 'Carrera Abierta 45',
        'direccion_reservada' => $reservada,
    ]);

    return Piso::forceCreate(['sede_id' => $sede->id, 'numero' => 1]);
}

function asignarParaReportes(Equipo $equipo, Piso $piso, Dependencia $dependencia, ?Persona $persona = null): void
{
    Asignacion::create([
        'equipo_id' => $equipo->id,
        'persona_id' => $persona?->id,
        'piso_id' => $piso->id,
        'dependencia_id' => $dependencia->id,
        'fecha_inicio' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Personas por retirarse con equipos a cargo
// ---------------------------------------------------------------------------

it('personas por retirarse lista a quien termina pronto o ya terminó y aún tiene equipos', function () {
    $piso = pisoParaReportes();
    $dependencia = Dependencia::forceCreate(['tipo' => 'direccion', 'nombre' => 'Dirección de pruebas retiro', 'activa' => true]);

    $crear = function (string $nombres, ?string $fin, bool $conEquipo = true) use ($piso, $dependencia) {
        $persona = Persona::factory()->create([
            'nombres' => $nombres,
            'apellidos' => 'Prueba',
            'tipo_vinculacion' => 'contratista',
            'fecha_fin_vinculacion' => $fin,
            'numero_documento' => (string) random_int(1000000000, 1999999999),
        ]);

        if ($conEquipo) {
            asignarParaReportes(Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']), $piso, $dependencia, $persona);
        }

        return $persona;
    };

    $crear('Pronto', today()->addDays(10)->toDateString());
    $crear('Vencido', today()->subDays(5)->toDateString());
    $crear('Lejano', today()->addDays(300)->toDateString());
    $crear('Planta', null);
    $crear('SinEquipo', today()->addDays(10)->toDateString(), false);

    // Se comprueban las filas del reporte (lo que va a pantalla, Excel y PDF), no toda la
    // página: el desplegable «Responsable» de los filtros lista a todas las personas.
    $filas = (new PersonasPorRetirarse)->filas([]);
    $nombres = $filas->pluck(0)->all();   // columna 0: Responsable
    $situaciones = $filas->pluck(7)->all(); // columna 7: Situación

    expect($nombres)->toContain('Pronto Prueba')
        ->and($nombres)->toContain('Vencido Prueba')
        ->and($nombres)->not->toContain('Lejano Prueba')     // termina fuera de la ventana
        ->and($nombres)->not->toContain('Planta Prueba')     // sin fecha de fin de vinculación
        ->and($nombres)->not->toContain('SinEquipo Prueba')  // no tiene equipos a cargo
        ->and($situaciones)->toContain('Terminó hace 5 días');

    // Y el reporte carga en pantalla.
    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'personas_por_retirarse')
        ->assertOk()
        ->assertSee('Terminó hace 5 días');
});

it('personas por retirarse muestra el documento enmascarado (RN-12)', function () {
    $piso = pisoParaReportes();
    $dependencia = Dependencia::forceCreate(['tipo' => 'direccion', 'nombre' => 'Dirección de pruebas documento', 'activa' => true]);
    $persona = Persona::factory()->create([
        'nombres' => 'Marcela', 'apellidos' => 'Pérez',
        'numero_documento' => '1067896226',
        'fecha_fin_vinculacion' => today()->addDays(10)->toDateString(),
    ]);
    asignarParaReportes(Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']), $piso, $dependencia, $persona);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'personas_por_retirarse')
        ->assertSee('****6226')
        ->assertDontSee('1067896226');
});

// ---------------------------------------------------------------------------
// Equipos por estructura
// ---------------------------------------------------------------------------

it('equipos por estructura suma los equipos de una secretaría con los de sus direcciones', function () {
    $piso = pisoParaReportes();
    $secretaria = Dependencia::forceCreate(['tipo' => 'secretaria', 'nombre' => 'Secretaría de Prueba', 'activa' => true]);
    $direccion = Dependencia::forceCreate(['tipo' => 'direccion', 'nombre' => 'Dirección de Prueba', 'padre_id' => $secretaria->id, 'activa' => true]);

    asignarParaReportes(Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']), $piso, $secretaria);
    asignarParaReportes(Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']), $piso, $direccion);
    asignarParaReportes(Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']), $piso, $direccion);

    $filas = (new EquiposPorEstructura)->filas([]);
    $fila = fn (string $nombre) => $filas->first(fn ($f) => str_contains($f[0], $nombre));

    // Columnas: 0 Dependencia, 1 Tipo, 2 Estructura, 3 Equipos propios, 4 Total con subdependencias.
    expect($fila('Secretaría de Prueba')[3])->toBe(1)
        ->and($fila('Secretaría de Prueba')[4])->toBe(3)
        ->and($fila('Dirección de Prueba')[3])->toBe(2)
        ->and($fila('Dirección de Prueba')[4])->toBe(2);

    // Filtrar por la secretaría incluye sus direcciones; «solo esta» las excluye.
    expect((new EquiposPorEstructura)->filas(['dependencia' => $secretaria->id]))->toHaveCount(2)
        ->and((new EquiposPorEstructura)->filas(['dependencia' => $secretaria->id, 'solo_dependencia' => true]))->toHaveCount(1);

    Livewire::test(Index::class)
        ->call('seleccionarReporte', 'equipos_por_estructura')
        ->assertSee('Secretaría de Prueba')
        ->assertSee('Dirección de Prueba');
});

// ---------------------------------------------------------------------------
// RN-12 y RN-28 en todas las salidas
// ---------------------------------------------------------------------------

it('ninguna salida muestra la dirección de una sede reservada (RN-28) ni el documento completo (RN-12)', function (string $clave) {
    $piso = pisoParaReportes(reservada: true);
    $dependencia = Dependencia::forceCreate(['tipo' => 'direccion', 'nombre' => 'Dirección reservada de prueba', 'activa' => true]);
    $persona = Persona::factory()->create([
        'nombres' => 'Marcela', 'apellidos' => 'Pérez',
        'tipo_vinculacion' => 'contratista',
        'numero_documento' => '1067896226',
        'fecha_fin_vinculacion' => today()->addDays(10)->toDateString(),
    ]);
    asignarParaReportes(Equipo::factory()->create(['estado_ciclo_vida' => 'en_servicio']), $piso, $dependencia, $persona);

    // Lo que va a Excel y PDF son estas mismas filas.
    $definicion = new (Index::DEFINICIONES[$clave]);
    $salida = json_encode($definicion->filas([]), JSON_UNESCAPED_UNICODE);

    expect($salida)->not->toContain('Calle Reservada 123')
        ->and($salida)->not->toContain('1067896226');

    if ($clave === 'matriz_for01') {
        $excel = json_encode((new MatrizFor01(Equipo::all()))->array(), JSON_UNESCAPED_UNICODE);

        expect($excel)->not->toContain('Calle Reservada 123')
            ->and($excel)->not->toContain('1067896226');
    }

    Livewire::test(Index::class)
        ->call('seleccionarReporte', $clave)
        ->assertDontSee('Calle Reservada 123')
        ->assertDontSee('1067896226');
})->with(fn () => array_keys(Index::DEFINICIONES));