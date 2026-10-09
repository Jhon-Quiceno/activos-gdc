<?php

use App\Livewire\Equipos\Editar;
use App\Livewire\Equipos\HojaDeVida;
use App\Livewire\Qr\Etiquetas;
use App\Livewire\Qr\Index;
use App\Livewire\Qr\Soporte\CodigoQr;
use App\Models\EtiquetaQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Movimientos\Apoyo;

/*
 * Códigos QR y etiquetas: RF-08, RF-48, RF-49, RF-50, RF-51; RN-17 a RN-19; RNF-18.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Apoyo::catalogos();
    $this->usuario = Apoyo::usuario();
    $this->equipo = Apoyo::equipo(atributos: ['serial' => 'SN-QR-0001']);
});

it('el QR solo lleva el enlace corto con el identificador permanente (RN-17, RNF-18)', function () {
    expect(CodigoQr::enlace($this->equipo))
        ->toBe(url('/e/'.$this->equipo->qr_uuid))
        ->not->toContain($this->equipo->serial);

    expect((string) CodigoQr::svg($this->equipo))->toContain('<svg');
});

it('al escanear con sesión abre la hoja de vida del equipo (RF-50)', function () {
    $this->actingAs($this->usuario)
        ->get(route('qr.escanear', $this->equipo->qr_uuid))
        ->assertRedirect(route('equipos.show', $this->equipo));
});

it('al escanear sin sesión pide iniciar sesión y recuerda el equipo (RN-18)', function () {
    $this->get(route('qr.escanear', $this->equipo->qr_uuid))
        ->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe(route('qr.escanear', $this->equipo->qr_uuid));
});

it('un QR que no corresponde a ningún equipo no muestra nada', function () {
    $this->actingAs($this->usuario)->get('/e/no-existe')->assertNotFound();
});

it('el identificador del QR no cambia al corregir el serial (RN-19)', function () {
    $uuid = $this->equipo->qr_uuid;

    Livewire::actingAs($this->usuario)
        ->test(Editar::class, ['equipo' => $this->equipo])
        ->set('serial', 'SN-CORREGIDO-1')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($this->equipo->fresh())
        ->serial->toBe('SN-CORREGIDO-1')
        ->qr_uuid->toBe($uuid);
});

it('la hoja de vida muestra el QR y el botón para imprimir la etiqueta (RF-48)', function () {
    $this->actingAs($this->usuario);
    $this->withoutVite();

    $this->get(route('equipos.show', $this->equipo))
        ->assertOk()
        ->assertSee('/e/'.$this->equipo->qr_uuid)
        ->assertSee('Sin imprimir')
        ->assertSee(route('qr.etiquetas', ['equipos' => $this->equipo->id]), false);
});

it('la hoja de etiquetas lleva los datos de RF-48 y registra la impresión', function () {
    $this->actingAs($this->usuario);

    Livewire::withQueryParams(['equipos' => (string) $this->equipo->id])
        ->test(Etiquetas::class)
        ->assertSee('Gobernación de Córdoba · Dirección TIC')
        ->assertSee('SN-QR-0001')
        ->call('imprimir');

    $impresion = EtiquetaQr::sole();
    expect($impresion->equipo_id)->toBe($this->equipo->id)
        ->and($impresion->usuario_id)->toBe($this->usuario->id)
        ->and($impresion->motivo)->toBe('primera_impresion')
        ->and($impresion->lote)->toBeNull();
});

it('reimprimir queda como reposición y varias a la vez van en un lote (RF-49, RF-51)', function () {
    $this->actingAs($this->usuario);
    $otro = Apoyo::equipo(atributos: ['serial' => 'SN-QR-0002']);

    Livewire::withQueryParams(['equipos' => (string) $this->equipo->id])->test(Etiquetas::class)->call('imprimir');
    Livewire::withQueryParams(['equipos' => $this->equipo->id.','.$otro->id])->test(Etiquetas::class)->call('imprimir');

    $segundas = EtiquetaQr::where('id', '>', EtiquetaQr::min('id'))->get()->keyBy('equipo_id');

    expect($segundas[$this->equipo->id]->motivo)->toBe('reposicion')
        ->and($segundas[$otro->id]->motivo)->toBe('primera_impresion')
        ->and($segundas[$this->equipo->id]->lote)->not->toBeNull()
        ->and($segundas[$this->equipo->id]->lote)->toBe($segundas[$otro->id]->lote);
});

it('la página de etiquetas filtra los equipos sin etiqueta y permite elegirlos todos', function () {
    $this->actingAs($this->usuario);
    $impreso = Apoyo::equipo(atributos: ['serial' => 'SN-QR-IMPRESO']);
    EtiquetaQr::create(['equipo_id' => $impreso->id, 'fecha_impresion' => now(), 'usuario_id' => $this->usuario->id, 'motivo' => 'primera_impresion']);

    Livewire::test(Index::class)
        ->set('etiqueta', 'sin')
        ->assertSee('SN-QR-0001')
        ->assertDontSee('SN-QR-IMPRESO')
        ->call('seleccionarFiltrados')
        ->assertSet('seleccionados', [(string) $this->equipo->id]);
});

it('la hoja de vida usa el componente del QR', function () {
    Livewire::actingAs($this->usuario)
        ->test(HojaDeVida::class, ['equipo' => $this->equipo])
        ->assertSeeLivewire('qr.etiqueta-equipo');
});
