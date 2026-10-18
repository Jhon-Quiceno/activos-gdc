<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: valoración del activo según el Manual GT-M4 (4.2.1 a 4.2.6).
 * Es histórica: una reclasificación crea una fila nueva con un evento
 * `valoracion` y deja la anterior como no vigente. `total`, `importancia`
 * y `requiere_gestion_riesgo` los calcula App\Models\ValoracionActivo:
 * nunca se digitan (RN-22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valoraciones_activo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('evento_id')->nullable()->unique()->constrained('eventos')->restrictOnDelete();
            $table->foreignId('levantamiento_id')->nullable()->constrained('levantamientos_activos')->nullOnDelete();
            $table->enum('contiene_datos_personales', ['si', 'no', 'no_aplica']);
            $table->enum('clasificacion_datos', [
                'publico', 'privado', 'semiprivado', 'sensible', 'reservado', 'clasificado', 'no_aplica',
            ])->default('no_aplica');
            $table->enum('infraestructura_critica', ['si', 'no', 'no_aplica']);
            $table->enum('continuidad_negocio', ['si', 'no', 'no_aplica']);
            $table->string('propietario_cargo', 120);
            $table->string('custodio_cargo', 120);
            $table->enum('tipo_ubicacion', ['fisico', 'electronico', 'fisico_electronico'])->default('fisico');
            $table->string('tiempo_retencion', 40)->nullable();
            $table->unsignedTinyInteger('confidencialidad');
            $table->unsignedTinyInteger('integridad');
            $table->unsignedTinyInteger('disponibilidad');
            $table->unsignedTinyInteger('total');
            $table->enum('importancia', ['baja', 'media', 'alta', 'muy_alta']);
            $table->boolean('requiere_gestion_riesgo');
            $table->string('controles_monitoreo', 200)->nullable();
            $table->boolean('vigente')->default(true);
            $table->date('fecha');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['equipo_id', 'vigente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valoraciones_activo');
    }
};
