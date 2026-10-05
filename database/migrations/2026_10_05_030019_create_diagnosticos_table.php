<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            // Extiende Evento 1 a 1 (el evento padre debe ser de tipo diagnostico).
            $table->foreignId('evento_id')->unique()->constrained('eventos')->onDelete('cascade');
            $table->text('estado_encontrado');
            $table->text('causa')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->boolean('es_baja')->default(false);
            $table->foreignId('motivo_baja_id')->nullable()->constrained('motivos_baja')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
    }
};
