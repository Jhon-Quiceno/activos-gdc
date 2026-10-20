<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La carga de datos de adquisición desde Almacén (§12 del análisis v4.1)
 * también queda registrada en `importaciones`, con su propio tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->enum('tipo', ['equipos', 'personas', 'valoraciones', 'adquisicion'])->default('equipos')->change();
        });
    }

    public function down(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->enum('tipo', ['equipos', 'personas', 'valoraciones'])->default('equipos')->change();
        });
    }
};
