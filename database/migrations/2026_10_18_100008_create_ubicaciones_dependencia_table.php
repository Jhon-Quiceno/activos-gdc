<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: en qué piso (y por lo tanto en qué sede) funciona cada
 * dependencia, según el Programa Anual de Mantenimiento 2026. Sirve para
 * proponer la ubicación en los traslados; no prohíbe otras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones_dependencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependencia_id')->constrained('dependencias')->cascadeOnDelete();
            $table->foreignId('piso_id')->constrained('pisos')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['dependencia_id', 'piso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_dependencia');
    }
};
