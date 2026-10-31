<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\FotoEquipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FotoEquipo>
 */
class FotoEquipoFactory extends Factory
{
    protected $model = FotoEquipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'archivo_path' => 'fotos-equipo/'.fake()->uuid().'.jpg',
            'descripcion' => fake('es_ES')->sentence(4),
            'tomada_en' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'es_principal' => false,
            'subida_por' => User::factory(),
        ];
    }

    public function principal(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_principal' => true,
        ]);
    }
}
