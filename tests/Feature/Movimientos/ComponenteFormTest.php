<?php

namespace Tests\Feature\Movimientos;

use App\Livewire\Movimientos\ComponenteForm;
use App\Models\Componente;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComponenteFormTest extends TestCase
{
    use RefreshDatabase;

    private function crearEquipo(string $serial): Equipo
    {
        $tipoEquipo = TipoEquipo::firstOrCreate(['nombre' => 'PC de escritorio'], ['familia' => 'computo']);
        $marca = Marca::firstOrCreate(['nombre' => 'HP']);

        return Equipo::create([
            'serial' => $serial,
            'tipo_equipo_id' => $tipoEquipo->id,
            'marca_id' => $marca->id,
            'propiedad' => 'gobernacion',
        ]);
    }

    public function test_no_permite_retirar_un_componente_de_otro_equipo(): void
    {
        $usuario = User::factory()->create();
        $equipoPropio = $this->crearEquipo('SN-TEST-0001');
        $equipoAjeno = $this->crearEquipo('SN-TEST-0002');

        $tipoComponente = TipoComponente::create(['nombre' => 'RAM']);
        $componenteAjeno = Componente::create([
            'equipo_id' => $equipoAjeno->id,
            'tipo_componente_id' => $tipoComponente->id,
            'fecha_instalacion' => now(),
        ]);

        Livewire::actingAs($usuario)
            ->test(ComponenteForm::class, ['equipo' => $equipoPropio])
            ->set('accion', 'quitar')
            ->set('componenteRetiradoId', $componenteAjeno->id)
            ->set('destinoRetirado', 'bodega')
            ->set('motivo', 'Prueba de seguridad')
            ->call('guardar')
            ->assertHasErrors(['componenteRetiradoId']);

        $this->assertNull($componenteAjeno->fresh()->fecha_retiro);
    }

    public function test_no_permite_retirar_un_componente_ya_retirado(): void
    {
        $usuario = User::factory()->create();
        $equipo = $this->crearEquipo('SN-TEST-0003');

        $tipoComponente = TipoComponente::create(['nombre' => 'Disco duro']);
        $componenteRetirado = Componente::create([
            'equipo_id' => $equipo->id,
            'tipo_componente_id' => $tipoComponente->id,
            'fecha_instalacion' => now()->subYear(),
            'fecha_retiro' => now()->subMonth(),
        ]);

        Livewire::actingAs($usuario)
            ->test(ComponenteForm::class, ['equipo' => $equipo])
            ->set('accion', 'quitar')
            ->set('componenteRetiradoId', $componenteRetirado->id)
            ->set('destinoRetirado', 'bodega')
            ->set('motivo', 'Prueba de seguridad')
            ->call('guardar')
            ->assertHasErrors(['componenteRetiradoId']);
    }

    public function test_permite_retirar_un_componente_propio_instalado(): void
    {
        $usuario = User::factory()->create();
        $equipo = $this->crearEquipo('SN-TEST-0004');

        $tipoComponente = TipoComponente::create(['nombre' => 'Fuente de poder']);
        $componente = Componente::create([
            'equipo_id' => $equipo->id,
            'tipo_componente_id' => $tipoComponente->id,
            'fecha_instalacion' => now()->subMonths(6),
        ]);

        Livewire::actingAs($usuario)
            ->test(ComponenteForm::class, ['equipo' => $equipo])
            ->set('accion', 'quitar')
            ->set('componenteRetiradoId', $componente->id)
            ->set('destinoRetirado', 'bodega')
            ->set('motivo', 'Componente dañado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNotNull($componente->fresh()->fecha_retiro);
        $this->assertDatabaseHas('eventos', [
            'equipo_id' => $equipo->id,
            'tipo' => 'cambio_componente',
            'usuario_id' => $usuario->id,
        ]);
    }
}
