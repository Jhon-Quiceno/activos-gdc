<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->enum('familia', [
                'computo',
                'video',
                'impresion',
                'conectividad',
                'digitalizacion',
                'energia',
                'proyeccion',
            ]);
            $table->json('campos_aplicables')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_equipo');
    }
};
