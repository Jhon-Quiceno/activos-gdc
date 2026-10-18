<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: levantamiento de activos por dependencia y proceso, cerrado con
 * el acta de identificación y aceptación firmada (GT-M4 4.1 y 4.1.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levantamientos_activos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->foreignId('dependencia_id')->constrained('dependencias')->restrictOnDelete();
            $table->foreignId('proceso_id')->nullable()->constrained('procesos')->restrictOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_aceptacion')->nullable();
            // Director o líder del proceso, y custodio (GT-M4 4.1.1).
            $table->foreignId('propietario_persona_id')->nullable()->constrained('personas')->restrictOnDelete();
            $table->foreignId('custodio_persona_id')->nullable()->constrained('personas')->restrictOnDelete();
            $table->enum('estado', ['en_curso', 'pendiente_de_firma', 'aceptado'])->default('en_curso');
            $table->string('acta_path')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levantamientos_activos');
    }
};
