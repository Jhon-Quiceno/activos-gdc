<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('restrict');
            $table->enum('tipo', [
                'alta',
                'traslado_responsable',
                'cambio_componente',
                'diagnostico',
                'baja',
                'actualizacion_datos',
                'anulacion_aclaracion',
            ]);
            $table->dateTime('fecha');
            $table->text('descripcion')->nullable();
            // Autor del evento (RF-11). Nunca se reasigna despues de creado.
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->enum('estado_firma', ['no_aplica', 'pendiente_de_firma', 'completo'])->default('no_aplica');
            // Autorreferencia para anulaciones/aclaraciones (tipo = anulacion_aclaracion):
            // apunta al evento que queda anulado por este.
            $table->foreignId('evento_anulado_id')->nullable()->constrained('eventos')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
