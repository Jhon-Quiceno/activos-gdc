<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        try {
            $this->crearEquipoBase(['codigo_activo' => 'I1-000001']);
            $this->fail('Se esperaba que la validación de RN-03 fallara.');
        } catch (ValidationException $validationException) {
            // Debe ser un error de validación "amigable" (traducible a un mensaje
            // de formulario), no una InvalidArgumentException cruda que produciría
            // un 500 en cualquier caller que no la capture explícitamente.
            $this->assertArrayHasKey('codigo_activo', $validationException->errors());
        }
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

        $this->expectException(ValidationException::class);

        $segundo->update(['codigo_activo_justificacion' => null]);
    }

    public function test_no_permite_justificacion_de_solo_espacios(): void
    {
        $this->crearEquipoBase(['codigo_activo' => 'I1-000005']);

        $this->expectException(ValidationException::class);

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

    public function test_codigo_activo_igual_a_cero_no_se_salta_la_validacion(): void
    {
        // Bug: empty("0") es true en PHP. Un código de activo literal "0" no puede
        // saltarse la validación de RN-03 como si no tuviera código.
        $this->crearEquipoBase(['codigo_activo' => '0']);

        $this->expectException(ValidationException::class);

        $this->crearEquipoBase(['codigo_activo' => '0']);
    }

    public function test_espacios_alrededor_del_codigo_no_evitan_la_deteccion_de_duplicado(): void
    {
        $this->crearEquipoBase(['codigo_activo' => 'I1-000006']);

        $this->expectException(ValidationException::class);

        // Mismo código con espacios de más: debe normalizarse y detectarse igual
        // como duplicado, no tratarse como un código distinto.
        $this->crearEquipoBase(['codigo_activo' => '  I1-000006  ']);
    }

    public function test_el_codigo_activo_se_normaliza_con_trim_al_guardarse(): void
    {
        $equipo = $this->crearEquipoBase(['codigo_activo' => '  I1-000007  ']);

        $this->assertSame('I1-000007', $equipo->codigo_activo);
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'codigo_activo' => 'I1-000007']);
    }

    public function test_existe_un_indice_no_unico_sobre_codigo_activo(): void
    {
        $indices = DB::select("SHOW INDEX FROM equipos WHERE Column_name = 'codigo_activo'");

        $this->assertNotEmpty(
            $indices,
            'Se esperaba al menos un índice sobre equipos.codigo_activo (RN-03 ya no puede ser unique).'
        );

        foreach ($indices as $indice) {
            $this->assertSame(
                1,
                (int) $indice->Non_unique,
                "El índice {$indice->Key_name} sobre codigo_activo no debería ser unique (RN-03 permite duplicados con justificación)."
            );
        }
    }
}
