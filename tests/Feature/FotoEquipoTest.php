<?php

use App\Models\Equipo;
use App\Models\FotoEquipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * F3-2 (RF-06): tabla fotos_equipo y modelo FotoEquipo. RN-22: como máximo
 * una foto principal por equipo.
 */

uses(RefreshDatabase::class);

function equipoParaFotos(): Equipo
{
    $tipoEquipo = TipoEquipo::firstOrCreate(['nombre' => 'PC de escritorio'], ['familia' => 'computo']);
    $marca = Marca::firstOrCreate(['nombre' => 'HP']);

    return Equipo::create([
        'serial' => 'SN-FOTO-'.uniqid(),
        'tipo_equipo_id' => $tipoEquipo->id,
        'marca_id' => $marca->id,
        'propiedad' => 'gobernacion',
    ]);
}

it('sube una foto y queda enlazada al equipo y a quien la subió', function () {
    $equipo = equipoParaFotos();
    $usuario = User::factory()->create();

    $foto = FotoEquipo::create([
        'equipo_id' => $equipo->id,
        'archivo_path' => 'fotos-equipo/prueba.jpg',
        'descripcion' => 'Vista frontal',
        'subida_por' => $usuario->id,
    ]);

    expect($foto->equipo->id)->toBe($equipo->id)
        ->and($foto->subidaPor->id)->toBe($usuario->id)
        ->and($equipo->fotos()->count())->toBe(1);
});

it('al guardar una foto como principal, degrada automáticamente a la anterior', function () {
    $equipo = equipoParaFotos();
    $usuario = User::factory()->create();

    $primera = FotoEquipo::create([
        'equipo_id' => $equipo->id,
        'archivo_path' => 'fotos-equipo/una.jpg',
        'es_principal' => true,
        'subida_por' => $usuario->id,
    ]);

    $segunda = FotoEquipo::create([
        'equipo_id' => $equipo->id,
        'archivo_path' => 'fotos-equipo/dos.jpg',
        'es_principal' => true,
        'subida_por' => $usuario->id,
    ]);

    expect($primera->fresh()->es_principal)->toBeFalse()
        ->and($segunda->fresh()->es_principal)->toBeTrue()
        ->and($equipo->fotoPrincipal->id)->toBe($segunda->id);
});

it('marcar como principal una foto ya existente degrada a la que lo era', function () {
    $equipo = equipoParaFotos();
    $usuario = User::factory()->create();

    $principal = FotoEquipo::create([
        'equipo_id' => $equipo->id, 'archivo_path' => 'fotos-equipo/una.jpg',
        'es_principal' => true, 'subida_por' => $usuario->id,
    ]);
    $otra = FotoEquipo::create([
        'equipo_id' => $equipo->id, 'archivo_path' => 'fotos-equipo/dos.jpg',
        'es_principal' => false, 'subida_por' => $usuario->id,
    ]);

    $otra->update(['es_principal' => true]);

    expect($principal->fresh()->es_principal)->toBeFalse()
        ->and($otra->fresh()->es_principal)->toBeTrue();
});

it('no afecta la foto principal de otro equipo', function () {
    $equipoA = equipoParaFotos();
    $equipoB = equipoParaFotos();
    $usuario = User::factory()->create();

    $principalA = FotoEquipo::create([
        'equipo_id' => $equipoA->id, 'archivo_path' => 'fotos-equipo/a.jpg',
        'es_principal' => true, 'subida_por' => $usuario->id,
    ]);
    FotoEquipo::create([
        'equipo_id' => $equipoB->id, 'archivo_path' => 'fotos-equipo/b.jpg',
        'es_principal' => true, 'subida_por' => $usuario->id,
    ]);

    expect($principalA->fresh()->es_principal)->toBeTrue();
});
