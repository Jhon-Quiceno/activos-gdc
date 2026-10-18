<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: la asignación puede apuntar a un puesto de trabajo, el usuario
 * del sistema se enlaza con su persona y la importación indica qué se cargó.
 *
 * Migración en dos tiempos: `asignaciones.sede_id` y `puestos_trabajo.sede_id`
 * siguen existiendo (App\Models\Asignacion copia la sede desde el piso); la
 * migración de cierre del día 2 los borra, porque el piso ya define la sede.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->foreignId('puesto_trabajo_id')->nullable()->after('dependencia_id')->constrained('puestos_trabajo')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('persona_id')->nullable()->unique()->after('email')->constrained('personas')->nullOnDelete();
        });

        Schema::table('importaciones', function (Blueprint $table) {
            $table->enum('tipo', ['equipos', 'personas', 'valoraciones'])->default('equipos')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('persona_id');
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('puesto_trabajo_id');
        });
    }
};
