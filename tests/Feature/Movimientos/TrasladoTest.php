<?php

use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\TrasladoForm;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);
});

function datosTraslado(?Persona $persona, array $extra = []): array
{
    return array_merge([
        'persona' => $persona,
        'piso_id' => Piso::query()->orderByDesc('id')->value('id'),
        'dependencia_id' => $persona?->dependencia_id,
        'fecha' => now()->toDateString(),
        'motivo' => 'Cambio de dependencia del funcionario',
    ], $extra);
}

it('cierra la asignación anterior, abre la nueva y deja una sola abierta (RN-04)', function () {
    $anterior = Apoyo::persona(['nombres' => 'Ana', 'apellidos' => 'Anterior']);
    $nueva = Apoyo::persona(['nombres' => 'Nicolás', 'apellidos' => 'Nuevo']);
    $equipo = Apoyo::equipo($anterior);

    $evento = app(RegistroMovimientos::class)->trasladar($equipo, $this->usuario, datosTraslado($nueva));

    $abiertas = Asignacion::where('equipo_id', $equipo->id)->whereNull('fecha_fin')->get();
    expect($abiertas)->toHaveCount(1)
        ->and($abiertas->first()->persona_id)->toBe($nueva->id)
        ->and($abiertas->first()->evento_origen_id)->toBe($evento->id);

    $cerrada = Asignacion::where('equipo_id', $equipo->id)->where('persona_id', $anterior->id)->first();
    expect($cerrada->fecha_fin)->not->toBeNull();
    expect($equipo->fresh()->estado_ciclo_vida)->toBe('en_servicio');
});

it('crea el evento a nombre del usuario en sesión, pendiente de firma (RF-11, RN-05)', function () {
    $equipo = Apoyo::equipo();

    $evento = app(RegistroMovimientos::class)->trasladar($equipo, $this->usuario, datosTraslado(Apoyo::persona()));

    expect($evento->tipo)->toBe('traslado_responsable')
        ->and($evento->usuario_id)->toBe($this->usuario->id)
        ->and($evento->estado_firma)->toBe('pendiente_de_firma')
        ->and($evento->descripcion)->toContain('Motivo: Cambio de dependencia');
});

it('genera los dos formatos prellenados en PDF (RF-20, RF-28, RF-29)', function () {
    $equipo = Apoyo::equipo();

    $evento = app(RegistroMovimientos::class)->trasladar($equipo, $this->usuario, datosTraslado(Apoyo::persona()));

    $documentos = Documento::where('evento_id', $evento->id)->get();
    expect($documentos->pluck('tipo')->sort()->values()->all())->toBe(['formato_baja', 'formato_entrega'])
        ->and($documentos->every(fn ($d) => $d->firmado === false))->toBeTrue();

    foreach ($documentos as $documento) {
        Storage::disk('local')->assertExists($documento->archivo_path);
        expect(Storage::disk('local')->get($documento->archivo_path))->toStartWith('%PDF');
    }
});

it('permite dejar el equipo sin asignar (bodega) como estado válido (RF-21)', function () {
    $equipo = Apoyo::equipo();

    app(RegistroMovimientos::class)->trasladar($equipo, $this->usuario, datosTraslado(null));

    $actual = $equipo->fresh()->asignacionActual;
    expect($actual->persona_id)->toBeNull()
        ->and($equipo->fresh()->estado_ciclo_vida)->toBe('sin_asignar');
});

it('registra el traslado desde la pantalla con una persona nueva (RF-18)', function () {
    $equipo = Apoyo::equipo();
    $dependencia = Dependencia::query()->value('id');

    Livewire::test(TrasladoForm::class, ['equipo' => $equipo])
        ->set('modoResponsable', 'nueva')
        ->set('nombres', 'Laura')
        ->set('apellidos', 'Gómez')
        ->set('numeroDocumento', '1067123456')
        ->set('tipoVinculacion', 'contratista')
        ->set('numeroContrato', 'CPS-0145-2026')
        ->set('dependenciaId', $dependencia)
        ->set('motivo', 'Ingreso de contratista')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSet('eventoId', fn ($id) => $id !== null)
        ->assertSee('Traslado registrado');

    $persona = Persona::where('numero_documento', '1067123456')->first();
    expect($persona)->not->toBeNull()
        ->and($persona->tipo_vinculacion)->toBe('contratista')
        ->and($equipo->fresh()->asignacionActual->persona_id)->toBe($persona->id);
});

it('valida los datos obligatorios del responsable nuevo y la cédula única', function () {
    $existente = Apoyo::persona(['numero_documento' => '1067000111']);
    $equipo = Apoyo::equipo();

    Livewire::test(TrasladoForm::class, ['equipo' => $equipo])
        ->set('modoResponsable', 'nueva')
        ->set('nombres', '')
        ->set('numeroDocumento', $existente->numero_documento)
        ->set('motivo', 'x')
        ->call('guardar')
        ->assertHasErrors(['nombres' => 'required', 'numeroDocumento' => 'unique', 'tipoVinculacion' => 'required']);

    expect(Evento::where('equipo_id', $equipo->id)->where('tipo', 'traslado_responsable')->count())->toBe(0);
});

it('no acepta una fecha futura (RN-13)', function () {
    $equipo = Apoyo::equipo();

    Livewire::test(TrasladoForm::class, ['equipo' => $equipo])
        ->set('personaId', Apoyo::persona()->id)
        ->set('dependenciaId', Dependencia::query()->value('id'))
        ->set('fecha', now()->addDays(3)->toDateString())
        ->set('motivo', 'Prueba')
        ->call('guardar')
        ->assertHasErrors(['fecha']);
});

it('un traslado guarda el piso elegido y la sede es la de ese piso (RN-21)', function () {
    $equipo = Apoyo::equipo();
    $piso = Piso::with('sede')->whereNotNull('sede_id')->orderByDesc('id')->first();

    Livewire::test(TrasladoForm::class, ['equipo' => $equipo])
        ->set('personaId', Apoyo::persona()->id)
        ->set('sedeId', $piso->sede_id)
        ->set('pisoId', $piso->id)
        ->set('dependenciaId', Dependencia::query()->value('id'))
        ->set('motivo', 'Cambio de oficina')
        ->call('guardar')
        ->assertHasNoErrors();

    $actual = $equipo->fresh()->asignacionActual;

    expect($actual->piso_id)->toBe($piso->id)
        ->and($actual->piso->sede_id)->toBe($piso->sede_id);
});

it('no acepta un piso que no es de la sede elegida', function () {
    $equipo = Apoyo::equipo();
    $piso = Piso::whereNotNull('sede_id')->first();
    $otraSede = Sede::where('id', '!=', $piso->sede_id)->value('id');

    Livewire::test(TrasladoForm::class, ['equipo' => $equipo])
        ->set('personaId', Apoyo::persona()->id)
        ->set('sedeId', $otraSede)
        ->set('pisoId', $piso->id)
        ->set('dependenciaId', Dependencia::query()->value('id'))
        ->set('motivo', 'Prueba')
        ->call('guardar')
        ->assertHasErrors(['pisoId' => 'exists']);
});
