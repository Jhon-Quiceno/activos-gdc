<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puestos_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_o_codigo');
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('restrict');
            $table->foreignId('piso_id')->constrained('pisos')->onDelete('restrict');
            $table->foreignId('dependencia_id')->nullable()->constrained('dependencias')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puestos_trabajo');
    }
};
