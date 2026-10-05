<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etiquetas_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('restrict');
            $table->dateTime('fecha_impresion');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->enum('motivo', ['primera_impresion', 'reposicion']);
            $table->string('lote')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etiquetas_qr');
    }
};
