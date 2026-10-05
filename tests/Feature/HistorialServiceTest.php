<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class HistorialServiceTest extends TestCase
{
    use RefreshDatabase;

    private function crearEquipo(): Equipo
    {
        $tipoEquipo = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo']);
        $marca = Marca::create(['nombre' => 'HP']);

        return Equipo::create([
            'serial' => 'SN-TEST-0001',
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca_id' => $marca->id,
            'propiedad' => 'gobernacion',
        ]);
    }

    public function test_registrar_crea_un_evento_con_el_usuario_como_autor(): void
    {
        $usuario = User::factory()->create();
        $equipo = $this->crearEquipo();

        $evento = app(HistorialService::class)->registrar(
            equipo: $equipo,
            tipo: 'alta',
            usuario: $usuario,
            descripcion: 'Alta inicial de prueba.',
        );

        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'equipo_id' => $equipo->id,
            'tipo' => 'alta',
            'usuario_id' => $usuario->id,
            'descripcion' => 'Alta inicial de prueba.',
        ]);
    }

    public function test_el_equipo_genera_un_qr_uuid_automaticamente(): void
    {
        $equipo = $this->crearEquipo();

        $this->assertNotEmpty($equipo->qr_uuid);
    }

    public function test_un_evento_no_se_puede_actualizar(): void
    {
        $usuario = User::factory()->create();
        $equipo = $this->crearEquipo();

        $evento = app(HistorialService::class)->registrar($equipo, 'alta', $usuario);

        $this->expectException(LogicException::class);

        $evento->update(['descripcion' => 'intento de edición']);
    }

    public function test_un_evento_no_se_puede_eliminar(): void
    {
        $usuario = User::factory()->create();
        $equipo = $this->crearEquipo();

        $evento = app(HistorialService::class)->registrar($equipo, 'alta', $usuario);

        $this->expectException(LogicException::class);

        $evento->delete();
    }

    public function test_registrar_enlaza_el_evento_anulado_en_una_anulacion(): void
    {
        $usuario = User::factory()->create();
        $equipo = $this->crearEquipo();

        $eventoOriginal = app(HistorialService::class)->registrar($equipo, 'actualizacion_datos', $usuario);

        $eventoAnulacion = app(HistorialService::class)->registrar(
            equipo: $equipo,
            tipo: 'anulacion_aclaracion',
            usuario: $usuario,
            descripcion: 'Se corrige un dato mal digitado.',
            eventoAnulado: $eventoOriginal,
        );

        $this->assertSame($eventoOriginal->id, $eventoAnulacion->fresh()->evento_anulado_id);
    }
}
