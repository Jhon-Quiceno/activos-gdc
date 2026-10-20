<?php

use App\Livewire\Movimientos\BajasListado;
use App\Livewire\Movimientos\Index;
use App\Livewire\Movimientos\Soporte\GestorAsignaciones;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\PazYSalvo;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Models\Evento;
use App\Models\MotivoBaja;
use App\Models\Piso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/**
 * Paz y salvo (RF-23) y listado de bajas de un período (RF-27).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->actingAs($this->usuario);
});

it('avisa de quien termina su vinculación pronto y todavía tiene equipos', function () {
    $porTerminar = Apoyo::persona(['nombres' => 'Termina', 'apellidos' => 'Pronto', 'fecha_fin_vinculacion' => now()->addDays(10)->toDateString()]);
    Apoyo::equipo($porTerminar);
    $sinEquipos = Apoyo::persona(['nombres' => 'Sin', 'apellidos' => 'Equipos', 'fecha_fin_vinculacion' => now()->addDays(5)->toDateString()]);
    $lejos = Apoyo::persona(['nombres' => 'Termina', 'apellidos' => 'Lejos', 'fecha_fin_vinculacion' => now()->addMonths(6)->toDateString()]);
    Apoyo::equipo($lejos);

    $avisos = app(PazYSalvo::class)->porTerminarConEquipos();

    expect($avisos->pluck('id')->all())->toBe([$porTerminar->id]);

    // «Termina Lejos» sí sale en la tabla general de personas; el aviso solo trae a «Termina Pronto» (arriba).
    Livewire::test(Index::class)
        ->assertSee('Paz y salvo pendiente')
        ->assertSee('Termina Pronto');
});

it('descarga el paz y salvo de la persona', function () {
    $persona = Apoyo::persona(['nombres' => 'Ana', 'apellidos' => 'Pérez']);

    Livewire::test(Index::class)
        ->call('descargarPazYSalvo', $persona->id)
        ->assertFileDownloaded('paz-y-salvo-ana-perez.pdf');

    $html = view('livewire.movimientos.pdf.paz-y-salvo', [
        'persona' => $persona, 'equipos' => collect(), 'consecutivo' => 'PS-1', 'fecha' => now(), 'tecnico' => $this->usuario,
    ])->render();

    expect($html)->toContain('PAZ Y SALVO por este concepto');
});

it('con equipos a cargo el paz y salvo los lista y no se expide', function () {
    $persona = Apoyo::persona();
    $equipo = Apoyo::equipo($persona);

    $html = view('livewire.movimientos.pdf.paz-y-salvo', [
        'persona' => $persona,
        'equipos' => app(GestorAsignaciones::class)->equiposACargo($persona),
        'consecutivo' => 'PS-1', 'fecha' => now(), 'tecnico' => $this->usuario,
    ])->render();

    expect($html)->toContain('NO SE EXPIDE PAZ Y SALVO')->toContain($equipo->serial);
});

it('descarga el listado de bajas del período en PDF y en Excel (RF-27)', function () {
    $equipo = Apoyo::equipo();
    app(RegistroMovimientos::class)->darDeBaja(
        $equipo,
        $this->usuario,
        [
            'fecha' => now()->toDateString(),
            'motivo_baja_id' => MotivoBaja::query()->value('id'),
            'estado_encontrado' => 'No enciende',
            'diagnostico' => 'Tarjeta madre dañada',
            'recomendaciones' => null,
        ],
    );

    expect(Evento::where('tipo', 'baja')->count())->toBe(1);

    Livewire::test(BajasListado::class)
        ->set('vista', 'registradas')
        ->set('desde', now()->subDay()->toDateString())
        ->set('hasta', now()->toDateString())
        ->assertSee($equipo->serial)
        ->call('exportarPdf')
        ->assertFileDownloaded()
        ->call('exportarExcel')
        ->assertFileDownloaded();

    Livewire::test(BajasListado::class)
        ->set('vista', 'registradas')
        ->set('desde', now()->addDay()->toDateString())
        ->assertDontSee($equipo->serial)
        ->set('hasta', now()->toDateString())
        ->call('exportarPdf')
        ->assertHasErrors('hasta');
});

it('quien entregó un equipo y no firmó el formato de baja no está a paz y salvo', function () {
    $entrega = Apoyo::persona(['nombres' => 'Entrega', 'apellidos' => 'Sin Firmar']);
    $recibe = Apoyo::persona(['nombres' => 'Recibe', 'apellidos' => 'Nuevo']);
    $equipo = Apoyo::equipo($entrega);

    $traslado = app(RegistroMovimientos::class)->trasladar($equipo, $this->usuario, [
        'persona' => $recibe,
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $recibe->dependencia_id,
        'fecha' => now()->toDateString(),
        'motivo' => 'Retiro del funcionario',
    ]);

    $pazYSalvo = app(PazYSalvo::class);
    $html = fn () => view('livewire.movimientos.pdf.paz-y-salvo', [
        'persona' => $entrega,
        'equipos' => app(GestorAsignaciones::class)->equiposACargo($entrega),
        'entregasSinFirmar' => $pazYSalvo->entregasSinFirmar($entrega),
        'consecutivo' => 'PS-1', 'fecha' => now(), 'tecnico' => $this->usuario,
    ])->render();

    expect(app(GestorAsignaciones::class)->equiposACargo($entrega))->toBeEmpty()
        ->and($pazYSalvo->entregasSinFirmar($entrega)->pluck('id')->all())->toBe([$traslado->id])
        ->and($pazYSalvo->entregasSinFirmar($recibe))->toBeEmpty()
        ->and($html())->toContain('NO SE EXPIDE PAZ Y SALVO')->toContain('Entregas pendientes de firma')->toContain($equipo->serial);

    app(GestorFirmas::class)->subirFirmado($traslado, 'formato_baja', Apoyo::pdfFirmado());

    expect($pazYSalvo->entregasSinFirmar($entrega))->toBeEmpty()
        ->and($html())->toContain('PAZ Y SALVO por este concepto');
});
