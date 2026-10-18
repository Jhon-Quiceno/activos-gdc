<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: los 30 municipios de Córdoba (DIVIPOLA), para ubicar las sedes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->char('codigo_dane', 5)->unique();
            $table->string('nombre', 80)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
