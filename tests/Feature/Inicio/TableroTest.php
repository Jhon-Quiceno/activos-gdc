<?php

use App\Models\Equipo;
use App\Models\Marca;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\ValoracionActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tipo = TipoEquipo::forceCreate(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'tipo_activo' => 'hardware']);
    Marca::forceCreate(['nombre' => 'HP']);
});

it('el tablero carga con las secciones de importancia y por secretaría (RF-36)', function () {
    $this->actingAs(User::factory()->create(['debe_cambiar_contrasena' => false]))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Importancia de los activos')
        ->assertSee('Sin valoración')
        ->assertSee('Equipos por secretaría');
});

it('cuenta los equipos de hardware sin valoración vigente', function () {
    $usuario = User::factory()->create(['debe_cambiar_contrasena' => false]);

    $valorado = Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]);
    ValoracionActivo::registrar($valorado, $usuario, [
        'contiene_datos_personales' => 'no', 'clasificacion_datos' => 'no_aplica',
        'infraestructura_critica' => 'no', 'continuidad_negocio' => 'no',
        'propietario_cargo' => 'Director TIC', 'custodio_cargo' => 'Técnico',
        'confidencialidad' => 2, 'integridad' => 2, 'disponibilidad' => 2,
    ]);
    Equipo::factory()->create(['tipo_equipo_id' => $this->tipo->id]); // sin valorar

    $html = $this->actingAs($usuario)->get('/dashboard')->assertOk()->getContent();

    // Una valoración media y un equipo sin valoración.
    expect($html)->toContain('Media')->toContain('Sin valoración');
});
