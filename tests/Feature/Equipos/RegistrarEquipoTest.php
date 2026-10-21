<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\Crear;
use App\Models\Asignacion;
use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\ModeloEquipo;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Registro de equipo (RF-01, RF-02, RF-04, RF-11, RF-18, RF-19; RN-02, RN-03,
 * RN-16, RN-21, RN-27), con los campos del modelo v4.
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

    private Proceso $procesoTic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        $this->monitor = TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
        $this->marca = Marca::create(['nombre' => 'HP']);
        $this->sede = Sede::create(['municipio_id' => \App\Models\Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería'])->id, 'nombre' => 'Palacio Naín']);
        $this->piso = Piso::create(['numero' => 3, 'sede_id' => $this->sede->id]);
        $this->procesoTic = Proceso::create(['nombre' => 'Gestión Tecnológica y de Sistemas de Información', 'tipo' => 'apoyo']);
        $this->dependencia = Dependencia::create(['nombre' => 'Dirección TIC', 'proceso_id' => $this->procesoTic->id]);
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
            'estadoFuncionamiento' => 'bueno',
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

    /**
     * RN-02: el serial es obligatorio salvo que se marque que el equipo no
     * tiene uno; en ese caso se guarda con un serial provisional y queda
     * "pendiente de verificar" en vez de "verificado".
     */
    public function test_el_serial_es_obligatorio_salvo_que_no_tenga(): void
    {
        $this->formularioValido(['serial' => ''])
            ->call('guardar')
            ->assertHasErrors(['serial' => 'required']);

        $this->formularioValido(['serial' => '', 'sinSerial' => true])
            ->call('guardar')
            ->assertHasNoErrors();

        $equipo = Equipo::sole();
        $this->assertStringStartsWith('PENDIENTE-REG-', $equipo->serial);
        $this->assertSame('pendiente_de_verificar', $equipo->verificacion);
    }

    public function test_no_permite_marcar_sin_serial_y_sin_codigo_a_la_vez(): void
    {
        $this->formularioValido([
            'serial' => '',
            'sinSerial' => true,
            'codigoActivo' => '',
            'sinCodigoActivo' => true,
        ])->call('guardar')->assertHasErrors(['sinSerial']);

        $this->assertSame(0, Equipo::count());
    }

    public function test_dos_equipos_con_serial_provisional_no_chocan_entre_si(): void
    {
        $this->formularioValido(['serial' => '', 'sinSerial' => true, 'codigoActivo' => 'I1-024147'])
            ->call('guardar')->assertHasNoErrors();

        $this->formularioValido(['serial' => '', 'sinSerial' => true, 'codigoActivo' => 'I1-024148'])
            ->call('guardar')->assertHasNoErrors();

        $this->assertSame(2, Equipo::distinct('serial')->count('serial'));
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

    public function test_un_equipo_de_tercero_guarda_el_propietario_del_catalogo_y_la_figura(): void
    {
        $ministerio = Proveedor::create(['razon_social' => 'Ministerio TIC', 'nit' => '899999053']);

        $this->formularioValido([
            'propiedad' => 'tercero',
            'proveedorTerceroId' => $ministerio->id,
            'figura' => 'comodato',
        ])->call('guardar')->assertHasNoErrors();

        $equipo = Equipo::sole();

        $this->assertSame('tercero', $equipo->propiedad);
        $this->assertSame($ministerio->id, $equipo->proveedor_tercero_id);
        $this->assertSame('comodato', $equipo->figura_tercero);
    }

    public function test_un_equipo_de_tercero_exige_propietario(): void
    {
        $this->formularioValido(['propiedad' => 'tercero', 'figura' => 'convenio'])
            ->call('guardar')
            ->assertHasErrors(['proveedorTerceroId' => 'required']);

        $this->assertSame(0, Equipo::count());
    }

    public function test_un_propietario_que_no_esta_se_agrega_al_catalogo(): void
    {
        $this->formularioValido([
            'propiedad' => 'tercero',
            'agregarProveedor' => true,
            'proveedorNuevo' => '  Arrendamientos del Sinú S.A.S.  ',
            'proveedorNuevoNit' => '900123456',
            'figura' => 'arrendamiento',
        ])->call('guardar')->assertHasNoErrors();

        $proveedor = Proveedor::sole();

        $this->assertSame('Arrendamientos del Sinú S.A.S.', $proveedor->razon_social);
        $this->assertSame($proveedor->id, Equipo::sole()->proveedor_tercero_id);
        $this->assertSame('arrendamiento', Equipo::sole()->figura_tercero);
    }

    public function test_la_figura_del_tercero_debe_ser_una_de_las_permitidas(): void
    {
        $this->formularioValido([
            'propiedad' => 'tercero',
            'proveedorTerceroId' => Proveedor::create(['razon_social' => 'Ministerio TIC'])->id,
            'figura' => 'Otra',
        ])->call('guardar')->assertHasErrors(['figura' => 'in']);

        $this->assertSame(0, Equipo::count());
    }

    public function test_el_modelo_se_elige_del_catalogo_de_la_marca_y_el_tipo(): void
    {
        $p24 = ModeloEquipo::create(['marca_id' => $this->marca->id, 'tipo_equipo_id' => $this->monitor->id, 'nombre' => 'P24']);
        $deOtraMarca = ModeloEquipo::create([
            'marca_id' => Marca::create(['nombre' => 'Lenovo'])->id,
            'tipo_equipo_id' => $this->monitor->id,
            'nombre' => 'ThinkVision T24',
        ]);

        $this->formularioValido(['modeloId' => $deOtraMarca->id])
            ->call('guardar')
            ->assertHasErrors(['modeloId' => 'exists']);

        $this->formularioValido(['modeloId' => $p24->id])->call('guardar')->assertHasNoErrors();

        $this->assertSame($p24->id, Equipo::sole()->modelo_id);
    }

    public function test_un_modelo_que_no_esta_se_agrega_al_catalogo(): void
    {
        $this->formularioValido(['agregarModelo' => true, 'modeloNuevo' => ' E24 G5 '])
            ->call('guardar')
            ->assertHasNoErrors();

        $modelo = ModeloEquipo::sole();

        $this->assertSame('E24 G5', $modelo->nombre);
        $this->assertSame($this->marca->id, $modelo->marca_id);
        $this->assertSame($this->monitor->id, $modelo->tipo_equipo_id);
        $this->assertSame($modelo->id, Equipo::sole()->modelo_id);
    }

    public function test_al_cambiar_la_marca_se_borra_el_modelo_elegido(): void
    {
        $p24 = ModeloEquipo::create(['marca_id' => $this->marca->id, 'tipo_equipo_id' => $this->monitor->id, 'nombre' => 'P24']);

        $this->formularioValido(['modeloId' => $p24->id])
            ->set('marcaId', Marca::create(['nombre' => 'Lenovo'])->id)
            ->assertSet('modeloId', null);
    }

    public function test_el_numero_de_activo_lo_asigna_el_sistema(): void
    {
        $this->formularioValido()->assertSee('Se asigna al guardar')->call('guardar')->assertHasNoErrors();

        $this->assertNotNull(Equipo::sole()->numero_activo);
    }

    public function test_el_proceso_por_defecto_es_el_de_la_dependencia(): void
    {
        $this->formularioValido(['descripcion' => 'Pantalla de la sala de juntas'])->call('guardar')->assertHasNoErrors();

        $equipo = Equipo::sole();

        $this->assertSame($this->procesoTic->id, $equipo->proceso_id);
        $this->assertSame('Pantalla de la sala de juntas', $equipo->descripcion);

        $otroProceso = Proceso::create(['nombre' => 'Gestión Documental', 'tipo' => 'apoyo']);

        $this->formularioValido(['serial' => 'CN-0002', 'codigoActivo' => 'I1-024148', 'procesoId' => $otroProceso->id])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame($otroProceso->id, Equipo::where('serial', 'CN-0002')->value('proceso_id'));
    }

    public function test_el_piso_debe_ser_de_la_sede_elegida(): void
    {
        $pisoDeOtraSede = Piso::create(['numero' => 2, 'sede_id' => Sede::create(['municipio_id' => \App\Models\Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería'])->id, 'nombre' => 'Edificio Morindó'])->id]);

        $this->formularioValido(['pisoId' => $pisoDeOtraSede->id])
            ->call('guardar')
            ->assertHasErrors(['pisoId' => 'exists']);

        $this->formularioValido()->call('guardar')->assertHasNoErrors();

        // RN-21: la sede de la asignación es la del piso.
        $this->assertSame($this->sede->id, Asignacion::sole()->piso->sede_id);
    }

    public function test_un_tipo_con_configuracion_de_computo_muestra_esa_seccion(): void
    {
        $pc = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'usa_configuracion_computo' => true]);
        $nas = TipoEquipo::create(['nombre' => 'NAS', 'familia' => 'computo', 'usa_configuracion_computo' => false]);

        Livewire::actingAs($this->usuario)->test(Crear::class)
            ->call('seleccionarTipo', $pc->id)
            ->assertSet('usaConfiguracionComputo', true)
            ->assertSee('4. Configuración de cómputo')
            ->call('seleccionarTipo', $nas->id)
            ->assertSet('usaConfiguracionComputo', false)
            ->assertDontSee('4. Configuración de cómputo');
    }

    public function test_muestra_las_familias_nuevas_de_tipos(): void
    {
        TipoEquipo::create(['nombre' => 'Cámara de vigilancia', 'familia' => 'seguridad_electronica']);
        TipoEquipo::create(['nombre' => 'Teléfono IP', 'familia' => 'telefonia']);
        TipoEquipo::create(['nombre' => 'Tipo retirado', 'familia' => 'telefonia', 'activo' => false]);

        Livewire::actingAs($this->usuario)->test(Crear::class)
            ->assertSee('Seguridad electrónica')
            ->assertSee('Cámara de vigilancia')
            ->assertSee('Teléfono IP')
            ->assertDontSee('Tipo retirado');
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
            'responsableNombres' => 'Ana',
            'vinculacion' => 'planta',
        ];

        $this->formularioValido($conResponsable + ['responsableDocumento' => 'No estaba funcionario'])
            ->call('guardar')
            ->assertHasErrors(['responsableDocumento' => 'regex']);

        $this->formularioValido($conResponsable + ['responsableDocumento' => '1.067.888.999'])
            ->assertSet('responsableDocumento', '1067888999')
            ->call('guardar')
            ->assertHasNoErrors();

        $asignacion = Asignacion::with('persona')->sole();

        $this->assertSame('1067888999', $asignacion->persona->numero_documento);
        $this->assertSame('en_servicio', Equipo::sole()->estado_ciclo_vida);
    }

    public function test_un_pasaporte_admite_letras(): void
    {
        $this->formularioValido([
            'asignarResponsable' => true,
            'responsableNombres' => 'John',
            'responsableTipoDocumento' => 'PA',
            'responsableDocumento' => 'ab 123 456',
            'vinculacion' => 'contratista',
        ])->assertSet('responsableDocumento', 'AB123456')->call('guardar')->assertHasNoErrors();

        $persona = Asignacion::with('persona')->sole()->persona;

        $this->assertSame('PA', $persona->tipo_documento);
        $this->assertSame('AB123456', $persona->numero_documento);
    }

    public function test_el_responsable_se_guarda_con_los_campos_v4(): void
    {
        $profesional = Cargo::create(['codigo' => '219', 'denominacion' => 'Profesional Universitario', 'nivel' => 'profesional']);

        $this->formularioValido([
            'asignarResponsable' => true,
            'responsableNombres' => '  Ana María ',
            'responsableApellidos' => 'Pérez Díaz',
            'responsableDocumento' => '1067888999',
            'responsableCargoId' => $profesional->id,
            'responsableEncargo' => true,
            'vinculacion' => 'pasante',
        ])->call('guardar')->assertHasNoErrors();

        $persona = Asignacion::with('persona')->sole()->persona;

        $this->assertSame('Ana María', $persona->nombres);
        $this->assertSame('Pérez Díaz', $persona->apellidos);
        $this->assertSame('Ana María Pérez Díaz', $persona->nombreCompleto());
        $this->assertSame($profesional->id, $persona->cargo_id);
        $this->assertSame('Profesional Universitario (E)', $persona->cargoTexto());
        $this->assertSame('pasante', $persona->tipo_vinculacion);
        $this->assertSame($this->dependencia->id, $persona->dependencia_id);
    }

    public function test_un_documento_ya_registrado_reutiliza_la_persona(): void
    {
        $datos = [
            'asignarResponsable' => true,
            'responsableNombres' => 'Ana',
            'responsableDocumento' => '1067888999',
            'vinculacion' => 'planta',
        ];

        $this->formularioValido($datos)->call('guardar')->assertHasNoErrors();
        $this->formularioValido($datos + ['serial' => 'CN-0002', 'codigoActivo' => 'I1-024148'])->call('guardar')->assertHasNoErrors();

        $this->assertSame(1, Asignacion::distinct('persona_id')->count('persona_id'));
    }
}
