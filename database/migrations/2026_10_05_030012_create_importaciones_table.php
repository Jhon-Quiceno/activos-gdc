<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');
            $table->dateTime('fecha');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->unsignedInteger('filas');
            $table->json('errores')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
