<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: las dependencias pasan de lista plana a árbol (Despacho →
 * Secretaría/Oficina/DAP → Dirección → Área) con los nombres del
 * Decreto 609 de 2023. Una dependencia nunca se borra: se desactiva (RN-20).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dependencias', function (Blueprint $table) {
            $table->dropUnique(['nombre']);

            $table->foreignId('padre_id')->nullable()->after('id')->constrained('dependencias')->restrictOnDelete();
            $table->enum('tipo', ['despacho', 'secretaria', 'departamento_administrativo', 'oficina', 'direccion', 'area'])
                ->default('area')->after('padre_id');
            $table->string('nombre_corto', 60)->nullable()->after('nombre');
            $table->string('sigla', 20)->nullable()->after('nombre_corto');
            $table->foreignId('proceso_id')->nullable()->after('sigla')->constrained('procesos')->restrictOnDelete();
            $table->enum('planta', ['central', 'educacion'])->default('central')->after('proceso_id');
            $table->string('acto_administrativo', 60)->nullable()->after('planta');
            $table->boolean('activa')->default(true)->after('acto_administrativo');

            $table->unique(['padre_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::table('dependencias', function (Blueprint $table) {
            // La FK de padre_id usa el índice único: se borra primero.
            $table->dropForeign(['padre_id']);
            $table->dropUnique(['padre_id', 'nombre']);
            $table->dropColumn('padre_id');
            $table->dropConstrainedForeignId('proceso_id');
            $table->dropColumn(['tipo', 'nombre_corto', 'sigla', 'planta', 'acto_administrativo', 'activa']);
            $table->unique('nombre');
        });
    }
};
