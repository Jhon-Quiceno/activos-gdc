<?php

namespace Tests\Feature\Movimientos;

use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HistorialService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Http\UploadedFile;

/**
 * Datos de prueba del bloque Movimientos. Parte siempre de los catálogos
 * oficiales (CatalogosSeeder), igual que `migrate:fresh --seed`.
 */
class Apoyo
{
    public static function catalogos(): void
    {
        (new CatalogosSeeder)->run();
    }

    public static function usuario(): User
    {
        return User::factory()->create();
    }

    public static function persona(array $atributos = []): Persona
    {
        return Persona::factory()->create(array_merge([
            'dependencia_id' => Dependencia::query()->value('id'),
            'tipo_vinculacion' => 'planta',
            'activo' => true,
        ], $atributos));
    }

    /**
     * Equipo de cómputo de la Gobernación con evento de alta, asignación
     * abierta y dos componentes, como los que deja el seeder demo. Con
     * $conResponsable = false queda «Sin asignar» (bodega).
     */
    public static function equipo(?Persona $persona = null, bool $conResponsable = true, array $atributos = []): Equipo
    {
        $persona = $conResponsable ? ($persona ?? self::persona()) : null;

        $equipo = Equipo::factory()->create(array_merge([
            'tipo_equipo_id' => TipoEquipo::query()->where('familia', 'computo')->value('id'),
            'propiedad' => 'gobernacion',
            'propietario_tercero' => null,
            'estado_ciclo_vida' => $persona ? 'en_servicio' : 'sin_asignar',
        ], $atributos));

        app(HistorialService::class)->registrar($equipo, 'alta', self::usuario(), [
            'fecha' => now()->subMonths(6),
        ], 'Alta de prueba');

        Asignacion::create([
            'equipo_id' => $equipo->id,
            'persona_id' => $persona?->id,
            'sede_id' => Sede::query()->value('id'),
            'piso_id' => Piso::query()->value('id'),
            'dependencia_id' => $persona?->dependencia_id,
            'fecha_inicio' => now()->subMonths(6)->toDateString(),
        ]);

        foreach (['RAM' => '8GB', 'Disco' => '256GB SSD'] as $tipo => $caracteristica) {
            Componente::create([
                'equipo_id' => $equipo->id,
                'tipo_componente_id' => TipoComponente::query()->where('nombre', $tipo)->value('id'),
                'capacidad_caracteristica' => $caracteristica,
                'serial' => 'CMP-'.$tipo.'-'.$equipo->id,
                'estado' => 'bueno',
                'fecha_instalacion' => now()->subMonths(6),
            ]);
        }

        return $equipo->fresh();
    }

    public static function pdfFirmado(string $nombre = 'firmado.pdf', int $kb = 120): UploadedFile
    {
        return UploadedFile::fake()->create($nombre, $kb, 'application/pdf');
    }
}
