<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: sedes reales con dirección y municipio, y pisos propios de
 * cada sede (en v3 los pisos 1 a 8 eran globales).
 *
 * Migración en dos tiempos: `pisos.sede_id` y `sedes.municipio_id` quedan
 * opcionales mientras los bloques se adaptan; la migración de cierre del
 * día 2 los vuelve obligatorios (ver docs/base-de-datos.md §6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->string('direccion', 160)->nullable()->after('nombre');
            $table->string('barrio', 80)->nullable()->after('direccion');
            $table->foreignId('municipio_id')->nullable()->after('barrio')->constrained('municipios')->restrictOnDelete();
            $table->enum('tipo', ['administrativa', 'salud', 'social', 'cultural', 'institucion_educativa'])
                ->default('administrativa')->after('municipio_id');
            $table->enum('tenencia', ['propia', 'arrendada', 'comodato', 'por_confirmar'])
                ->default('por_confirmar')->after('tipo');
            // Casa Refugio: su dirección nunca sale en listados, reportes ni etiquetas (RN-28).
            $table->boolean('direccion_reservada')->default(false)->after('tenencia');
            $table->boolean('activa')->default(true)->after('direccion_reservada');
        });

        Schema::table('pisos', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->foreignId('sede_id')->nullable()->after('id')->constrained('sedes')->restrictOnDelete();
            $table->string('nombre', 60)->nullable()->after('numero');

            $table->unique(['sede_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::table('pisos', function (Blueprint $table) {
            // La FK de sede_id usa el índice único: se borra primero.
            $table->dropForeign(['sede_id']);
            $table->dropUnique(['sede_id', 'numero']);
            $table->dropColumn('sede_id');
            $table->dropColumn('nombre');
            $table->unique('numero');
        });

        Schema::table('sedes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('municipio_id');
            $table->dropColumn(['direccion', 'barrio', 'tipo', 'tenencia', 'direccion_reservada', 'activa']);
        });
    }
};
