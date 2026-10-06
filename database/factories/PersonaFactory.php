<?php

namespace Database\Factories;

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
     * Cargos típicos de una entidad pública (fake()->jobTitle() no tiene datos
     * realistas en el locale es_ES, así que se usa un catálogo propio).
     *
     * @var array<int, string>
     */
    private const CARGOS = [
        'Profesional Universitario',
        'Técnico Administrativo',
        'Auxiliar Administrativo',
        'Secretario(a) de Despacho',
        'Director(a) Técnico',
        'Asesor(a)',
        'Contratista de Apoyo a la Gestión',
        'Jefe de Oficina',
        'Subsecretario(a)',
        'Profesional Especializado',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake('es_ES')->name(),
            'cedula' => fake()->unique()->numerify('##########'),
            'cargo' => fake()->randomElement(self::CARGOS),
            'dependencia_id' => Dependencia::query()->inRandomOrder()->value('id'),
            'tipo_vinculacion' => fake()->randomElement(['planta', 'contratista']),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
