<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('restrict');
            // Null = equipo sin responsable (bodega / sin asignar).
            $table->foreignId('persona_id')->nullable()->constrained('personas')->onDelete('restrict');
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('restrict');
            $table->foreignId('piso_id')->constrained('pisos')->onDelete('restrict');
            $table->foreignId('dependencia_id')->nullable()->constrained('dependencias')->onDelete('restrict');
            $table->date('fecha_inicio');
            // Null = asignacion abierta/actual.
            $table->date('fecha_fin')->nullable();
            $table->foreignId('evento_origen_id')->nullable()->constrained('eventos')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
