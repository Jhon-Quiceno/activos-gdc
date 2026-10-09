<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\Crear;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

/**
 * Registro de equipo (RF-01, RF-02, RF-04, RF-11, RF-19; RN-02, RN-03).
 */
class RegistrarEquipoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private TipoEquipo $monitor;

    private Marca $marca;

    private Sede $sede;

    private Piso $piso;

    private Dependencia $dependencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        $this->monitor = TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
        $this->marca = Marca::create(['nombre' => 'HP']);
        $this->sede = Sede::create(['nombre' => 'Palacio Naín']);
        $this->piso = Piso::create(['numero' => 3]);
        $this->dependencia = Dependencia::create(['nombre' => 'Dirección TIC']);
    }

    /**
     * Formulario con todos los campos obligatorios llenos, sin responsable
     * (el equipo queda en bodega).
     */
    private function formularioValido(array $cambios = []): Testable
    {
        $formulario = Livewire::actingAs($this->usuario)
            ->test(Crear::class)
            ->call('seleccionarTipo', $this->monitor->id);

        $datos = array_merge([
            'serial' => 'CN-0001',
            'codigoActivo' => 'I1-024147',
            'marcaId' => $this->marca->id,
            'estadoFuncionamiento' => 'Bueno',
            'sedeId' => $this->sede->id,
            'pisoId' => $this->piso->id,
            'dependenciaId' => $this->dependencia->id,
        ], $cambios);

        foreach ($datos as $campo => $valor) {
            $formulario->set($campo, $valor);
        }

        return $formulario;
    }

    private function crearEquipoExistente(string $serial, ?string $codigoActivo): Equipo
    {
        return Equipo::create([
            'serial' => $serial,
            'codigo_activo' => $codigoActivo,
            'tipo_equipo_id' => $this->monitor->id,
            'marca_id' => $this->marca->id,
            'propiedad' => 'gobernacion',
        ]);
    }

    public function test_al_elegir_el_tipo_la_seccion_se_resume_en_una_linea(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Crear::class)
            ->assertDontSee('Cambiar')
            ->call('seleccionarTipo', $this->monitor->id)
            ->assertSet('tipoEquipoId', $this->monitor->id)
            ->assertSee('Cambiar')
            ->assertSee('Monitor');
    }

    public function test_registra_el_equipo_con_evento_de_alta_a_nombre_del_usuario(): void
    {
        $this->formularioValido()->call('guardar')->assertHasNoErrors();

        $equipo = Equipo::sole();
        $alta = Evento::sole();

        $this->assertSame('alta', $alta->tipo);
        $this->assertSame($equipo->id, $alta->equipo_id);
        // RF-11: el autor es el usuario que inició sesión, sin pasarlo a mano.
        $this->assertSame($this->usuario->id, $alta->usuario_id);
    }

    public function test_la_asignacion_inicial_queda_enlazada_al_evento_de_alta(): void
    {
        $this->formularioValido()->call('guardar')->assertHasNoErrors();

        $this->assertSame(Evento::sole()->id, Asignacion::sole()->evento_origen_id);
    }

    public function test_sin_responsable_la_ubicacion_conserva_la_dependencia(): void
    {
        $this->formularioValido()->call('guardar')->assertHasNoErrors();

        $asignacion = Asignacion::sole();

        $this->assertNull($asignacion->persona_id);
        $this->assertSame($this->dependencia->id, $asignacion->dependencia_id);
        $this->assertSame('sin_asignar', Equipo::sole()->estado_ciclo_vida);
    }

    public function test_la_dependencia_es_obligatoria(): void
    {
        $this->formularioValido(['dependenciaId' => null])
            ->call('guardar')
            ->assertHasErrors(['dependenciaId' => 'required']);
    }

    public function test_normaliza_el_codigo_de_activo_al_formato_i1(): void
    {
        $casos = [
            'I1 24147' => 'I1-24147',
            'i124147' => 'I1-24147',
            'L1-24147' => 'I1-24147',
            ' i1-024147 ' => 'I1-024147',
        ];

        foreach ($casos as $escrito => $esperado) {
            $this->formularioValido(['codigoActivo' => $escrito])
                ->assertSet('codigoActivo', $esperado);
        }
    }

    public function test_rechaza_un_codigo_de_activo_con_formato_invalido(): void
    {
        $this->formularioValido(['codigoActivo' => 'VNB4513213'])
            ->call('guardar')
            ->assertHasErrors(['codigoActivo' => 'regex']);

        $this->assertSame(0, Equipo::count());
    }

    public function test_el_codigo_de_activo_es_obligatorio_salvo_que_no_tenga(): void
    {
        $this->formularioValido(['codigoActivo' => ''])
            ->call('guardar')
            ->assertHasErrors(['codigoActivo' => 'required']);

        $this->formularioValido(['codigoActivo' => '', 'sinCodigoActivo' => true])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNull(Equipo::sole()->codigo_activo);
    }

    public function test_un_codigo_de_activo_repetido_exige_justificacion(): void
    {
        $this->crearEquipoExistente('OTRO-1', 'I1-024147');

        $this->formularioValido(['codigoActivo' => 'i1 024147'])
            ->assertSee('Este código ya está en otro equipo: Monitor con serial OTRO-1')
            ->call('guardar')
            ->assertHasErrors(['codigoActivoJustificacion' => 'required']);

        $this->assertSame(1, Equipo::count());
    }

    public function test_con_justificacion_se_registra_el_codigo_repetido(): void
    {
        $this->crearEquipoExistente('OTRO-1', 'I1-024147');

        $this->formularioValido([
            'codigoActivo' => 'I1-024147',
            'codigoActivoJustificacion' => 'All in One: comparte código con su pantalla integrada.',
        ])->call('guardar')->assertHasNoErrors();

        $nuevo = Equipo::where('serial', 'CN-0001')->sole();

        $this->assertSame('I1-024147', $nuevo->codigo_activo);
        $this->assertSame('All in One: comparte código con su pantalla integrada.', $nuevo->codigo_activo_justificacion);
    }

    public function test_sin_codigo_repetido_no_se_guarda_justificacion(): void
    {
        $this->formularioValido(['codigoActivoJustificacion' => 'Texto que sobra porque no se repite.'])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNull(Equipo::sole()->codigo_activo_justificacion);
    }

    public function test_guarda_las_caracteristicas_del_tipo_y_las_observaciones(): void
    {
        $this->formularioValido([
            'tamanoPulgadas' => '24',
            'conexionMonitor' => 'HDMI',
            'observaciones' => '  Rayón en la esquina inferior.  ',
        ])->call('guardar')->assertHasNoErrors();

        $equipo = Equipo::sole();

        // assertEquals y no assertSame: MySQL reordena las claves de un JSON al guardarlo.
        $this->assertEquals(['tamano_pulgadas' => '24', 'conexion' => 'HDMI'], $equipo->caracteristicas);
        $this->assertSame('Rayón en la esquina inferior.', $equipo->observaciones);
    }

    public function test_sin_caracteristicas_la_columna_queda_vacia(): void
    {
        $this->formularioValido()->call('guardar')->assertHasNoErrors();

        $this->assertNull(Equipo::sole()->caracteristicas);
    }

    public function test_un_equipo_de_tercero_guarda_propietario_y_figura(): void
    {
        $this->formularioValido([
            'propiedad' => 'tercero',
            'propietarioTercero' => 'Ministerio TIC',
            'figura' => 'comodato',
        ])->call('guardar')->assertHasNoErrors();

        $equipo = Equipo::sole();

        $this->assertSame('tercero', $equipo->propiedad);
        $this->assertSame('Ministerio TIC', $equipo->propietario_tercero);
        $this->assertSame('comodato', $equipo->figura_tercero);
    }

    public function test_la_figura_del_tercero_debe_ser_una_de_las_permitidas(): void
    {
        $this->formularioValido([
            'propiedad' => 'tercero',
            'propietarioTercero' => 'Ministerio TIC',
            'figura' => 'Otra',
        ])->call('guardar')->assertHasErrors(['figura' => 'in']);

        $this->assertSame(0, Equipo::count());
    }

    public function test_el_serial_es_unico_sin_importar_mayusculas_ni_espacios(): void
    {
        $this->crearEquipoExistente('CN-0001', null);

        $this->formularioValido(['serial' => '  cn-0001 '])
            ->assertSet('serial', 'CN-0001')
            ->call('guardar')
            ->assertHasErrors('serial');

        $this->assertSame(1, Equipo::count());
    }

    public function test_el_serial_se_guarda_normalizado(): void
    {
        $this->formularioValido(['serial' => '  cn-0001 '])->call('guardar')->assertHasNoErrors();

        $this->assertSame('CN-0001', Equipo::sole()->serial);
    }

    public function test_la_cedula_del_responsable_solo_admite_digitos(): void
    {
        $conResponsable = [
            'asignarResponsable' => true,
            'responsableNombre' => 'Ana Pérez',
            'vinculacion' => 'planta',
        ];

        $this->formularioValido($conResponsable + ['responsableCedula' => 'No estaba funcionario'])
            ->call('guardar')
            ->assertHasErrors(['responsableCedula' => 'regex']);

        $this->formularioValido($conResponsable + ['responsableCedula' => '1.067.888.999'])
            ->assertSet('responsableCedula', '1067888999')
            ->call('guardar')
            ->assertHasNoErrors();

        $asignacion = Asignacion::with('persona')->sole();

        $this->assertSame('1067888999', $asignacion->persona->cedula);
        $this->assertSame('en_servicio', Equipo::sole()->estado_ciclo_vida);
    }
}
