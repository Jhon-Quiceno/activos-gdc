<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: procesos del SIG (hoja «Datos» del formato FOR.01). Cada
 * dependencia y cada equipo se asocian a un proceso (GT-M4 4.2.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->enum('tipo', ['estrategico', 'misional', 'apoyo', 'evaluacion_control']);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procesos');
    }
};
