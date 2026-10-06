<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EquipoTest extends TestCase
{
    use RefreshDatabase;

    private function crearEquipoBase(array $atributos = []): Equipo
    {
        $tipoEquipo = TipoEquipo::firstOrCreate(['nombre' => 'PC de escritorio'], ['familia' => 'computo']);
        $marca = Marca::firstOrCreate(['nombre' => 'HP']);

        return Equipo::create(array_merge([
            'serial' => 'SN-'.uniqid(),
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca_id' => $marca->id,
            'propiedad' => 'gobernacion',
        ], $atributos));
    }

    public function test_no_permite_codigo_activo_duplicado_sin_justificacion(): void
    {
        $this->crearEquipoBase(['codigo_activo' => 'I1-000001']);

        $this->expectException(InvalidArgumentException::class);

        $this->crearEquipoBase(['codigo_activo' => 'I1-000001']);
    }

    public function test_permite_codigo_activo_duplicado_con_justificacion(): void
    {
        $this->crearEquipoBase(['codigo_activo' => 'I1-000002']);

        $segundo = $this->crearEquipoBase([
            'codigo_activo' => 'I1-000002',
            'codigo_activo_justificacion' => 'All in One: PC y pantalla comparten el mismo código de activo.',
        ]);

        $this->assertDatabaseHas('equipos', ['id' => $segundo->id, 'codigo_activo' => 'I1-000002']);
    }

    public function test_no_permite_borrar_la_justificacion_si_el_codigo_sigue_duplicado(): void
    {
        $this->crearEquipoBase(['codigo_activo' => 'I1-000003']);

        $segundo = $this->crearEquipoBase([
            'codigo_activo' => 'I1-000003',
            'codigo_activo_justificacion' => 'All in One: PC y pantalla comparten el mismo código de activo.',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $segundo->update(['codigo_activo_justificacion' => null]);
    }

    public function test_no_permite_justificacion_de_solo_espacios(): void
    {
        $this->crearEquipoBase(['codigo_activo' => 'I1-000005']);

        $this->expectException(InvalidArgumentException::class);

        $this->crearEquipoBase([
            'codigo_activo' => 'I1-000005',
            'codigo_activo_justificacion' => '   ',
        ]);
    }

    public function test_permite_codigo_activo_sin_duplicar_sin_justificacion(): void
    {
        $equipo = $this->crearEquipoBase(['codigo_activo' => 'I1-000004']);

        $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'codigo_activo' => 'I1-000004']);
    }
}
