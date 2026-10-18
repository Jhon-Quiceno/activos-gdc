<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\Editar;
use App\Livewire\Equipos\HojaDeVida;
use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\SistemaOperativo;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Edición de datos del equipo con evento «actualizacion_datos» y valor
 * anterior/nuevo (RF-12), RN-02, RN-03 y RN-10.
 */
class EditarEquipoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private TipoEquipo $monitor;

    private Marca $hp;

    private Marca $lenovo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['name' => 'Ana Técnica']);
        $this->monitor = TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
        $this->hp = Marca::create(['nombre' => 'HP']);
        $this->lenovo = Marca::create(['nombre' => 'Lenovo']);
    }

    private function crearMonitor(array $datos = []): Equipo
    {
        return Equipo::create(array_merge([
            'serial' => 'SN-MON-0001',
            'codigo_activo' => 'I1-024147',
            'tipo_equipo_id' => $this->monitor->id,
            'marca_id' => $this->hp->id,
            'modelo' => 'P24',
            'propiedad' => 'gobernacion',
            'estado_funcionamiento' => 'Bueno',
            'caracteristicas' => ['tamano_pulgadas' => '24', 'conexion' => 'HDMI'],
        ], $datos));
    }

    private function editar(Equipo $equipo)
    {
        return Livewire::actingAs($this->usuario)->test(Editar::class, ['equipo' => $equipo]);
    }

    public function test_carga_los_datos_actuales_del_equipo(): void
    {
        $this->editar($this->crearMonitor())
            ->assertSet('serial', 'SN-MON-0001')
            ->assertSet('codigoActivo', 'I1-024147')
            ->assertSet('marcaId', $this->hp->id)
            ->assertSet('modelo', 'P24')
            ->assertSet('tamanoPulgadas', '24')
            ->assertSet('conexionMonitor', 'HDMI');
    }

    public function test_guardar_cambios_registra_el_antes_y_despues(): void
    {
        $equipo = $this->crearMonitor();

        $this->editar($equipo)
            ->set('marcaId', $this->lenovo->id)
            ->set('modelo', 'ThinkVision T24')
            ->set('tamanoPulgadas', '27')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('equipos.show', $equipo));

        $equipo->refresh();
        $this->assertSame($this->lenovo->id, $equipo->marca_id);
        $this->assertSame('ThinkVision T24', $equipo->modelo);
        $this->assertEquals(['tamano_pulgadas' => '27', 'conexion' => 'HDMI'], $equipo->caracteristicas);

        $evento = Evento::sole();
        $this->assertSame('actualizacion_datos', $evento->tipo);
        // RF-11: autor automático.
        $this->assertSame($this->usuario->id, $evento->usuario_id);
        $this->assertEquals(['marca' => 'HP', 'modelo' => 'P24', 'caracteristicas' => 'Conexión: HDMI · Tamaño en pulgadas: 24'], $evento->valores['antes']);
        $this->assertEquals(['marca' => 'Lenovo', 'modelo' => 'ThinkVision T24', 'caracteristicas' => 'Conexión: HDMI · Tamaño en pulgadas: 27'], $evento->valores['despues']);
        $this->assertStringContainsString('Marca, Modelo, Características', $evento->descripcion);
    }

    public function test_sin_cambios_no_registra_evento(): void
    {
        $equipo = $this->crearMonitor();

        $this->editar($equipo)
            ->set('modelo', ' P24 ')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(0, Evento::count());
    }

    public function test_el_serial_se_normaliza_y_puede_quedar_igual(): void
    {
        $equipo = $this->crearMonitor();

        $this->editar($equipo)
            ->set('serial', '  sn-mon-0001 ')
            ->assertSet('serial', 'SN-MON-0001')
            ->assertHasNoErrors('serial');
    }

    public function test_no_permite_el_serial_de_otro_equipo(): void
    {
        $this->crearMonitor(['serial' => 'SN-OTRO-9', 'codigo_activo' => null]);
        $equipo = $this->crearMonitor();

        $this->editar($equipo)
            ->set('serial', 'sn-otro-9')
            ->call('guardar')
            ->assertHasErrors('serial');

        $this->assertSame('SN-MON-0001', $equipo->fresh()->serial);
    }

    public function test_un_codigo_repetido_exige_justificacion(): void
    {
        $this->crearMonitor(['serial' => 'SN-OTRO-9', 'codigo_activo' => 'I1-111111']);
        $equipo = $this->crearMonitor();

        $this->editar($equipo)
            ->set('codigoActivo', 'i1 111111')
            ->call('guardar')
            ->assertHasErrors(['codigoActivoJustificacion' => 'required']);

        $this->editar($equipo)
            ->set('codigoActivo', 'I1-111111')
            ->set('codigoActivoJustificacion', 'All in One: comparte código con su pantalla integrada.')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('I1-111111', $equipo->fresh()->codigo_activo);
        $this->assertSame('All in One: comparte código con su pantalla integrada.', $equipo->fresh()->codigo_activo_justificacion);
    }

    public function test_actualiza_el_software_de_un_equipo_de_computo(): void
    {
        $pc = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo']);
        $w10 = SistemaOperativo::create(['nombre' => 'Windows 10']);
        $w11 = SistemaOperativo::create(['nombre' => 'Windows 11']);
        $equipo = $this->crearMonitor(['tipo_equipo_id' => $pc->id, 'caracteristicas' => null]);
        ConfiguracionComputo::create(['equipo_id' => $equipo->id, 'sistema_operativo_id' => $w10->id, 'tiene_antivirus' => false]);

        $this->editar($equipo)
            ->set('sistemaOperativoId', $w11->id)
            ->set('tieneAntivirus', 'si')
            ->set('antivirusProducto', 'FortiEDR')
            ->call('guardar')
            ->assertHasNoErrors();

        $configuracion = $equipo->configuracionComputo()->first();
        $this->assertSame($w11->id, $configuracion->sistema_operativo_id);
        $this->assertTrue((bool) $configuracion->tiene_antivirus);

        $evento = Evento::sole();
        // assertEquals: MySQL reordena las claves del JSON al guardarlo.
        $this->assertEquals(['sistema_operativo' => 'Windows 10', 'antivirus' => 'No'], $evento->valores['antes']);
        $this->assertEquals(['sistema_operativo' => 'Windows 11', 'antivirus' => 'Sí (FortiEDR)'], $evento->valores['despues']);
    }

    public function test_un_equipo_dado_de_baja_no_se_edita(): void
    {
        $equipo = $this->crearMonitor(['estado_ciclo_vida' => 'dado_de_baja']);

        $this->editar($equipo)
            ->assertSee('no admite cambios')
            ->set('modelo', 'Otro modelo')
            ->call('guardar')
            ->assertHasErrors('equipo');

        $this->assertSame('P24', $equipo->fresh()->modelo);
        $this->assertSame(0, Evento::count());
    }

    public function test_la_pagina_de_edicion_y_el_boton_en_la_hoja_de_vida(): void
    {
        $equipo = $this->crearMonitor();
        $deBaja = $this->crearMonitor(['serial' => 'SN-BAJA-1', 'codigo_activo' => null, 'estado_ciclo_vida' => 'dado_de_baja']);

        $this->actingAs($this->usuario)->get(route('equipos.editar', $equipo))
            ->assertOk()
            ->assertSee('Editar equipo');

        $this->actingAs($this->usuario)->get(route('equipos.show', $equipo))
            ->assertSee(route('equipos.editar', $equipo));

        $this->actingAs($this->usuario)->get(route('equipos.show', $deBaja))
            ->assertDontSee(route('equipos.editar', $deBaja));
    }

    public function test_la_hoja_de_vida_muestra_el_antes_y_despues(): void
    {
        $equipo = $this->crearMonitor();

        $this->editar($equipo)->set('modelo', 'ThinkVision T24')->call('guardar');

        Livewire::actingAs($this->usuario)
            ->test(HojaDeVida::class, ['equipo' => $equipo->fresh()])
            ->assertSee('Actualización de datos')
            ->assertSeeInOrder(['Modelo:', 'P24', '→', 'ThinkVision T24']);
    }
}
