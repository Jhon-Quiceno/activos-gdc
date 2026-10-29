<?php

use App\Livewire\Movimientos\PendientesListado;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\PendientesDeFirma;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Models\Documento;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\MotivoBaja;
use App\Models\Piso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/*
 * RF-31: pantalla de pendientes de firma completa. Solo lista los eventos a
 * los que les falta algún documento firmado, se filtra por tipo y por
 * antigüedad, y lleva directo a subir el documento.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);
});

function pendientesTraslado(Equipo $equipo, $usuario, ?string $fecha = null): Evento
{
    $persona = Apoyo::persona();

    return app(RegistroMovimientos::class)->trasladar($equipo, $usuario, [
        'persona' => $persona,
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $persona->dependencia_id,
        'fecha' => $fecha ?? now()->toDateString(),
        'motivo' => 'Reasignación',
    ]);
}

function pendientesBaja(Equipo $equipo, $usuario, ?string $fecha = null): Evento
{
    return app(RegistroMovimientos::class)->darDeBaja($equipo, $usuario, [
        'motivo_baja_id' => MotivoBaja::query()->value('id'),
        'fecha' => $fecha ?? now()->toDateString(),
        'estado_encontrado' => 'No enciende.',
        'diagnostico' => 'Fuente quemada sin repuesto.',
        'recomendaciones' => null,
    ]);
}

/** @return array<int, int> */
function idsPendientes(array $filtros = []): array
{
    return app(PendientesDeFirma::class)->consulta($filtros)->pluck('id')->sort()->values()->all();
}

it('solo lista los eventos a los que les falta algún documento firmado', function () {
    $traslado = pendientesTraslado(Apoyo::equipo(), $this->usuario);
    $firmas = app(GestorFirmas::class);

    // Con un solo documento firmado sigue pendiente, y solo falta el otro.
    $firmas->subirFirmado($traslado, 'formato_baja', Apoyo::pdfFirmado());
    expect(idsPendientes())->toBe([$traslado->id]);

    $faltantes = app(PendientesDeFirma::class)->faltantes($traslado->fresh());
    expect(collect($faltantes)->pluck('tipo')->all())->toBe(['formato_entrega']);

    Livewire::test(PendientesListado::class)
        ->assertSee($traslado->equipo->codigo_activo)
        ->assertSee('Formato de entrega')
        ->assertDontSee('Formato de retiro / baja');

    // Con los dos documentos sale de la lista.
    $firmas->subirFirmado($traslado, 'formato_entrega', Apoyo::pdfFirmado('entrega.pdf'));
    expect(idsPendientes())->toBe([]);

    Livewire::test(PendientesListado::class)
        ->assertSee('No hay movimientos pendientes de firma.')
        ->assertDontSee($traslado->equipo->codigo_activo);
});

it('no muestra un evento que ya tiene todos sus documentos firmados aunque su estado no se haya actualizado', function () {
    $traslado = pendientesTraslado(Apoyo::equipo(), $this->usuario);

    // Dato desalineado: los dos firmados existen pero el estado sigue pendiente.
    foreach (['formato_baja', 'formato_entrega'] as $tipo) {
        Documento::create([
            'evento_id' => $traslado->id,
            'tipo' => $tipo,
            'archivo_path' => "movimientos/{$tipo}.pdf",
            'firmado' => true,
            'fecha' => now()->toDateString(),
        ]);
    }

    expect($traslado->fresh()->estado_firma)->toBe('pendiente_de_firma')
        ->and(idsPendientes())->toBe([]);
});

it('acepta el documento único como reemplazo de los dos formatos del traslado', function () {
    $traslado = pendientesTraslado(Apoyo::equipo(), $this->usuario);

    app(GestorFirmas::class)->subirFirmado($traslado, GestorFirmas::DOCUMENTO_UNICO, Apoyo::pdfFirmado('unico.pdf'));

    expect(idsPendientes())->toBe([]);
});

it('no lista una baja anulada', function () {
    $equipo = Apoyo::equipo();
    pendientesBaja($equipo, $this->usuario);

    app(RegistroMovimientos::class)->anularBaja($equipo, $this->usuario, 'Se registró sobre el equipo equivocado.');

    expect(idsPendientes())->toBe([]);
});

it('filtra por tipo de evento y solo ofrece los tipos que tienen pendientes', function () {
    $traslado = pendientesTraslado(Apoyo::equipo(), $this->usuario);
    $baja = pendientesBaja(Apoyo::equipo(), $this->usuario);

    expect(idsPendientes(['tipo' => 'baja']))->toBe([$baja->id])
        ->and(idsPendientes(['tipo' => 'traslado_responsable']))->toBe([$traslado->id])
        ->and(array_keys(app(PendientesDeFirma::class)->tiposConPendientes()))->toBe(['baja', 'traslado_responsable']);
});

it('filtra por antigüedad', function () {
    $reciente = pendientesTraslado(Apoyo::equipo(), $this->usuario, now()->subDays(3)->toDateString());
    $medio = pendientesTraslado(Apoyo::equipo(), $this->usuario, now()->subDays(15)->toDateString());
    $viejo = pendientesTraslado(Apoyo::equipo(), $this->usuario, now()->subDays(45)->toDateString());

    expect(idsPendientes(['antiguedad' => 'hasta_7']))->toBe([$reciente->id])
        ->and(idsPendientes(['antiguedad' => 'de_8_a_30']))->toBe([$medio->id])
        ->and(idsPendientes(['antiguedad' => 'mas_30']))->toBe([$viejo->id])
        ->and(app(PendientesDeFirma::class)->resumen()['mas_30'])->toBe(1);
});

it('busca por código de activo escrito sin guion', function () {
    $buscado = pendientesTraslado(Apoyo::equipo(), $this->usuario);
    pendientesTraslado(Apoyo::equipo(), $this->usuario);

    $sinGuion = str_replace('-', ' ', $buscado->equipo->codigo_activo);

    expect(idsPendientes(['busqueda' => $sinGuion]))->toBe([$buscado->id]);
});

it('el enlace directo abre el panel para subir el documento firmado de ese evento', function () {
    $this->withoutVite();
    $traslado = pendientesTraslado(Apoyo::equipo(), $this->usuario);

    $this->get(route('movimientos.pendientes', ['evento' => $traslado->id]))
        ->assertOk()
        ->assertSee(__('Documentos del evento #:id', ['id' => $traslado->id]))
        ->assertSee(route('movimientos.pendientes', ['evento' => $traslado->id]), false);

    Livewire::withQueryParams(['evento' => $traslado->id])
        ->test(PendientesListado::class)
        ->assertSet('eventoSeleccionado', $traslado->id)
        ->assertSeeLivewire('movimientos.documentos-evento');
});

it('exporta a Excel el listado filtrado', function () {
    pendientesTraslado(Apoyo::equipo(), $this->usuario);

    Livewire::test(PendientesListado::class)
        ->call('exportarExcel')
        ->assertFileDownloaded('pendientes-de-firma-'.now()->format('Y-m-d').'.xlsx');

    expect(app(PendientesDeFirma::class)->filas())->toHaveCount(1);
});
