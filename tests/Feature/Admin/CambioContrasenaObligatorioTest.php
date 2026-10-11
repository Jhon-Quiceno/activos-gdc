<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RF-44: la contraseña inicial asignada por el Administrador se debe cambiar
 * en el primer ingreso antes de poder usar el resto del sistema.
 */
class CambioContrasenaObligatorioTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_que_debe_cambiar_la_contrasena_es_redirigido_desde_cualquier_pantalla(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_contrasena' => true]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.obligatoria'));
    }

    public function test_un_usuario_que_no_debe_cambiarla_entra_normalmente(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_contrasena' => false]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_la_pantalla_de_cambio_obligatorio_no_redirige_en_bucle(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_contrasena' => true]);

        $this->actingAs($usuario)
            ->get(route('password.obligatoria'))
            ->assertOk();
    }

    public function test_al_guardar_la_nueva_contrasena_el_usuario_ya_puede_entrar_al_resto_del_sistema(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_contrasena' => true]);

        Livewire::actingAs($usuario)
            ->test(\App\Livewire\Admin\CambioContrasenaObligatorio::class)
            ->set('password', 'una-contrasena-nueva-segura')
            ->set('password_confirmation', 'una-contrasena-nueva-segura')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertFalse($usuario->refresh()->debe_cambiar_contrasena);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
