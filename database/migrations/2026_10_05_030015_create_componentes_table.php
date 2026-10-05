<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('tipo_componente_id')->constrained('tipos_componente')->onDelete('restrict');
            $table->string('capacidad_caracteristica')->nullable();
            $table->string('marca')->nullable();
            $table->string('serial')->nullable();
            $table->string('estado')->nullable();
            $table->date('fecha_instalacion')->nullable();
            // Null = el componente sigue instalado actualmente.
            $table->date('fecha_retiro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('componentes');
    }
};
