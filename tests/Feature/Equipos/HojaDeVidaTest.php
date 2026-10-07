<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\HojaDeVida;
use App\Models\CambioComponente;
use App\Models\Componente;
use App\Models\Diagnostico;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\MotivoBaja;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hoja de vida (RF-09) y corrección del historial con anulación o aclaración
 * (RF-12, RN-06, RN-10).
 */
class HojaDeVidaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private TipoEquipo $tipo;

    private Marca $marca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['name' => 'Ana Técnica']);
        $this->tipo = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo']);
        $this->marca = Marca::create(['nombre' => 'Lenovo']);
    }

    private function crearEquipo(string $serial = 'SN-HV-0001', string $estado = 'en_servicio'): Equipo
    {
        return Equipo::create([
            'serial' => $serial,
            'tipo_equipo_id' => $this->tipo->id,
            'marca_id' => $this->marca->id,
            'propiedad' => 'gobernacion',
            'estado_ciclo_vida' => $estado,
        ]);
    }

    private function registrar(Equipo $equipo, string $tipo, ?string $descripcion = null): Evento
    {
        return app(HistorialService::class)->registrar($equipo, $tipo, $this->usuario, descripcion: $descripcion);
    }

    private function registrarDiagnostico(Equipo $equipo): Evento
    {
        $evento = $this->registrar($equipo, 'diagnostico', 'Diagnóstico técnico: no enciende');

        Diagnostico::create([
            'evento_id' => $evento->id,
            'estado_encontrado' => 'No enciende',
            'causa' => 'Fuente de poder quemada',
            'recomendaciones' => 'Cambiar la fuente',
        ]);

        return $evento;
    }

    public function test_muestra_la_ficha_el_historial_y_el_espacio_del_qr(): void
    {
        $equipo = $this->crearEquipo();
        $this->registrar($equipo, 'alta', 'Alta registrada desde el formulario de registro.');

        $this->actingAs($this->usuario)
            ->get(route('equipos.show', $equipo))
            ->assertOk()
            ->assertSee('SN-HV-0001')
            ->assertSee('Alta registrada desde el formulario de registro.')
            ->assertSee('Por Ana Técnica')
            ->assertSee('Etiqueta QR')
            ->assertSee('Trasladar / reasignar');
    }

    public function test_un_equipo_dado_de_baja_no_muestra_los_botones_de_movimientos(): void
    {
        $equipo = $this->crearEquipo(estado: 'dado_de_baja');

        $this->actingAs($this->usuario)
            ->get(route('equipos.show', $equipo))
            ->assertOk()
            ->assertSee('no admite movimientos nuevos')
            ->assertDontSee('Trasladar / reasignar')
            ->assertDontSee('Registrar baja')
            ->assertDontSee(route('movimientos.componente', $equipo))
            ->assertDontSee(route('movimientos.diagnostico', $equipo));
    }

    public function test_muestra_el_detalle_de_un_cambio_de_componente(): void
    {
        $equipo = $this->crearEquipo();
        $ram = TipoComponente::create(['nombre' => 'RAM', 'es_periferico' => false]);
        $retirado = Componente::create(['equipo_id' => $equipo->id, 'tipo_componente_id' => $ram->id, 'serial' => 'RAM-VIEJA', 'fecha_instalacion' => now()]);
        $instalado = Componente::create(['equipo_id' => $equipo->id, 'tipo_componente_id' => $ram->id, 'serial' => 'RAM-NUEVA', 'fecha_instalacion' => now()]);
        $evento = $this->registrar($equipo, 'cambio_componente', 'Cambio de memoria');

        CambioComponente::create([
            'evento_id' => $evento->id,
            'accion' => 'cambiar',
            'componente_retirado_id' => $retirado->id,
            'serial_retirado' => 'RAM-VIEJA',
            'componente_instalado_id' => $instalado->id,
            'serial_instalado' => 'RAM-NUEVA',
            'motivo' => 'Ampliación a 16 GB',
            'destino_retirado' => 'bodega',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->assertSee('RAM-VIEJA')
            ->assertSee('RAM-NUEVA')
            ->assertSee('Ampliación a 16 GB')
            ->assertSee('Bodega');
    }

    public function test_muestra_el_detalle_de_un_diagnostico_y_el_motivo_de_baja(): void
    {
        $equipo = $this->crearEquipo();
        $this->registrarDiagnostico($equipo);
        $obsolescencia = MotivoBaja::create(['nombre' => 'Obsolescencia']);
        $baja = $this->registrar($equipo, 'baja', 'Baja del equipo');
        Diagnostico::create([
            'evento_id' => $baja->id,
            'estado_encontrado' => 'Equipo muy antiguo',
            'causa' => 'No soporta Windows 11',
            'es_baja' => true,
            'motivo_baja_id' => $obsolescencia->id,
        ]);

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->assertSee('Fuente de poder quemada')
            ->assertSee('Cambiar la fuente')
            ->assertSee('Obsolescencia')
            ->assertSee('No soporta Windows 11');
    }

    public function test_anular_un_diagnostico_registra_un_evento_nuevo_sin_tocar_el_original(): void
    {
        $equipo = $this->crearEquipo();
        $diagnostico = $this->registrarDiagnostico($equipo);

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->call('abrirCorreccion', $diagnostico->id)
            ->assertSet('modo', 'anulacion')
            ->set('justificacion', 'Se registró en el equipo equivocado.')
            ->call('registrarCorreccion')
            ->assertHasNoErrors()
            ->assertSee('Anulado');

        $anulacion = Evento::where('tipo', 'anulacion_aclaracion')->sole();

        $this->assertSame($diagnostico->id, $anulacion->evento_anulado_id);
        // RF-11: el autor es el usuario de la sesión.
        $this->assertSame($this->usuario->id, $anulacion->usuario_id);
        $this->assertStringContainsString('Se registró en el equipo equivocado.', $anulacion->descripcion);
        // RN-06: el evento original sigue igual.
        $this->assertSame('Diagnóstico técnico: no enciende', $diagnostico->fresh()->descripcion);
    }

    public function test_aclarar_un_traslado_no_lo_deja_anulado(): void
    {
        $equipo = $this->crearEquipo();
        $traslado = $this->registrar($equipo, 'traslado_responsable', 'Traslado de A a B');

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->call('abrirCorreccion', $traslado->id)
            ->assertSet('modo', 'aclaracion')
            ->set('justificacion', 'El motivo correcto es cambio de puesto.')
            ->call('registrarCorreccion')
            ->assertHasNoErrors()
            ->assertDontSee('Anulado');

        $aclaracion = Evento::where('tipo', 'anulacion_aclaracion')->sole();

        $this->assertNull($aclaracion->evento_anulado_id);
        $this->assertStringContainsString("Aclaración del evento #{$traslado->id}", $aclaracion->descripcion);
    }

    public function test_no_permite_anular_un_traslado_aunque_se_fuerce_el_modo(): void
    {
        $equipo = $this->crearEquipo();
        $traslado = $this->registrar($equipo, 'traslado_responsable', 'Traslado de A a B');

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->call('abrirCorreccion', $traslado->id)
            ->set('modo', 'anulacion')
            ->set('justificacion', 'Intento de anular un traslado.')
            ->call('registrarCorreccion')
            ->assertHasErrors('modo');

        $this->assertSame(0, Evento::where('tipo', 'anulacion_aclaracion')->count());
    }

    public function test_la_justificacion_es_obligatoria(): void
    {
        $equipo = $this->crearEquipo();
        $diagnostico = $this->registrarDiagnostico($equipo);

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->call('abrirCorreccion', $diagnostico->id)
            ->set('justificacion', 'corto')
            ->call('registrarCorreccion')
            ->assertHasErrors(['justificacion' => 'min']);

        $this->assertSame(0, Evento::where('tipo', 'anulacion_aclaracion')->count());
    }

    public function test_un_evento_anulado_no_admite_otra_correccion(): void
    {
        $equipo = $this->crearEquipo();
        $diagnostico = $this->registrarDiagnostico($equipo);
        app(HistorialService::class)->registrar($equipo, 'anulacion_aclaracion', $this->usuario, descripcion: 'Anulado antes', eventoAnulado: $diagnostico);

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->call('abrirCorreccion', $diagnostico->id)
            ->assertSet('eventoSeleccionadoId', null);

        $this->assertSame(1, Evento::where('tipo', 'anulacion_aclaracion')->count());
    }

    public function test_no_se_puede_corregir_un_evento_de_otro_equipo(): void
    {
        $equipo = $this->crearEquipo();
        $otro = $this->crearEquipo('SN-OTRO-0002');
        $eventoAjeno = $this->registrarDiagnostico($otro);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo])
            ->call('abrirCorreccion', $eventoAjeno->id);
    }
}
