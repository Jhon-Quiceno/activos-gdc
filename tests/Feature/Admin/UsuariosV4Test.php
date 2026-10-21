<?php

use App\Livewire\Admin\UsuariosPanel;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->persona = Persona::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'numero_documento' => '1067']);
});

it('crea un usuario enlazado a su persona (RF-44)', function () {
    Livewire::test(UsuariosPanel::class)
        ->set('nombre', 'Ana Pérez')
        ->set('correo', 'ana@cordoba.gov.co')
        ->set('personaId', $this->persona->id)
        ->set('contrasena', 'secret-123')
        ->call('crear')
        ->assertHasNoErrors();

    expect(User::where('email', 'ana@cordoba.gov.co')->first()?->persona_id)->toBe($this->persona->id);
});

it('no permite enlazar la misma persona a dos usuarios', function () {
    User::factory()->create(['persona_id' => $this->persona->id]);

    Livewire::test(UsuariosPanel::class)
        ->set('nombre', 'Otro')
        ->set('correo', 'otro@cordoba.gov.co')
        ->set('personaId', $this->persona->id)
        ->set('contrasena', 'secret-123')
        ->call('crear')
        ->assertHasErrors('personaId');
});

it('restablece la contraseña y obliga a cambiarla (RF-47)', function () {
    $usuario = User::factory()->create(['debe_cambiar_contrasena' => false, 'password' => bcrypt('vieja-clave')]);

    Livewire::test(UsuariosPanel::class)
        ->call('restablecerContrasena', $usuario->id);

    $usuario->refresh();

    expect($usuario->debe_cambiar_contrasena)->toBeTrue()
        ->and(Hash::check('vieja-clave', $usuario->password))->toBeFalse();
});
