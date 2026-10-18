<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\ModeloEquipo;
use App\Models\Persona;
use App\Models\Proveedor;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\UbicacionDependencia;
use App\Models\User;
use App\Models\ValoracionActivo;
use App\Services\HistorialService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Crea ~100 equipos de prueba con su hoja de vida: evento de alta, asignación
 * en un piso real y en una dependencia que funciona en ese piso (PAMP 2026),
 * configuración y componentes para los equipos de cómputo, y valoración GT-M4
 * para cerca de la mitad del hardware. Todos los nombres y documentos son
 * ficticios. Requiere CatalogosSeeder y UsersSeeder.
 */
class EquiposDemoSeeder extends Seeder
{
    public function run(): void
    {
        $historial = app(HistorialService::class);

        $usuarioSistema = User::query()->where('perfil', 'administrador')->first()
            ?? User::factory()->create(['perfil' => 'administrador']);

        $ubicaciones = UbicacionDependencia::query()->with('piso')->get();
        $proveedores = Proveedor::factory(6)->create();
        $modelos = $this->modelos();

        // Cada persona trabaja en una dependencia con ubicación conocida.
        $personas = $ubicaciones->random(40)->map(
            fn (UbicacionDependencia $u) => Persona::factory()->create(['dependencia_id' => $u->dependencia_id])
        );
        $this->asignarJefes($personas);

        $componentesBasicosComputo = TipoComponente::query()
            ->whereIn('nombre', ['RAM', 'Disco', 'Procesador', 'Teclado', 'Mouse'])
            ->get()
            ->keyBy('nombre');

        $sistemasOperativosIds = SistemaOperativo::query()->pluck('id');

        foreach (range(1, 100) as $i) {
            $modelo = $modelos->random();
            $deTercero = fake()->boolean(10);

            $equipo = Equipo::factory()->create([
                'tipo_equipo_id' => $modelo->tipo_equipo_id,
                'marca_id' => $modelo->marca_id,
                'modelo_id' => $modelo->id,
                'propiedad' => $deTercero ? 'tercero' : 'gobernacion',
                'proveedor_tercero_id' => $deTercero ? $proveedores->random()->id : null,
                'figura_tercero' => $deTercero ? 'comodato' : null,
                'proveedor_id' => $deTercero ? null : $proveedores->random()->id,
                'fecha_fin_garantia' => fake()->boolean(40) ? fake()->dateTimeBetween('-1 year', '+2 years')->format('Y-m-d') : null,
            ]);

            // RF-12 / RN-06: toda alta pasa por HistorialService.
            $historial->registrar(
                equipo: $equipo,
                tipo: 'alta',
                usuario: $usuarioSistema,
                descripcion: 'Alta inicial del equipo en el sistema (carga de datos demo).',
            );

            // ~70 % con responsable; la ubicación siempre es un piso real.
            $persona = fake()->boolean(70) ? $personas->random() : null;
            $ubicacion = $persona
                ? $ubicaciones->firstWhere('dependencia_id', $persona->dependencia_id)
                : $ubicaciones->random();

            Asignacion::create([
                'equipo_id' => $equipo->id,
                'persona_id' => $persona?->id,
                'piso_id' => $ubicacion->piso_id,
                'dependencia_id' => $ubicacion->dependencia_id,
                'fecha_inicio' => now()->subDays(fake()->numberBetween(1, 400)),
            ]);

            if (! $persona) {
                $equipo->update(['estado_ciclo_vida' => 'sin_asignar']);
            }

            $equipo->load('tipoEquipo');

            if ($equipo->tipoEquipo->usa_configuracion_computo) {
                $this->configuracionComputo($equipo, $sistemasOperativosIds, $componentesBasicosComputo);
            }

            if (! $equipo->tipoEquipo->esSoporte() && fake()->boolean(50)) {
                $this->valorar($equipo, $usuarioSistema);
            }
        }
    }

    /**
     * Unos cuantos modelos por tipo y marca, para que la ficha use el catálogo.
     *
     * @return Collection<int, ModeloEquipo>
     */
    private function modelos(): Collection
    {
        $catalogo = [
            'PC de escritorio' => ['HP' => ['ProDesk 400 G7', 'EliteDesk 800 G6'], 'Dell' => ['OptiPlex 3080'], 'Lenovo' => ['ThinkCentre M70s']],
            'Portátil' => ['HP' => ['ProBook 440 G8'], 'Lenovo' => ['ThinkPad E14']],
            'All in One' => ['HP' => ['ProOne 400 G6'], 'Lenovo' => ['IdeaCentre AIO 3']],
            'Monitor' => ['HP' => ['P22v G4'], 'Samsung' => ['S24R350'], 'LG' => ['22MK430H']],
            'Impresora' => ['HP' => ['LaserJet Pro M404dn'], 'Epson' => ['EcoTank L3250'], 'Kyocera' => ['ECOSYS P2040dw']],
            'Escáner' => ['Epson' => ['WorkForce DS-530'], 'Canon' => ['imageFORMULA DR-C225']],
            'UPS' => ['APC' => ['Back-UPS 1500VA']],
            'Switch' => ['Cisco' => ['Catalyst 1000-24T'], 'TP-Link' => ['TL-SG1024D']],
            'Access Point' => ['Ubiquiti' => ['UniFi U6 Lite']],
            'Videograbador (DVR/NVR)' => ['Hikvision' => ['DS-7616NI-K2']],
        ];

        $modelos = collect();

        foreach ($catalogo as $tipo => $porMarca) {
            $tipoId = TipoEquipo::where('nombre', $tipo)->value('id');

            foreach ($porMarca as $marca => $nombres) {
                $marcaId = Marca::where('nombre', $marca)->value('id');

                foreach ($nombres as $nombre) {
                    $modelos->push(ModeloEquipo::create(['marca_id' => $marcaId, 'tipo_equipo_id' => $tipoId, 'nombre' => $nombre]));
                }
            }
        }

        return $modelos;
    }

    /**
     * Jefe inmediato: otra persona de la misma dependencia, si la hay.
     *
     * @param  Collection<int, Persona>  $personas
     */
    private function asignarJefes(Collection $personas): void
    {
        $personas->groupBy('dependencia_id')->each(function (Collection $grupo): void {
            $jefe = $grupo->first();
            $grupo->skip(1)->each(fn (Persona $p) => $p->update(['jefe_inmediato_id' => $jefe->id]));
        });
    }

    /**
     * @param  Collection<int, int>  $sistemasOperativosIds
     * @param  Collection<string, TipoComponente>  $componentes
     */
    private function configuracionComputo(Equipo $equipo, Collection $sistemasOperativosIds, Collection $componentes): void
    {
        ConfiguracionComputo::create([
            'equipo_id' => $equipo->id,
            'sistema_operativo_id' => $sistemasOperativosIds->random(),
            'licencia' => fake()->bothify('LIC-#####-????'),
            'ofimatica' => fake()->randomElement(['Office Home and Business 2019', 'Microsoft 365', 'LibreOffice']),
            'tiene_antivirus' => fake()->boolean(80),
            'antivirus_producto' => fake()->randomElement(['FortiEDR', 'Windows Defender', 'ESET', null]),
            'nombre_red' => 'GOB-'.fake()->numberBetween(1000, 9999),
            'dominio' => 'cordoba.local',
            'direccion_ip' => '10.10.'.fake()->numberBetween(1, 30).'.'.fake()->numberBetween(2, 254),
        ]);

        foreach ($componentes as $nombre => $tipoComponente) {
            Componente::factory()->create([
                'equipo_id' => $equipo->id,
                'tipo_componente_id' => $tipoComponente->id,
                'capacidad_caracteristica' => match ($nombre) {
                    'RAM' => fake()->randomElement(['4GB', '8GB', '16GB']),
                    'Disco' => fake()->randomElement(['256GB SSD', '500GB HDD', '1TB HDD']),
                    'Procesador' => fake()->randomElement(['Intel Core i5-10500', 'Intel Core i7-10700', 'AMD Ryzen 5 5600G']),
                    default => null,
                },
            ]);
        }
    }

    private function valorar(Equipo $equipo, User $usuario): void
    {
        $datosPersonales = fake()->randomElement(['si', 'no']);

        ValoracionActivo::registrar($equipo, $usuario, [
            'contiene_datos_personales' => $datosPersonales,
            'clasificacion_datos' => $datosPersonales === 'si' ? fake()->randomElement(['privado', 'semiprivado', 'sensible']) : 'no_aplica',
            'infraestructura_critica' => fake()->randomElement(['si', 'no']),
            'continuidad_negocio' => fake()->randomElement(['si', 'no']),
            'propietario_cargo' => 'Director Administrativo, Financiero, Técnico u Operativo',
            'custodio_cargo' => 'Ingeniero de Soporte',
            'tipo_ubicacion' => 'fisico',
            'tiempo_retencion' => 'Indeterminado',
            'confidencialidad' => fake()->numberBetween(1, 4),
            'integridad' => fake()->numberBetween(1, 4),
            'disponibilidad' => fake()->numberBetween(1, 4),
            'controles_monitoreo' => fake()->randomElement(ValoracionActivo::CONTROLES_MONITOREO),
        ]);
    }
}
