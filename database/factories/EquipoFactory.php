<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    protected $model = Equipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serial' => fake()->unique()->bothify('SN-########??'),
            // Formato I1-###### (correlativo de activo).
            'codigo_activo' => fake()->unique()->numerify('I1-######'),
            // qr_uuid se deja sin definir a propósito: Equipo::booted() lo genera solo.
            'tipo_equipo_id' => TipoEquipo::query()->inRandomOrder()->value('id'),
            'marca_id' => Marca::query()->inRandomOrder()->value('id'),
            'modelo' => fake()->bothify('Modelo ??-####'),
            'propiedad' => fake()->randomElement(['gobernacion', 'tercero']),
            'propietario_tercero' => null,
            'estado_funcionamiento' => fake()->randomElement(['Bueno', 'Regular', 'Malo']),
            'estado_ciclo_vida' => 'en_servicio',
            'verificacion' => fake()->randomElement(['verificado', 'pendiente_de_verificar']),
            'puesto_trabajo_id' => null,
            'importacion_id' => null,
            'fila_origen_importacion' => null,
        ];
    }

    public function deTercero(): static
    {
        return $this->state(fn (array $attributes) => [
            'propiedad' => 'tercero',
            'propietario_tercero' => fake('es_ES')->company(),
        ]);
    }

    public function sinAsignar(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_ciclo_vida' => 'sin_asignar',
        ]);
    }

    public function dadoDeBaja(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_ciclo_vida' => 'dado_de_baja',
        ]);
    }
}
