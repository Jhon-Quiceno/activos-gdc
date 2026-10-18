<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: nomenclatura oficial de empleos (Decreto 607 de 2023). El
 * primer dígito del código indica el nivel (art. 16). Los contratistas y
 * pasantes usan un cargo sin código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->char('codigo', 3)->nullable();
            $table->string('denominacion', 120);
            $table->enum('nivel', ['directivo', 'asesor', 'profesional', 'tecnico', 'asistencial']);
            $table->timestamps();

            $table->unique(['codigo', 'denominacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
