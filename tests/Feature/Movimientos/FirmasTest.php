<?php

use App\Livewire\Movimientos\DocumentosEvento;
use App\Livewire\Movimientos\PendientesListado;
use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Models\Evento;
use App\Models\Piso;
use App\Models\Sede;
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

    $persona = Apoyo::persona();
    $this->traslado = app(RegistroMovimientos::class)->trasladar(Apoyo::equipo(), $this->usuario, [
        'persona' => $persona,
        'sede_id' => Sede::query()->value('id'),
        'piso_id' => Piso::query()->value('id'),
        'dependencia_id' => $persona->dependencia_id,
        'fecha' => now()->toDateString(),
        'motivo' => 'Reasignación',
    ]);
});

it('sigue pendiente de firma mientras falte uno de los dos documentos (RF-30)', function () {
    app(GestorFirmas::class)->subirFirmado($this->traslado, 'formato_baja', Apoyo::pdfFirmado());

    expect($this->traslado->fresh()->estado_firma)->toBe('pendiente_de_firma');
});

it('queda completo al subir los dos documentos firmados', function () {
    $firmas = app(GestorFirmas::class);
    $firmas->subirFirmado($this->traslado, 'formato_baja', Apoyo::pdfFirmado());
    $firmas->subirFirmado($this->traslado, 'formato_entrega', UploadedFile::fake()->image('entrega.jpg'));

    expect($this->traslado->fresh()->estado_firma)->toBe('completo')
        ->and(Evento::where('estado_firma', 'pendiente_de_firma')->count())->toBe(0);
});

it('acepta el documento único en lugar de los dos formatos', function () {
    app(GestorFirmas::class)->subirFirmado($this->traslado, GestorFirmas::DOCUMENTO_UNICO, Apoyo::pdfFirmado('unico.pdf'));

    expect($this->traslado->fresh()->estado_firma)->toBe('completo');
});

it('no admite más documentos cuando el evento ya está completo', function () {
    app(GestorFirmas::class)->subirFirmado($this->traslado, GestorFirmas::DOCUMENTO_UNICO, Apoyo::pdfFirmado());

    app(GestorFirmas::class)->subirFirmado($this->traslado, 'formato_baja', Apoyo::pdfFirmado());
})->throws(ValidationException::class);

it('sube los firmados desde el panel de documentos y guarda el archivo', function () {
    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->set('archivos.formato_baja', Apoyo::pdfFirmado())
        ->call('subir', 'formato_baja')
        ->assertHasNoErrors()
        ->assertSee('Falta el otro formato')
        ->set('archivos.formato_entrega', UploadedFile::fake()->image('entrega.png'))
        ->call('subir', 'formato_entrega')
        ->assertHasNoErrors()
        ->assertSee('Documentos completos');

    $firmados = $this->traslado->documentos()->where('firmado', true)->get();
    expect($firmados)->toHaveCount(2);
    $firmados->each(fn ($d) => Storage::disk('local')->assertExists($d->archivo_path));
});

it('rechaza archivos que no sean PDF, JPG o PNG (RNF-12)', function () {
    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->set('archivos.formato_baja', UploadedFile::fake()->create('firmado.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->call('subir', 'formato_baja')
        ->assertHasErrors(['archivos.formato_baja' => 'mimes']);
});

it('rechaza archivos de más de 10 MB (RNF-12)', function () {
    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->set('archivos.formato_baja', Apoyo::pdfFirmado('grande.pdf', 10241))
        ->call('subir', 'formato_baja')
        ->assertHasErrors(['archivos.formato_baja' => 'max']);

    expect($this->traslado->fresh()->estado_firma)->toBe('pendiente_de_firma');
});

it('descarga el formato prellenado desde el panel', function () {
    Livewire::test(DocumentosEvento::class, ['eventoId' => $this->traslado->id])
        ->call('descargarPrellenado', 'formato_entrega')
        ->assertFileDownloaded();
});

it('muestra el evento en Pendientes de firma hasta completarlo (RF-31)', function () {
    $this->get(route('movimientos.pendientes'))->assertOk();

    Livewire::test(PendientesListado::class)
        ->assertSee('Formato de baja + Formato de entrega')
        ->call('gestionar', $this->traslado->id)
        ->assertSet('eventoSeleccionado', $this->traslado->id);

    app(GestorFirmas::class)->subirFirmado($this->traslado, GestorFirmas::DOCUMENTO_UNICO, Apoyo::pdfFirmado());

    Livewire::test(PendientesListado::class)
        ->assertSee('No hay movimientos pendientes de firma');
});
