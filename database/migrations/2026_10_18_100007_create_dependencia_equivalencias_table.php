<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: cada variante de texto con que llega una dependencia en un
 * Excel («DIRECION DE SISTEMAS», «Sec Educación») y la dependencia oficial
 * a la que corresponde. Es la tabla editable de RF-39.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencia_equivalencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependencia_id')->constrained('dependencias')->cascadeOnDelete();
            $table->string('texto', 200)->unique();
            $table->string('origen', 60)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencia_equivalencias');
    }
};
