<?php

use App\Livewire\Movimientos\BajaForm;
use App\Livewire\Movimientos\ComponenteForm;
use App\Livewire\Movimientos\DiagnosticoForm;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\TrasladoForm;
use App\Models\Evento;
use App\Models\MotivoBaja;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoComponente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);
    $this->equipo = Apoyo::equipo();
});

function registrarBaja($equipo, $usuario): Evento
{
    return app(RegistroMovimientos::class)->darDeBaja($equipo, $usuario, [
        'motivo_baja_id' => MotivoBaja::query()->value('id'),
        'fecha' => now()->toDateString(),
        'estado_encontrado' => 'No enciende, olor a quemado en la fuente.',
        'diagnostico' => 'Tarjeta madre en corto, reparación no viable.',
        'recomendaciones' => 'Reemplazar por un equipo nuevo.',
    ]);
}

it('registra la baja con diagnóstico y formato, pero el equipo sigue igual hasta firmar (RF-25)', function () {
    $baja = registrarBaja($this->equipo, $this->usuario);

    expect($baja->tipo)->toBe('baja')
        ->and($baja->usuario_id)->toBe($this->usuario->id)
        ->and($baja->estado_firma)->toBe('pendiente_de_firma')
        ->and($baja->diagnostico->es_baja)->toBeTrue()
        ->and($baja->diagnostico->motivo_baja_id)->not->toBeNull()
        ->and($baja->documentos()->where('tipo', 'formato_baja')->where('firmado', false)->exists())->toBeTrue()
        ->and($this->equipo->fresh()->estado_ciclo_vida)->toBe('en_servicio');
});

it('pasa a «Dado de baja» al subir el formato de baja firmado', function () {
    $baja = registrarBaja($this->equipo, $this->usuario);

    app(GestorFirmas::class)->subirFirmado($baja, 'formato_baja', Apoyo::pdfFirmado());

    expect($baja->fresh()->estado_firma)->toBe('completo')
        ->and($this->equipo->fresh()->estado_ciclo_vida)->toBe('dado_de_baja');
});

it('bloquea nuevos eventos en un equipo dado de baja (RN-10)', function () {
    $baja = registrarBaja($this->equipo, $this->usuario);
    app(GestorFirmas::class)->subirFirmado($baja, 'formato_baja', Apoyo::pdfFirmado());

    $persona = Apoyo::persona();
    expect(fn () => app(RegistroMovimientos::class)->trasladar($this->equipo->fresh(), $this->usuario, [
        'persona' => $persona,
        'sede_id' => Sede::query()->value('id'),
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $persona->dependencia_id,
        'fecha' => now()->toDateString(),
        'motivo' => 'No debería poder',
    ]))->toThrow(ValidationException::class);

    expect(fn () => registrarBaja($this->equipo->fresh(), $this->usuario))->toThrow(ValidationException::class);

    Livewire::test(DiagnosticoForm::class, ['equipo' => $this->equipo->fresh()])
        ->set('estadoEncontrado', 'no_funciona')
        ->set('descripcionEstado', 'x')
        ->set('causa', 'x')
        ->call('guardar')
        ->assertHasErrors(['equipo']);

    Livewire::test(ComponenteForm::class, ['equipo' => $this->equipo->fresh()])
        ->set('tipoComponenteId', TipoComponente::query()->value('id'))
        ->set('motivo', 'x')
        ->call('guardar')
        ->assertHasErrors(['equipo']);

    expect(Evento::where('equipo_id', $this->equipo->id)->where('id', '>', $baja->id)->count())->toBe(0);
});

it('no permite trasladar un equipo con la baja en trámite', function () {
    registrarBaja($this->equipo, $this->usuario);

    Livewire::test(TrasladoForm::class, ['equipo' => $this->equipo->fresh()])
        ->assertSee('baja en trámite');
});

it('anula una baja con un evento justificado y devuelve el equipo a su estado (RF-26)', function () {
    $baja = registrarBaja($this->equipo, $this->usuario);
    app(GestorFirmas::class)->subirFirmado($baja, 'formato_baja', Apoyo::pdfFirmado());

    Livewire::test(BajaForm::class, ['equipo' => $this->equipo->fresh()])
        ->assertSee('Equipo dado de baja')
        ->set('mostrarAnulacion', true)
        ->set('justificacion', 'Se dio de baja el equipo equivocado por error de serial.')
        ->call('anular')
        ->assertHasNoErrors();

    $anulacion = Evento::where('tipo', 'anulacion_aclaracion')->first();
    expect($anulacion->evento_anulado_id)->toBe($baja->id)
        ->and($anulacion->usuario_id)->toBe($this->usuario->id)
        ->and($this->equipo->fresh()->estado_ciclo_vida)->toBe('en_servicio')
        // La baja original queda intacta en el historial (RN-06).
        ->and($baja->fresh()->tipo)->toBe('baja');
});

it('anular una baja en trámite la saca de pendientes de firma', function () {
    $baja = registrarBaja($this->equipo, $this->usuario);

    app(RegistroMovimientos::class)->anularBaja($this->equipo, $this->usuario, 'Registrada sobre el equipo equivocado.');

    expect($baja->fresh()->estado_firma)->toBe('no_aplica')
        ->and(Evento::where('estado_firma', 'pendiente_de_firma')->count())->toBe(0)
        ->and($this->equipo->fresh()->estado_ciclo_vida)->toBe('en_servicio');
});

it('no da de baja equipos de terceros (CU-08)', function () {
    $tercero = Apoyo::equipo(atributos: ['propiedad' => 'tercero', 'propietario_tercero' => 'Proveedor S.A.S.']);

    registrarBaja($tercero, $this->usuario);
})->throws(ValidationException::class);

it('registra la baja desde la pantalla con evidencias', function () {
    Livewire::test(BajaForm::class, ['equipo' => $this->equipo])
        ->set('motivoBajaId', MotivoBaja::query()->value('id'))
        ->set('estadoEncontrado', 'Pantalla rota')
        ->set('diagnostico', 'Panel LCD dañado sin repuesto')
        ->set('evidencias', [UploadedFile::fake()->image('foto1.jpg'), UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf')])
        ->call('confirmar')
        ->assertHasNoErrors()
        ->assertSee('Baja pendiente de firma');

    $baja = Evento::where('equipo_id', $this->equipo->id)->where('tipo', 'baja')->first();
    expect($baja->documentos()->where('tipo', 'foto')->count())->toBe(1)
        ->and($baja->documentos()->where('tipo', 'otro')->count())->toBe(1);
});

it('registra un diagnóstico con evidencia y, si se pide, con formato pendiente de firma (RF-24)', function () {
    Livewire::test(DiagnosticoForm::class, ['equipo' => $this->equipo])
        ->set('estadoEncontrado', 'funciona_con_fallas')
        ->set('descripcionEstado', 'Se reinicia solo')
        ->set('causa', 'Fuente de poder inestable')
        ->set('recomendaciones', 'Cambiar fuente')
        ->set('evidencias', [UploadedFile::fake()->image('fuente.png')])
        ->set('generarFormato', true)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Diagnóstico registrado');

    $evento = Evento::where('tipo', 'diagnostico')->first();
    expect($evento->estado_firma)->toBe('pendiente_de_firma')
        ->and($evento->diagnostico->es_baja)->toBeFalse()
        ->and($evento->diagnostico->estado_encontrado)->toStartWith('Funciona con fallas')
        ->and($evento->documentos()->where('tipo', 'foto')->count())->toBe(1)
        ->and($evento->documentos()->where('tipo', 'formato_baja')->count())->toBe(1);
});
