<?php

use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Municipio;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * F3-1 (RF-05): puestos_trabajo.activo, unique(piso_id, nombre_o_codigo) y la
 * eliminación de equipos.puesto_trabajo_id (resto de v3 sin uso).
 */

uses(RefreshDatabase::class);

function pisoDePrueba(): Piso
{
    $municipio = Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería']);
    $sede = Sede::create([
        'nombre' => 'Sede de prueba '.uniqid(),
        'municipio_id' => $municipio->id,
        'tipo' => 'administrativa',
        'tenencia' => 'propia',
    ]);

    return Piso::create(['sede_id' => $sede->id, 'numero' => 1]);
}

it('un puesto nace activo por defecto', function () {
    $puesto = PuestoTrabajo::create([
        'nombre_o_codigo' => 'PT-TEST-'.uniqid(),
        'piso_id' => pisoDePrueba()->id,
    ]);

    // El default lo pone la base de datos, no el modelo en memoria: hay que
    // volver a leerlo para comprobarlo (create() no devuelve los defaults).
    expect($puesto->fresh()->activo)->toBeTrue();
});

it('no permite dos puestos con el mismo nombre en el mismo piso', function () {
    $piso = pisoDePrueba();

    PuestoTrabajo::create(['nombre_o_codigo' => 'PT-DUP', 'piso_id' => $piso->id]);

    expect(fn () => PuestoTrabajo::create(['nombre_o_codigo' => 'PT-DUP', 'piso_id' => $piso->id]))
        ->toThrow(QueryException::class);
});

it('permite el mismo nombre de puesto en pisos distintos', function () {
    $piso1 = pisoDePrueba();
    $piso2 = pisoDePrueba();

    PuestoTrabajo::create(['nombre_o_codigo' => 'PT-REPETIDO', 'piso_id' => $piso1->id]);
    $segundo = PuestoTrabajo::create(['nombre_o_codigo' => 'PT-REPETIDO', 'piso_id' => $piso2->id]);

    expect($segundo->exists)->toBeTrue();
});

it('un puesto se puede desactivar sin borrarse', function () {
    $puesto = PuestoTrabajo::create(['nombre_o_codigo' => 'PT-DESACTIVAR', 'piso_id' => pisoDePrueba()->id]);

    $puesto->update(['activo' => false]);

    expect($puesto->fresh()->activo)->toBeFalse();
    $this->assertDatabaseHas('puestos_trabajo', ['id' => $puesto->id]);
});

it('equipos ya no tiene la columna puesto_trabajo_id', function () {
    expect(\Illuminate\Support\Facades\Schema::hasColumn('equipos', 'puesto_trabajo_id'))->toBeFalse();
});

it('un equipo se crea sin depender de puesto_trabajo_id', function () {
    $tipoEquipo = TipoEquipo::firstOrCreate(['nombre' => 'PC de escritorio'], ['familia' => 'computo']);
    $marca = Marca::firstOrCreate(['nombre' => 'HP']);

    $equipo = Equipo::create([
        'serial' => 'SN-PUESTO-TEST',
        'tipo_equipo_id' => $tipoEquipo->id,
        'marca_id' => $marca->id,
        'propiedad' => 'gobernacion',
    ]);

    expect($equipo->exists)->toBeTrue();
});
