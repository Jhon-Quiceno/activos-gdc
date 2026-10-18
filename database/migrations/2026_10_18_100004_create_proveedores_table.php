<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: quien vende, dona o presta equipos. También es el dueño de
 * los equipos de terceros (reemplaza a equipos.propietario_tercero).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20)->nullable()->unique();
            $table->string('razon_social', 160);
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
