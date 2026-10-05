<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // Dato personal protegido por la Ley 1581 de 2012 (Colombia). No se enmascara en BD
            // a propósito: el enmascaramiento/control de acceso es responsabilidad de la capa
            // de presentación (vistas/políticas), no de este esquema.
            $table->string('cedula')->nullable();
            $table->string('cargo')->nullable();
            $table->foreignId('dependencia_id')->nullable()->constrained('dependencias')->onDelete('restrict');
            $table->enum('tipo_vinculacion', ['planta', 'contratista'])->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
