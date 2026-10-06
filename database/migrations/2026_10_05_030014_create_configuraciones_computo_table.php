<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_computo', function (Blueprint $table) {
            $table->id();
            // Relacion 1 a 1: solo aplica a equipos cuyo tipo_equipo pertenece a la familia computo.
            $table->foreignId('equipo_id')->unique()->constrained('equipos')->onDelete('cascade');
            $table->foreignId('sistema_operativo_id')->nullable()->constrained('sistemas_operativos')->onDelete('restrict');
            $table->string('licencia')->nullable();
            $table->boolean('tiene_antivirus')->default(false);
            $table->string('antivirus_producto')->nullable();
            $table->string('nombre_red')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_computo');
    }
};
