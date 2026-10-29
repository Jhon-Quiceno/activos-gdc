<?php

use App\Livewire\Movimientos\ComponenteForm;
use App\Models\CambioComponente;
use App\Models\Componente;
use App\Models\Equipo;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/*
 * RF-17: destino del componente retirado (bodega, otro equipo con su
 * referencia, o descarte). Con «otro equipo», el componente aparece en la
 * configuración actual del equipo destino, con su propio evento.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);

    $this->origen = Apoyo::equipo();
    $this->destino = Apoyo::equipo();
    $this->ram = Componente::query()
        ->where('equipo_id', $this->origen->id)
        ->whereHas('tipoComponente', fn ($q) => $q->where('nombre', 'RAM'))
        ->firstOrFail();
});

function retirarHacia(Equipo $origen, Componente $componente, string $destino, ?string $busqueda = null)
{
    $prueba = Livewire::test(ComponenteForm::class, ['equipo' => $origen])
        ->set('accion', 'quitar')
        ->set('componenteRetiradoId', $componente->id)
        ->set('destinoRetirado', $destino)
        ->set('motivo', 'Se reasigna la memoria');

    if ($busqueda !== null) {
        $prueba->set('busquedaDestino', $busqueda);
    }

    return $prueba->call('guardar');
}

it('retirar con destino «otro equipo» lo deja en la configuración del equipo destino, con su propio evento', function () {
    retirarHacia($this->origen, $this->ram, 'otro_equipo', $this->destino->serial)->assertHasNoErrors();

    // Sale de la configuración del origen…
    expect($this->ram->fresh()->fecha_retiro)->not->toBeNull();

    // …y entra a la del destino con el mismo serial.
    $enDestino = Componente::query()
        ->where('equipo_id', $this->destino->id)
        ->whereNull('fecha_retiro')
        ->where('serial', $this->ram->serial)
        ->first();
    expect($enDestino)->not->toBeNull()
        ->and($enDestino->tipo_componente_id)->toBe($this->ram->tipo_componente_id)
        ->and($enDestino->capacidad_caracteristica)->toBe($this->ram->capacidad_caracteristica);

    // El origen guarda el destino con su referencia.
    $cambioOrigen = CambioComponente::query()
        ->whereHas('evento', fn ($q) => $q->where('equipo_id', $this->origen->id))
        ->firstOrFail();
    expect($cambioOrigen->destino_retirado)->toBe('otro_equipo')
        ->and($cambioOrigen->equipo_destino_id)->toBe($this->destino->id);

    // El destino tiene su propio evento, a nombre del usuario en sesión.
    $eventoDestino = Evento::query()
        ->where('equipo_id', $this->destino->id)
        ->where('tipo', 'cambio_componente')
        ->firstOrFail();
    expect($eventoDestino->usuario_id)->toBe($this->usuario->id)
        ->and($eventoDestino->descripcion)->toContain($this->origen->codigo_activo)
        ->and($eventoDestino->cambioComponente->accion)->toBe('agregar')
        ->and($eventoDestino->cambioComponente->componente_instalado_id)->toBe($enDestino->id);
});

it('encuentra el equipo destino por código de activo escrito sin guion', function () {
    $sinGuion = str_replace('-', ' ', $this->destino->codigo_activo);

    Livewire::test(ComponenteForm::class, ['equipo' => $this->origen])
        ->set('accion', 'quitar')
        ->set('destinoRetirado', 'otro_equipo')
        ->set('busquedaDestino', $sinGuion)
        ->assertSet('equipoDestinoId', $this->destino->id);
});

it('exige el equipo destino cuando el destino es «otro equipo»', function () {
    retirarHacia($this->origen, $this->ram, 'otro_equipo', 'NO-EXISTE-999')
        ->assertHasErrors(['equipoDestinoId' => 'required']);

    expect($this->ram->fresh()->fecha_retiro)->toBeNull();
});

it('no deja mandar el componente al mismo equipo del que sale', function () {
    retirarHacia($this->origen, $this->ram, 'otro_equipo', $this->origen->serial)
        ->assertHasErrors(['equipoDestinoId' => 'not_in']);
});

it('no deja mandar el componente a un equipo dado de baja (RN-10)', function () {
    Equipo::query()->whereKey($this->destino->id)->update(['estado_ciclo_vida' => 'dado_de_baja']);

    retirarHacia($this->origen, $this->ram, 'otro_equipo', $this->destino->serial)
        ->assertHasErrors(['equipoDestinoId']);

    expect($this->ram->fresh()->fecha_retiro)->toBeNull()
        ->and(Evento::query()->where('tipo', 'cambio_componente')->count())->toBe(0);
});

it('con destino bodega o descarte el componente no aparece en ningún otro equipo', function (string $destino) {
    retirarHacia($this->origen, $this->ram, $destino)->assertHasNoErrors();

    $cambio = CambioComponente::query()->firstOrFail();
    expect($cambio->destino_retirado)->toBe($destino)
        ->and($cambio->equipo_destino_id)->toBeNull()
        ->and(Componente::query()->where('serial', $this->ram->serial)->whereNull('fecha_retiro')->count())->toBe(0)
        ->and(Evento::query()->where('tipo', 'cambio_componente')->count())->toBe(1);
})->with(['bodega', 'descarte']);

it('un cambio con destino «otro equipo» instala el nuevo y manda el viejo al destino', function () {
    $tipoRam = $this->ram->tipo_componente_id;

    Livewire::test(ComponenteForm::class, ['equipo' => $this->origen])
        ->set('accion', 'cambiar')
        ->set('componenteRetiradoId', $this->ram->id)
        ->set('destinoRetirado', 'otro_equipo')
        ->set('busquedaDestino', $this->destino->serial)
        ->set('tipoComponenteId', $tipoRam)
        ->set('caracteristica', '16GB')
        ->set('serial', 'RAM-NUEVA-01')
        ->set('motivo', 'Ampliación de memoria')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Componente::query()->where('equipo_id', $this->origen->id)->whereNull('fecha_retiro')->where('serial', 'RAM-NUEVA-01')->exists())->toBeTrue()
        ->and(Componente::query()->where('equipo_id', $this->destino->id)->whereNull('fecha_retiro')->where('serial', $this->ram->serial)->exists())->toBeTrue();
});
