<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F3-3 (RF-13): tabla de catálogo para los tipos de evento de la hoja de vida.
 * `eventos.tipo` pasa de enum a varchar(40) con llave foránea a `tipos_evento.clave`,
 * conservando exactamente las mismas 8 claves para no tocar ningún evento existente.
 *
 * Los 8 tipos del sistema se siembran aquí (no en CatalogosSeeder) para que existan
 * en cualquier entorno donde corran las migraciones, incluidos los tests que no pasan
 * por el seeder: HistorialService::registrar() valida contra esta tabla.
 */
return new class extends Migration
{
    private const TIPOS_SISTEMA = [
        ['clave' => 'alta', 'nombre' => 'Alta', 'descripcion' => 'Alta del equipo en la hoja de vida.'],
        ['clave' => 'traslado_responsable', 'nombre' => 'Traslado de responsable', 'descripcion' => 'Cambio de responsable, ubicación o puesto de trabajo del equipo.'],
        ['clave' => 'cambio_componente', 'nombre' => 'Cambio de componente', 'descripcion' => 'Agregar, cambiar o quitar un componente del equipo.'],
        ['clave' => 'diagnostico', 'nombre' => 'Diagnóstico', 'descripcion' => 'Diagnóstico técnico del equipo.'],
        ['clave' => 'valoracion', 'nombre' => 'Valoración del activo', 'descripcion' => 'Reclasificación CID del activo (GT-M4).'],
        ['clave' => 'baja', 'nombre' => 'Baja', 'descripcion' => 'Baja definitiva del equipo.'],
        ['clave' => 'actualizacion_datos', 'nombre' => 'Actualización de datos', 'descripcion' => 'Corrección o actualización de los datos del equipo.'],
        ['clave' => 'anulacion_aclaracion', 'nombre' => 'Anulación / aclaración', 'descripcion' => 'Anula o aclara un evento anterior de la hoja de vida.'],
    ];

    public function up(): void
    {
        Schema::create('tipos_evento', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre', 80);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('requiere_firma')->default(false);
            $table->boolean('es_sistema')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $ahora = now();
        DB::table('tipos_evento')->insert(array_map(
            fn (array $tipo) => $tipo + [
                // Los 8 del sistema: su clave no se edita ni se desactiva, tienen
                // efectos propios (traslado, baja, valoración...) y por ahora ninguno
                // exige firma por sí mismo: cada módulo ya decide su estado_firma
                // (traslado, baja, diagnóstico con formato) con su propia lógica.
                'requiere_firma' => false,
                'es_sistema' => true,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            self::TIPOS_SISTEMA
        ));

        Schema::table('eventos', function (Blueprint $table) {
            $table->string('tipo', 40)->change();
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->foreign('tipo')->references('clave')->on('tipos_evento')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropForeign(['tipo']);
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->enum('tipo', [
                'alta',
                'traslado_responsable',
                'cambio_componente',
                'diagnostico',
                'valoracion',
                'baja',
                'actualizacion_datos',
                'anulacion_aclaracion',
            ])->change();
        });

        Schema::dropIfExists('tipos_evento');
    }
};
