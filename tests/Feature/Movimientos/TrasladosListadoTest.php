<?php

use App\Livewire\Equipos\HojaDeVida;
use App\Livewire\Movimientos\DocumentosEvento;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\TrasladosListado;
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

    $this->entrega = Apoyo::persona(['nombre' => 'Quien Entrega']);
    $this->recibe = Apoyo::persona(['nombre' => 'Quien Recibe']);
    $this->equipo = Apoyo::equipo($this->entrega, atributos: ['serial' => 'SN-TRASLADO-1']);

    $this->traslado = app(RegistroMovimientos::class)->trasladar($this->equipo, $this->usuario, [
        'persona' => $this->recibe,
        'sede_id' => Sede::query()->value('id'),
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $this->recibe->dependencia_id,
        'fecha' => now()->toDateString(),
        'motivo' => 'Cambio de puesto',
    ]);
});

it('la pestaña de equipos usa los filtros del listado de equipos y no muestra dados de baja', function () {
    Apoyo::equipo(conResponsable: false, atributos: ['serial' => 'SN-BODEGA-1']);
    Apoyo::equipo(atributos: ['serial' => 'SN-BAJA-1', 'estado_ciclo_vida' => 'dado_de_baja']);

    Livewire::test(TrasladosListado::class)
        ->assertSee('SN-TRASLADO-1')
        ->assertDontSee('SN-BAJA-1')
        ->set('responsable', 'sin')
        ->assertSee('SN-BODEGA-1')
        ->assertDontSee('SN-TRASLADO-1');
});

it('la pestaña de traslados registrados lista el traslado y abre sus dos formatos', function () {
    Livewire::test(TrasladosListado::class)
        ->set('vista', 'registrados')
        ->assertSee('SN-TRASLADO-1')
        ->assertSee('Pendiente de firma')
        ->call('seleccionarTraslado', $this->traslado->id)
        ->assertSet('trasladoSeleccionado', $this->traslado->id)
        ->assertSeeLivewire(DocumentosEvento::class);

    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->assertSee('Formato de retiro (quien entrega)')
        ->assertSee('Formato de entrega (quien recibe)')
        ->assertSee('Descargar formato para firmar');
});

it('filtra los traslados registrados por estado de firma y por búsqueda', function () {
    Livewire::test(TrasladosListado::class)
        ->set('vista', 'registrados')
        ->set('firma', 'completo')
        ->assertDontSee('SN-TRASLADO-1')
        ->set('firma', 'pendiente_de_firma')
        ->set('busquedaTraslados', 'Quien Recibe')
        ->assertSee('SN-TRASLADO-1')
        ->set('busquedaTraslados', 'nadie con este nombre')
        ->assertDontSee('SN-TRASLADO-1');
});

it('descarga los formatos con el formato oficial y un nombre que dice qué son', function () {
    $consecutivo = str_pad((string) $this->traslado->id, 6, '0', STR_PAD_LEFT);

    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->call('descargarPrellenado', 'formato_baja')
        ->assertFileDownloaded("formato-retiro-FB-{$consecutivo}-SN-TRASLADO-1.pdf");

    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->call('descargarPrellenado', 'formato_entrega')
        ->assertFileDownloaded("formato-entrega-FE-{$consecutivo}-SN-TRASLADO-1.pdf");
});

it('desde la hoja de vida se abren los formatos y firmas del traslado sin salir de la página', function () {
    Livewire::test(HojaDeVida::class, ['equipo' => $this->equipo])
        ->assertSee('Formatos y firmas')
        ->call('verFormatos', $this->traslado->id)
        ->assertSet('eventoFormatosId', $this->traslado->id)
        ->assertDispatched('open-modal', 'formatos-evento');
});
