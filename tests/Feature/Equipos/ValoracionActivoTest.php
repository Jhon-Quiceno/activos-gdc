<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\HojaDeVida;
use App\Livewire\Equipos\ValorarActivo;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\ValoracionActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pantalla de valoración del activo, Manual GT-M4 (RF-56, RF-57), y
 * reclasificación con historial (RF-58). Las reglas RN-22 a RN-25 las aplica
 * el modelo; aquí se prueba que la pantalla las muestra.
 */
class ValoracionActivoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Equipo $pc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['name' => 'Ana Técnica']);
        $this->pc = $this->equipoDeTipo('PC de escritorio', 'hardware');
    }

    private function equipoDeTipo(string $tipo, string $tipoActivo): Equipo
    {
        return Equipo::create([
            'serial' => 'SN-'.str_replace(' ', '', $tipo),
            'tipo_equipo_id' => TipoEquipo::create(['nombre' => $tipo, 'familia' => 'computo', 'tipo_activo' => $tipoActivo])->id,
            'marca_id' => Marca::firstOrCreate(['nombre' => 'HP'])->id,
            'propiedad' => 'gobernacion',
        ]);
    }

    private function formulario(?Equipo $equipo = null, array $cambios = [])
    {
        $formulario = Livewire::actingAs($this->usuario)->test(ValorarActivo::class, ['equipo' => $equipo ?? $this->pc]);

        foreach (array_merge([
            'contieneDatosPersonales' => 'no',
            'infraestructuraCritica' => 'no',
            'continuidadNegocio' => 'si',
            'propietarioCargo' => 'Director TIC',
            'custodioCargo' => 'Profesional Universitario',
            'tipoUbicacion' => 'fisico',
            'confidencialidad' => 2,
            'integridad' => 2,
            'disponibilidad' => 2,
        ], $cambios) as $campo => $valor) {
            $formulario->set($campo, $valor);
        }

        return $formulario;
    }

    public function test_muestra_el_resultado_en_vivo_con_el_texto_de_cada_nivel(): void
    {
        Livewire::actingAs($this->usuario)->test(ValorarActivo::class, ['equipo' => $this->pc])
            ->assertSee('Elige los tres niveles')
            ->assertSee(ValoracionActivo::ESCALA_DISPONIBILIDAD[3])
            ->set('confidencialidad', 4)
            ->set('integridad', 4)
            ->set('disponibilidad', 2)
            ->assertSee('Importancia Muy alta')
            ->assertSee('Pasa a gestión de riesgos');

        $this->formulario(cambios: ['confidencialidad' => 1, 'integridad' => 2, 'disponibilidad' => 2])
            ->assertSee('Importancia Media')
            ->assertSee('No requiere gestión de riesgos');
    }

    public function test_guarda_la_valoracion_con_su_evento_a_nombre_del_usuario(): void
    {
        $this->formulario(cambios: [
            'confidencialidad' => 3,
            'integridad' => 3,
            'disponibilidad' => 3,
            'controlesMonitoreo' => ['Cámaras de vigilancia', 'Sensor de humo'],
        ])->call('guardar')->assertHasNoErrors()->assertDispatched('valoracion-registrada');

        $valoracion = ValoracionActivo::sole();

        $this->assertSame(9, $valoracion->total);
        $this->assertSame('alta', $valoracion->importancia);
        $this->assertTrue($valoracion->requiere_gestion_riesgo);
        $this->assertSame('Cámaras de vigilancia, Sensor de humo', $valoracion->controles_monitoreo);
        $this->assertSame('valoracion', $valoracion->evento->tipo);
        $this->assertSame($this->usuario->id, $valoracion->evento->usuario_id);
    }

    public function test_muestra_el_error_de_rn_24_que_lanza_el_modelo(): void
    {
        $this->formulario(cambios: ['contieneDatosPersonales' => 'si', 'clasificacionDatos' => ''])
            ->call('guardar')
            ->assertHasErrors('clasificacionDatos')
            ->assertSee('hay que clasificarlos (RN-24)');

        $this->assertSame(0, ValoracionActivo::count());

        $this->formulario(cambios: ['contieneDatosPersonales' => 'si', 'clasificacionDatos' => 'semiprivado'])
            ->call('guardar')
            ->assertHasNoErrors();
    }

    public function test_sin_datos_personales_la_clasificacion_queda_no_aplica(): void
    {
        $this->formulario(cambios: ['contieneDatosPersonales' => 'si', 'clasificacionDatos' => 'privado'])
            ->set('contieneDatosPersonales', 'no')
            ->assertSet('clasificacionDatos', 'no_aplica');
    }

    public function test_muestra_el_error_de_rn_25_para_un_activo_de_soporte(): void
    {
        $ups = $this->equipoDeTipo('UPS', 'soporte');

        $this->formulario($ups)
            ->assertSee('los activos de soporte no se valoran (RN-25)')
            ->call('guardar')
            ->assertHasErrors('equipo')
            ->assertSee('no se valoran (RN-25)');

        $this->assertSame(0, ValoracionActivo::count());
        $this->assertSame(0, Evento::count());
    }

    public function test_reclasificar_parte_de_la_vigente_y_deja_la_anterior_en_el_historial(): void
    {
        $this->formulario()->call('guardar')->assertHasNoErrors();

        Livewire::actingAs($this->usuario)->test(ValorarActivo::class, ['equipo' => $this->pc->fresh()])
            ->assertSet('propietarioCargo', 'Director TIC')
            ->assertSet('confidencialidad', 2)
            ->assertSee('Reclasificación')
            ->set('disponibilidad', 4)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(2, ValoracionActivo::count());
        $this->assertSame(1, ValoracionActivo::where('vigente', true)->count());
        $this->assertSame(4, $this->pc->fresh()->valoracionVigente->disponibilidad);

        $reclasificacion = Evento::where('tipo', 'valoracion')->latest('id')->first();
        $this->assertSame(2, $reclasificacion->valores['antes']['disponibilidad']);
        $this->assertSame(4, $reclasificacion->valores['despues']['disponibilidad']);
    }

    public function test_la_hoja_de_vida_muestra_la_vigente_destacada_y_el_historial(): void
    {
        $this->formulario()->call('guardar');
        Livewire::actingAs($this->usuario)->test(ValorarActivo::class, ['equipo' => $this->pc->fresh()])
            ->set('integridad', 4)->call('guardar');

        Livewire::actingAs($this->usuario)->test(HojaDeVida::class, ['equipo' => $this->pc->fresh()])
            ->assertSee('Valoración del activo (GT-M4)')
            ->assertSee('Reclasificar')
            ->assertSee('Vigente')
            ->assertSee('Pasa a gestión de riesgos')
            ->assertSee('Reclasificación del activo')
            ->assertSeeInOrder(['Antes', 'C 2 · I 2 · D 2 = 6', 'Ahora', 'C 2 · I 4 · D 2 = 8']);
    }

    public function test_la_hoja_de_vida_de_un_activo_de_soporte_no_ofrece_valorar(): void
    {
        $ups = $this->equipoDeTipo('UPS', 'soporte');

        Livewire::actingAs($this->usuario)->test(HojaDeVida::class, ['equipo' => $ups])
            ->assertSee('no se valoran según el Manual GT-M4 (RN-25)')
            ->assertDontSee('Valorar activo');
    }

    public function test_desde_un_levantamiento_la_valoracion_queda_enlazada(): void
    {
        // RF-59: el levantamiento (bloque de Anuar) reutiliza este mismo formulario.
        $levantamiento = \App\Models\LevantamientoActivos::create([
            'codigo' => 'LEV-2026-001',
            'dependencia_id' => \App\Models\Dependencia::create(['nombre' => 'Dirección TIC'])->id,
            'sede_id' => \App\Models\Sede::create(['municipio_id' => \App\Models\Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería'])->id, 'nombre' => 'Palacio Naín'])->id,
            'fecha_inicio' => now()->toDateString(),
            'usuario_id' => $this->usuario->id,
        ]);

        $formulario = Livewire::actingAs($this->usuario)
            ->test(ValorarActivo::class, ['equipo' => $this->pc, 'levantamientoId' => $levantamiento->id]);

        foreach ([
            'contieneDatosPersonales' => 'no', 'infraestructuraCritica' => 'no', 'continuidadNegocio' => 'no',
            'propietarioCargo' => 'Director TIC', 'custodioCargo' => 'Técnico', 'confidencialidad' => 1, 'integridad' => 1, 'disponibilidad' => 1,
        ] as $campo => $valor) {
            $formulario->set($campo, $valor);
        }

        $formulario->call('guardar')->assertHasNoErrors();

        $this->assertSame($levantamiento->id, ValoracionActivo::sole()->levantamiento_id);
    }

    public function test_al_registrar_una_valoracion_la_hoja_de_vida_se_actualiza(): void
    {
        $hoja = Livewire::actingAs($this->usuario)->test(HojaDeVida::class, ['equipo' => $this->pc])
            ->assertSee('Este activo aún no tiene valoración.');

        $this->formulario()->call('guardar');

        $hoja->dispatch('valoracion-registrada', equipoId: $this->pc->id)
            ->assertSee('Valoración registrada.')
            ->assertDontSee('Este activo aún no tiene valoración.');
    }

    public function test_no_valora_un_equipo_que_se_dio_de_baja_con_la_ventana_abierta(): void
    {
        $formulario = $this->formulario();

        // La baja se registra desde otra pantalla mientras la ventana sigue abierta.
        Equipo::whereKey($this->pc->id)->update(['estado_ciclo_vida' => 'dado_de_baja']);

        $formulario->call('guardar')->assertHasErrors('equipo');

        $this->assertSame(0, ValoracionActivo::count());
    }
}
