<?php

use App\Livewire\Reportes\InventarioGeneral;
use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class); // si tu Pest.php ya lo aplica a Feature, borra esta línea

it('muestra el inventario general', function () {
    $equipo = Equipo::factory()->create();

    Livewire::test(InventarioGeneral::class)
        ->assertOk()
        ->assertSee($equipo->serial);
});

it('filtra por estado del ciclo de vida', function () {
    $a = Equipo::factory()->create(['estado_ciclo_vida' => 'En servicio']);
    $b = Equipo::factory()->create(['estado_ciclo_vida' => 'Dado de baja']);

    Livewire::test(InventarioGeneral::class)
        ->set('filtros.estado_ciclo_vida', 'Dado de baja')
        ->assertSee($b->serial)
        ->assertDontSee($a->serial);
});

it('enmascara la cédula', function () {
    $reporte = new class extends InventarioGeneral {
        public function probar(string $cedula): string
        {
            return $this->enmascararCedula($cedula);
        }
    };

    expect($reporte->probar('1067896226'))->toBe('****6226');
});