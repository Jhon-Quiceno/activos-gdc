<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vinculacion = fake()->randomElement(['planta', 'contratista']);

        return [
            // Datos ficticios: nunca se usan nombres ni documentos de la planta real.
            'tipo_documento' => 'CC',
            'numero_documento' => fake()->unique()->numerify('##########'),
            'nombres' => fake('es_ES')->firstName(),
            'apellidos' => fake('es_ES')->lastName().' '.fake('es_ES')->lastName(),
            'correo_institucional' => fake()->unique()->userName().'@cordoba.gov.co',
            // Catálogo del Decreto 607 (null si la prueba no sembró catálogos).
            'cargo_id' => Cargo::query()->whereNotNull('codigo')->inRandomOrder()->value('id'),
            'grado' => fake()->randomElement(['02', '06', '07', '08', '10']),
            'dependencia_id' => Dependencia::query()->inRandomOrder()->value('id'),
            'tipo_vinculacion' => $vinculacion,
            'tipo_provision' => $vinculacion === 'planta'
                ? fake()->randomElement(['carrera_administrativa', 'provisional', 'libre_nombramiento'])
                : 'no_aplica',
            'numero_contrato' => $vinculacion === 'contratista' ? fake()->numerify('CPS-####-2026') : null,
            'activo' => true,
            // Campos viejos (se borran el día 2): se derivan de los nuevos y se
            // pueden sobrescribir igual que antes, p. ej. create(['nombre' => 'X']).
            'nombre' => fn (array $a) => trim($a['nombres'].' '.$a['apellidos']),
            'cedula' => fn (array $a) => $a['numero_documento'],
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
