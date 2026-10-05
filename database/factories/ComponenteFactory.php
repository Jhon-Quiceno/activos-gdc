<?php

namespace Database\Factories;

use App\Models\Componente;
use App\Models\Equipo;
use App\Models\TipoComponente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Componente>
 */
class ComponenteFactory extends Factory
{
    protected $model = Componente::class;

    /**
     * @var array<int, string>
     */
    private const CARACTERISTICAS = [
        '4GB RAM', '8GB RAM', '16GB RAM',
        '256GB SSD', '500GB HDD', '1TB HDD',
        'Intel Core i5', 'Intel Core i7', 'AMD Ryzen 5',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'tipo_componente_id' => TipoComponente::query()->inRandomOrder()->value('id'),
            'capacidad_caracteristica' => fake()->randomElement(self::CARACTERISTICAS),
            'marca' => fake('es_ES')->company(),
            'serial' => fake()->unique()->bothify('CMP-????####'),
            'estado' => fake()->randomElement(['Bueno', 'Regular', 'Dañado']),
            'fecha_instalacion' => fake()->dateTimeBetween('-3 years', 'now'),
            'fecha_retiro' => null,
        ];
    }

    public function retirado(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_retiro' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
