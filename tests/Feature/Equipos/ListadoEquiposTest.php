<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\Index;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Listado de equipos: búsqueda (RF-07) y filtros combinables.
 */
class ListadoEquiposTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private TipoEquipo $pc;

    private TipoEquipo $monitor;

    private Sede $palacio;

    private Sede $morindo;

    private Dependencia $tic;

    private Piso $piso;

    private Marca $marca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        $this->pc = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo']);
        $this->monitor = TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
        $this->palacio = Sede::create(['nombre' => 'Palacio Naín']);
        $this->morindo = Sede::create(['nombre' => 'Morindo']);
        $this->tic = Dependencia::create(['nombre' => 'Dirección TIC']);
        $this->piso = Piso::create(['numero' => 1]);
        $this->marca = Marca::create(['nombre' => 'HP']);
    }

    private function equipo(string $serial, array $datos = [], ?Sede $sede = null, ?Persona $persona = null): Equipo
    {
        $equipo = Equipo::create(array_merge([
            'serial' => $serial,
            'tipo_equipo_id' => $this->pc->id,
            'marca_id' => $this->marca->id,
            'propiedad' => 'gobernacion',
        ], $datos));

        if ($sede) {
            Asignacion::create([
                'equipo_id' => $equipo->id,
                'persona_id' => $persona?->id,
                'sede_id' => $sede->id,
                'piso_id' => $this->piso->id,
                'dependencia_id' => $this->tic->id,
                'fecha_inicio' => now(),
            ]);
        }

        return $equipo;
    }

    private function listado()
    {
        return Livewire::actingAs($this->usuario)->test(Index::class);
    }

    public function test_filtra_los_equipos_sin_responsable(): void
    {
        $ana = Persona::create(['nombre' => 'Ana Pérez']);
        $this->equipo('SN-CON-1', [], $this->palacio, $ana);
        $this->equipo('SN-BODEGA-1', [], $this->palacio);
        $this->equipo('SN-SINASIG-1');

        $this->listado()
            ->set('responsable', 'sin')
            ->assertSee('SN-BODEGA-1')
            ->assertSee('SN-SINASIG-1')
            ->assertDontSee('SN-CON-1')
            ->set('responsable', 'con')
            ->assertSee('SN-CON-1')
            ->assertDontSee('SN-BODEGA-1');
    }

    public function test_filtra_por_sede_y_por_tipo_combinados(): void
    {
        $this->equipo('SN-PAL-PC', [], $this->palacio);
        $this->equipo('SN-PAL-MON', ['tipo_equipo_id' => $this->monitor->id], $this->palacio);
        $this->equipo('SN-MOR-PC', [], $this->morindo);

        $this->listado()
            ->set('sede', (string) $this->palacio->id)
            ->set('tipo', (string) $this->pc->id)
            ->assertSee('SN-PAL-PC')
            ->assertDontSee('SN-PAL-MON')
            ->assertDontSee('SN-MOR-PC');
    }

    public function test_filtra_por_estado_verificacion_propiedad_y_sin_codigo(): void
    {
        $this->equipo('SN-BAJA', ['estado_ciclo_vida' => 'dado_de_baja', 'codigo_activo' => 'I1-000001']);
        $this->equipo('SN-PEND', ['verificacion' => 'pendiente_de_verificar', 'codigo_activo' => 'I1-000002']);
        $this->equipo('SN-TERCERO', ['propiedad' => 'tercero', 'codigo_activo' => 'I1-000003', 'verificacion' => 'verificado']);
        $this->equipo('SN-SINCODIGO', ['verificacion' => 'verificado']);

        $this->listado()->set('estado', 'dado_de_baja')->assertSee('SN-BAJA')->assertDontSee('SN-PEND');
        $this->listado()->set('verificacion', 'pendiente_de_verificar')->assertSee('SN-PEND')->assertDontSee('SN-TERCERO');
        $this->listado()->set('propiedad', 'tercero')->assertSee('SN-TERCERO')->assertDontSee('SN-BAJA');
        $this->listado()->set('identificacion', 'sin_codigo')->assertSee('SN-SINCODIGO')->assertDontSee('SN-TERCERO');
    }

    public function test_filtra_los_equipos_sin_serial_real(): void
    {
        $this->equipo('PENDIENTE-12-MONITOR');
        $this->equipo('SN-REAL-1');

        $this->listado()
            ->set('identificacion', 'sin_serial')
            ->assertSee('PENDIENTE-12-MONITOR')
            ->assertDontSee('SN-REAL-1');
    }

    public function test_filtra_por_firmas_pendientes_o_al_dia(): void
    {
        $historial = app(\App\Services\HistorialService::class);

        $pendiente = $this->equipo('SN-FIRMA-PEND');
        $historial->registrar($pendiente, 'traslado_responsable', $this->usuario, ['estado_firma' => 'pendiente_de_firma']);

        $alDia = $this->equipo('SN-FIRMA-OK');
        $historial->registrar($alDia, 'traslado_responsable', $this->usuario, ['estado_firma' => 'completo']);

        // Un traslado pendiente que se anuló ya no espera firmas.
        $anulado = $this->equipo('SN-FIRMA-ANULADA');
        $traslado = $historial->registrar($anulado, 'traslado_responsable', $this->usuario, ['estado_firma' => 'pendiente_de_firma']);
        $historial->registrar($anulado, 'anulacion_aclaracion', $this->usuario, descripcion: 'Error', eventoAnulado: $traslado);

        $this->equipo('SN-SIN-MOVIMIENTOS');

        $this->listado()
            ->set('firmas', 'pendiente')
            ->assertSee('SN-FIRMA-PEND')
            ->assertDontSee('SN-FIRMA-OK')
            ->assertDontSee('SN-FIRMA-ANULADA')
            ->assertDontSee('SN-SIN-MOVIMIENTOS')
            ->set('firmas', 'al_dia')
            ->assertSee('SN-FIRMA-OK')
            ->assertDontSee('SN-FIRMA-PEND')
            ->assertDontSee('SN-SIN-MOVIMIENTOS');
    }

    public function test_limpiar_filtros_vuelve_a_mostrar_todo(): void
    {
        $this->equipo('SN-A', ['estado_ciclo_vida' => 'dado_de_baja']);
        $this->equipo('SN-B');

        $this->listado()
            ->set('estado', 'dado_de_baja')
            ->set('busqueda', 'SN')
            ->assertDontSee('SN-B')
            ->call('limpiarFiltros')
            ->assertSet('estado', '')
            ->assertSet('busqueda', '')
            ->assertSee('SN-A')
            ->assertSee('SN-B');
    }

    public function test_la_busqueda_tolera_espacios_y_guiones(): void
    {
        $this->equipo('SN-X', ['codigo_activo' => 'I1-24147']);
        $this->equipo('SN-Y', ['codigo_activo' => 'I1-99999']);

        $this->listado()
            ->set('busqueda', 'I1 24147')
            ->assertSee('SN-X')
            ->assertDontSee('SN-Y');
    }

    public function test_buscar_solo_guiones_o_espacios_no_devuelve_todo(): void
    {
        $this->equipo('SN-X');

        $this->listado()
            ->set('busqueda', ' - ')
            ->assertDontSee('SN-X')
            ->assertSee('No se encontraron equipos');
    }
}
