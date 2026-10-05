<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evento>
 *
 * NOTA: esta factory existe para pruebas y para herramientas de desarrollo. El
 * flujo normal de la aplicación NUNCA debe crear un Evento directamente (ni con
 * esta factory ni con Evento::create()): debe usar siempre
 * App\Services\HistorialService::registrar().
 */
class EventoFactory extends Factory
{
    protected $model = Evento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'tipo' => fake()->randomElement([
                'alta',
                'traslado_responsable',
                'cambio_componente',
                'diagnostico',
                'baja',
                'actualizacion_datos',
                'anulacion_aclaracion',
            ]),
            'fecha' => now(),
            'descripcion' => fake('es_ES')->sentence(),
            'usuario_id' => User::factory(),
            'estado_firma' => 'no_aplica',
            'evento_anulado_id' => null,
        ];
    }

    public function alta(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'alta',
            'descripcion' => 'Alta inicial del equipo en el sistema.',
        ]);
    }
}
