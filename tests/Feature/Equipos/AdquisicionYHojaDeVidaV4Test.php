<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\Crear;
use App\Livewire\Equipos\Editar;
use App\Livewire\Equipos\HojaDeVida;
use App\Models\Asignacion;
use App\Models\Cargo;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Datos de adquisición (RF-52), ofimática y red, y la hoja de vida con los
 * datos del modelo v4 (RF-09, RF-18).
 */
class AdquisicionYHojaDeVidaV4Test extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private TipoEquipo $pc;

    private Marca $marca;

    private Sede $sede;

    private Piso $piso;

    private Dependencia $dependencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        $this->pc = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'usa_configuracion_computo' => true, 'vida_util_meses' => 60]);
        $this->marca = Marca::create(['nombre' => 'HP']);
        $this->sede = Sede::create(['municipio_id' => \App\Models\Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería'])->id, 'nombre' => 'Palacio Naín', 'direccion' => 'Calle 27 No. 3-28']);
        $this->piso = Piso::create(['numero' => 6, 'sede_id' => $this->sede->id]);
        $this->dependencia = Dependencia::create(['nombre' => 'Dirección TIC']);
    }

    private function registro(array $cambios = [])
    {
        $formulario = Livewire::actingAs($this->usuario)->test(Crear::class)->call('seleccionarTipo', $this->pc->id);

        foreach (array_merge([
            'serial' => 'CN-0001',
            'codigoActivo' => 'I1-024147',
            'marcaId' => $this->marca->id,
            'estadoFuncionamiento' => 'Bueno',
            'sedeId' => $this->sede->id,
            'pisoId' => $this->piso->id,
            'dependenciaId' => $this->dependencia->id,
        ], $cambios) as $campo => $valor) {
            $formulario->set($campo, $valor);
        }

        return $formulario;
    }

    private function pc(array $datos = []): Equipo
    {
        return Equipo::create(array_merge([
            'serial' => 'SN-PC-1',
            'tipo_equipo_id' => $this->pc->id,
            'marca_id' => $this->marca->id,
            'propiedad' => 'gobernacion',
            'estado_funcionamiento' => 'Bueno',
        ], $datos));
    }

    public function test_registra_los_datos_de_adquisicion_con_el_valor_en_pesos(): void
    {
        $proveedor = Proveedor::create(['razon_social' => 'Tecnología del Sinú S.A.S.']);

        $this->registro([
            'formaIngreso' => 'compra',
            'fechaAdquisicion' => '2024-03-15',
            'valorAdquisicion' => '$ 2.350.000,50',
            'proveedorId' => $proveedor->id,
            'numeroContrato' => 'CD-045-2024',
            'numeroFactura' => 'FE-9981',
            'fechaFinGarantia' => '2027-03-15',
            'vidaUtilMeses' => '48',
        ])->assertSet('valorAdquisicion', '2350000,50')->call('guardar')->assertHasNoErrors();

        $equipo = Equipo::sole();

        $this->assertSame('compra', $equipo->forma_ingreso);
        $this->assertSame('2024-03-15', $equipo->fecha_adquisicion->toDateString());
        $this->assertSame('2350000.50', $equipo->valor_adquisicion);
        $this->assertSame($proveedor->id, $equipo->proveedor_id);
        $this->assertSame('CD-045-2024', $equipo->numero_contrato);
        $this->assertSame(48, $equipo->vida_util_meses);
    }

    public function test_la_garantia_no_termina_antes_de_la_adquisicion_ni_el_valor_es_texto(): void
    {
        $this->registro(['fechaAdquisicion' => '2024-03-15', 'fechaFinGarantia' => '2024-01-01', 'valorAdquisicion' => 'dos millones'])
            ->call('guardar')
            ->assertHasErrors(['fechaFinGarantia' => 'after_or_equal', 'valorAdquisicion' => 'regex']);

        $this->assertSame(0, Equipo::count());
    }

    public function test_guarda_ofimatica_y_red_y_normaliza_la_mac(): void
    {
        $this->registro([
            'ofimatica' => 'Microsoft 365',
            'dominio' => 'cordoba.gov.co',
            'direccionIp' => '10.20.30.40',
            'direccionMac' => '3c-52-82-1a-2b-4c',
        ])->assertSet('direccionMac', '3C:52:82:1A:2B:4C')->call('guardar')->assertHasNoErrors();

        $configuracion = ConfiguracionComputo::sole();

        $this->assertSame('Microsoft 365', $configuracion->ofimatica);
        $this->assertSame('10.20.30.40', $configuracion->direccion_ip);
        $this->assertSame('3C:52:82:1A:2B:4C', $configuracion->direccion_mac);

        $this->registro(['serial' => 'CN-0002', 'codigoActivo' => 'I1-024148', 'direccionIp' => '999.1.1.1'])
            ->call('guardar')
            ->assertHasErrors(['direccionIp' => 'ip']);
    }

    public function test_editar_la_adquisicion_queda_en_el_historial_con_valor_en_pesos(): void
    {
        $equipo = $this->pc(['forma_ingreso' => 'carga_inicial']);

        Livewire::actingAs($this->usuario)->test(Editar::class, ['equipo' => $equipo])
            ->assertSet('formaIngreso', 'carga_inicial')
            ->set('formaIngreso', 'donacion')
            ->set('valorAdquisicion', '1.800.000')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('donacion', $equipo->fresh()->forma_ingreso);

        $evento = Evento::sole();
        $this->assertEquals(['forma_ingreso' => 'Carga inicial', 'valor_adquisicion' => null], $evento->valores['antes']);
        $this->assertEquals(['forma_ingreso' => 'Donación', 'valor_adquisicion' => '$ 1.800.000'], $evento->valores['despues']);
    }

    public function test_la_hoja_de_vida_avisa_garantia_por_vencer_y_vida_util_superada(): void
    {
        $equipo = $this->pc([
            'fecha_adquisicion' => now()->subYears(6)->toDateString(),
            'fecha_fin_garantia' => now()->addDays(20)->toDateString(),
            'valor_adquisicion' => 2350000,
        ]);

        Livewire::actingAs($this->usuario)->test(HojaDeVida::class, ['equipo' => $equipo])
            ->assertSee('Garantía por vencer')
            ->assertSee('Vida útil superada')
            ->assertSee('Superó su vida útil de 60 meses')
            ->assertSee('$ 2.350.000');
    }

    public function test_la_hoja_de_vida_muestra_responsable_v4_numero_de_activo_y_ubicacion(): void
    {
        $equipo = $this->pc(['descripcion' => 'Equipo de la mesa de ayuda']);
        $cargo = Cargo::create(['codigo' => '219', 'denominacion' => 'Profesional Universitario', 'nivel' => 'profesional']);
        $persona = Persona::create([
            'nombres' => 'Ana María',
            'apellidos' => 'Pérez',
            'tipo_documento' => 'CC',
            'numero_documento' => '1067888999',
            'cargo_id' => $cargo->id,
            'encargo' => true,
            'tipo_vinculacion' => 'planta',
        ]);
        Asignacion::create([
            'equipo_id' => $equipo->id,
            'persona_id' => $persona->id,
            'piso_id' => $this->piso->id,
            'dependencia_id' => $this->dependencia->id,
            'fecha_inicio' => now(),
        ]);

        Livewire::actingAs($this->usuario)->test(HojaDeVida::class, ['equipo' => $equipo->fresh()])
            ->assertSee('N.º de activo '.$equipo->fresh()->numero_activo)
            ->assertSee('Ana María Pérez')
            ->assertSee('CC 1067888999')
            ->assertSee('Profesional Universitario (E)')
            ->assertSee('Palacio Naín · Calle 27 No. 3-28')
            ->assertSee('Piso 6')
            ->assertSee('Equipo de la mesa de ayuda');
    }

    public function test_la_direccion_de_una_sede_reservada_no_se_muestra(): void
    {
        $refugio = Sede::create(['municipio_id' => \App\Models\Municipio::firstOrCreate(['codigo_dane' => '23001'], ['nombre' => 'Montería'])->id, 'nombre' => 'Casa Refugio', 'direccion' => 'Dirección reservada 123', 'direccion_reservada' => true]);
        $equipo = $this->pc();
        Asignacion::create([
            'equipo_id' => $equipo->id,
            'piso_id' => Piso::create(['numero' => 1, 'sede_id' => $refugio->id])->id,
            'dependencia_id' => $this->dependencia->id,
            'fecha_inicio' => now(),
        ]);

        // RN-28: la dirección de la Casa Refugio nunca se muestra.
        Livewire::actingAs($this->usuario)->test(HojaDeVida::class, ['equipo' => $equipo->fresh()])
            ->assertSee('Casa Refugio')
            ->assertDontSee('Dirección reservada 123');
    }
}
