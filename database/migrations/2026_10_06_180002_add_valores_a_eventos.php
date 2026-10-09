<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            // RF-12: al registrar un evento por cambio de datos hay que dejar el valor
            // anterior y el nuevo. Se guarda como JSON estructurado:
            // ['antes' => [...], 'despues' => [...]]. Se pasa igual que cualquier otro
            // campo extra de HistorialService::registrar(), ej.:
            // registrar(..., datos: ['valores' => ['antes' => $antes, 'despues' => $nuevos]]).
            $table->json('valores')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('valores');
        });
    }
};
