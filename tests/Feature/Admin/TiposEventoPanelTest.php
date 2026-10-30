<?php

use App\Livewire\Admin\TiposEventoPanel;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\TipoEvento;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/*
 * RF-13 (Manuel, bloque de Administración): pantalla de tipos de evento.
 * Los 8 tipos del sistema ya existen tras la migración F3-3, sin seeder aparte.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function crearEquipoDePrueba(): Equipo
{
    $tipoEquipo = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo']);
    $marca = Marca::create(['nombre' => 'HP']);

    return Equipo::create([
        'serial' => 'SN-ADMIN-TEST-'.uniqid(),
        'tipo_equipo_id' => $tipoEquipo->id,
        'marca_id' => $marca->id,
        'propiedad' => 'gobernacion',
    ]);
}

it('muestra los 8 tipos del sistema sin acciones de editar o desactivar', function () {
    Livewire::test(TiposEventoPanel::class)
        ->assertSee('Alta')
        ->assertSee('Traslado de responsable')
        ->assertSee('No editable');
});

it('agrega un tipo de evento configurable con una clave única derivada del nombre', function () {
    Livewire::test(TiposEventoPanel::class)
        ->set('nuevoNombre', 'Mantenimiento preventivo')
        ->set('nuevaDescripcion', 'Revisión periódica sin efecto sobre el equipo.')
        ->set('nuevoRequiereFirma', true)
        ->call('agregar')
        ->assertHasNoErrors();

    $tipo = TipoEvento::where('nombre', 'Mantenimiento preventivo')->first();

    expect($tipo)->not->toBeNull()
        ->and($tipo->clave)->toBe('mantenimiento_preventivo')
        ->and($tipo->es_sistema)->toBeFalse()
        ->and($tipo->activo)->toBeTrue()
        ->and($tipo->requiere_firma)->toBeTrue();
});

it('rechaza un nombre de tipo de evento duplicado', function () {
    Livewire::test(TiposEventoPanel::class)
        ->set('nuevoNombre', 'Alta') // ya existe como tipo del sistema
        ->call('agregar')
        ->assertHasErrors(['nuevoNombre']);
});

it('un tipo nuevo creado desde Administración se puede registrar sobre un equipo sin desarrollo', function () {
    Livewire::test(TiposEventoPanel::class)
        ->set('nuevoNombre', 'Inspección eléctrica')
        ->call('agregar');

    $equipo = crearEquipoDePrueba();
    $usuario = User::factory()->create();

    $evento = app(HistorialService::class)->registrar(
        $equipo,
        'inspeccion_electrica',
        $usuario,
        descripcion: 'Inspección eléctrica de prueba.',
    );

    expect($evento->tipo)->toBe('inspeccion_electrica');
});

it('edita el nombre, la descripción y el requiere_firma de un tipo configurable', function () {
    $tipo = TipoEvento::create(['clave' => 'nota_interna', 'nombre' => 'Nota interna']);

    Livewire::test(TiposEventoPanel::class)
        ->call('abrirEdicion', $tipo->id)
        ->set('editandoNombre', 'Nota interna de seguimiento')
        ->set('editandoDescripcion', 'Para anotaciones que no cambian el equipo.')
        ->set('editandoRequiereFirma', true)
        ->call('guardarEdicion')
        ->assertHasNoErrors();

    $tipo->refresh();

    expect($tipo->nombre)->toBe('Nota interna de seguimiento')
        ->and($tipo->descripcion)->toBe('Para anotaciones que no cambian el equipo.')
        ->and($tipo->requiere_firma)->toBeTrue()
        ->and($tipo->clave)->toBe('nota_interna'); // la clave nunca cambia
});

it('desactiva y reactiva un tipo configurable', function () {
    $tipo = TipoEvento::create(['clave' => 'nota_interna', 'nombre' => 'Nota interna']);

    Livewire::test(TiposEventoPanel::class)->call('desactivar', $tipo->id);
    expect($tipo->refresh()->activo)->toBeFalse();

    Livewire::test(TiposEventoPanel::class)->call('desactivar', $tipo->id);
    expect($tipo->refresh()->activo)->toBeTrue();
});

it('abrirEdicion no abre el formulario para un tipo del sistema', function () {
    $sistema = TipoEvento::where('clave', 'alta')->first();

    Livewire::test(TiposEventoPanel::class)
        ->call('abrirEdicion', $sistema->id)
        ->assertSet('editandoId', null);
});

it('un tipo del sistema no se puede renombrar ni desactivar aunque se fuerce la llamada', function () {
    $sistema = TipoEvento::where('clave', 'alta')->first();

    // Simula un intento de saltarse la pantalla (p. ej. editando el payload de
    // Livewire a mano): guardarEdicion() y desactivar() deben rechazarlo igual,
    // no solo abrirEdicion().
    Livewire::test(TiposEventoPanel::class)
        ->set('editandoId', $sistema->id)
        ->set('editandoNombre', 'Intento de renombrar')
        ->call('guardarEdicion');

    expect($sistema->refresh()->nombre)->toBe('Alta');

    Livewire::test(TiposEventoPanel::class)->call('desactivar', $sistema->id);

    expect($sistema->refresh()->activo)->toBeTrue();
});

it('un tipo configurable con eventos no se puede borrar', function () {
    $tipo = TipoEvento::create(['clave' => 'nota_interna', 'nombre' => 'Nota interna']);
    $equipo = crearEquipoDePrueba();
    $usuario = User::factory()->create();

    app(HistorialService::class)->registrar($equipo, 'nota_interna', $usuario);

    expect(fn () => $tipo->delete())->toThrow(QueryException::class);
});
