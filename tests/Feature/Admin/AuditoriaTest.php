<?php

namespace Tests\Feature\Admin;

use App\Models\Auditoria;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * RF-46 (Ley 1581): bitácora de auditoría de inicios de sesión y cambios
 * sobre un equipo. El modelo Auditoria ya existía; lo que faltaba era
 * conectarlo en los puntos donde ocurren esos hechos.
 */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_inicio_de_sesion_queda_en_la_bitacora_de_auditoria(): void
    {
        // El login real pasa por un componente Volt (routes/auth.php); lo que
        // importa aquí es que Auth::login() (lo que ese componente llama al
        // autenticar) dispare el evento Login y el listener quede conectado.
        $usuario = User::factory()->create(['debe_cambiar_contrasena' => false]);

        Auth::login($usuario);

        $auditoria = Auditoria::sole();
        $this->assertSame('login', $auditoria->accion);
        $this->assertSame($usuario->id, $auditoria->usuario_id);
    }

    public function test_cada_evento_registrado_por_historial_service_queda_tambien_en_la_auditoria(): void
    {
        $usuario = User::factory()->create();
        $equipo = Equipo::factory()->create([
            'tipo_equipo_id' => TipoEquipo::forceCreate(['nombre' => 'Portátil'])->id,
            'marca_id' => Marca::forceCreate(['nombre' => 'HP'])->id,
        ]);

        app(HistorialService::class)->registrar(
            equipo: $equipo,
            tipo: 'actualizacion_datos',
            usuario: $usuario,
            descripcion: 'Prueba de auditoría.',
        );

        $auditoria = Auditoria::sole();
        $this->assertSame('actualizacion_datos', $auditoria->accion);
        $this->assertSame('Equipo', $auditoria->entidad);
        $this->assertSame($equipo->id, $auditoria->entidad_id);
        $this->assertSame($usuario->id, $auditoria->usuario_id);
    }
}
