<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F3-2 (RF-06): fotografías del equipo. No es un `documento` porque los
 * documentos cuelgan de un evento (`documentos.evento_id` es obligatorio) y la
 * foto del equipo no (docs/base-de-datos.md, sección 10.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos_equipo', function (Blueprint $table) {
            $table->id();
            // restrict: la foto es parte de la hoja de vida y no se borra con el
            // equipo (los equipos tampoco se borran).
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('restrict');
            $table->string('archivo_path', 255);
            $table->string('descripcion', 160)->nullable();
            $table->date('tomada_en')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->foreignId('subida_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('equipo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_equipo');
    }
};
