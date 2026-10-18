<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Proveedor;
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
            'forma_ingreso' => fake()->randomElement(['compra', 'compra', 'carga_inicial', 'donacion']),
            'fecha_adquisicion' => fake()->dateTimeBetween('-8 years', '-1 month')->format('Y-m-d'),
            'valor_adquisicion' => fake()->numberBetween(800, 6000) * 1000,
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
            'proveedor_tercero_id' => Proveedor::factory(),
            'figura_tercero' => fake()->randomElement(['comodato', 'convenio', 'proveedor']),
            'forma_ingreso' => 'comodato',
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
