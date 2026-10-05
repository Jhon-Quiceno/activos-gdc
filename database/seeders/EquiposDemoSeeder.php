<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Database\Seeder;

/**
 * Crea ~100 equipos de prueba (hoja de vida completa: evento de alta, asignación
 * de responsable cuando aplica, y configuración de cómputo + componentes básicos
 * para los equipos de la familia "computo"). Requiere que CatalogosSeeder y
 * UsersSeeder ya se hayan ejecutado.
 */
class EquiposDemoSeeder extends Seeder
{
    public function run(): void
    {
        $historial = app(HistorialService::class);

        $usuarioSistema = User::query()->where('perfil', 'administrador')->first()
            ?? User::factory()->create(['perfil' => 'administrador']);

        $personas = Persona::factory(40)->create();

        $tipoComputoIds = TipoEquipo::query()->where('familia', 'computo')->pluck('id');

        $sedeIds = Sede::query()->pluck('id');
        $pisoIds = Piso::query()->pluck('id');

        $componentesBasicosComputo = TipoComponente::query()
            ->whereIn('nombre', ['RAM', 'Disco', 'Teclado', 'Mouse'])
            ->get()
            ->keyBy('nombre');

        $sistemasOperativosIds = SistemaOperativo::query()->pluck('id');

        Equipo::factory()
            ->count(100)
            ->create()
            ->each(function (Equipo $equipo) use (
                $historial,
                $usuarioSistema,
                $personas,
                $tipoComputoIds,
                $sedeIds,
                $pisoIds,
                $componentesBasicosComputo,
                $sistemasOperativosIds,
            ) {
                // RF-12 / RN-06: toda alta queda registrada en la hoja de vida vía
                // HistorialService, nunca creando el Evento a mano.
                $historial->registrar(
                    equipo: $equipo,
                    tipo: 'alta',
                    usuario: $usuarioSistema,
                    descripcion: 'Alta inicial del equipo en el sistema (carga de datos demo).',
                );

                // ~70% con responsable asignado (asignación abierta), ~30% sin asignar.
                $conResponsable = fake()->boolean(70);
                $persona = $conResponsable ? $personas->random() : null;

                Asignacion::create([
                    'equipo_id' => $equipo->id,
                    'persona_id' => $persona?->id,
                    'sede_id' => $sedeIds->random(),
                    'piso_id' => $pisoIds->random(),
                    'dependencia_id' => $persona?->dependencia_id,
                    'fecha_inicio' => now()->subDays(fake()->numberBetween(1, 400)),
                    'fecha_fin' => null,
                ]);

                if (! $conResponsable) {
                    $equipo->update(['estado_ciclo_vida' => 'sin_asignar']);
                }

                // Configuración de cómputo + componentes básicos solo para la familia "computo".
                if ($tipoComputoIds->contains($equipo->tipo_equipo_id)) {
                    ConfiguracionComputo::create([
                        'equipo_id' => $equipo->id,
                        'sistema_operativo_id' => $sistemasOperativosIds->isNotEmpty() ? $sistemasOperativosIds->random() : null,
                        'licencia' => fake()->bothify('LIC-#####-????'),
                        'tiene_antivirus' => fake()->boolean(80),
                        'antivirus_producto' => fake()->randomElement(['Windows Defender', 'ESET', 'Kaspersky', null]),
                        'nombre_red' => fake()->domainWord().'-PC'.fake()->numberBetween(1, 999),
                    ]);

                    foreach ($componentesBasicosComputo as $nombre => $tipoComponente) {
                        Componente::factory()->create([
                            'equipo_id' => $equipo->id,
                            'tipo_componente_id' => $tipoComponente->id,
                            'capacidad_caracteristica' => match ($nombre) {
                                'RAM' => fake()->randomElement(['4GB', '8GB', '16GB']),
                                'Disco' => fake()->randomElement(['256GB SSD', '500GB HDD', '1TB HDD']),
                                default => null,
                            },
                        ]);
                    }
                }
            });
    }
}
