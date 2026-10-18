<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nit' => fake()->unique()->numerify('9########-#'),
            'razon_social' => fake('es_ES')->company().' S.A.S.',
            'telefono' => fake()->numerify('60########'),
            'correo' => fake()->unique()->safeEmail(),
        ];
    }
}
