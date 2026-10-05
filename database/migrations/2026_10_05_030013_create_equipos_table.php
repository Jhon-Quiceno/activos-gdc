<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique();
            // Formato esperado: I1-###### (correlativo de activo).
            $table->string('codigo_activo')->nullable()->unique();
            // Identificador permanente e independiente del serial/codigo (RN-19): una vez
            // generado no cambia, aunque el equipo cambie de serial o se reimprima el QR.
            $table->uuid('qr_uuid')->unique();
            $table->foreignId('tipo_equipo_id')->constrained('tipos_equipo')->onDelete('restrict');
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('restrict');
            $table->string('modelo')->nullable();
            $table->enum('propiedad', ['gobernacion', 'tercero']);
            $table->string('propietario_tercero')->nullable();
            $table->string('estado_funcionamiento')->nullable();
            $table->enum('estado_ciclo_vida', ['en_servicio', 'sin_asignar', 'dado_de_baja'])->default('en_servicio');
            $table->enum('verificacion', ['verificado', 'pendiente_de_verificar'])->default('pendiente_de_verificar');
            $table->foreignId('puesto_trabajo_id')->nullable()->constrained('puestos_trabajo')->onDelete('restrict');
            $table->foreignId('importacion_id')->nullable()->constrained('importaciones')->onDelete('set null');
            $table->integer('fila_origen_importacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
