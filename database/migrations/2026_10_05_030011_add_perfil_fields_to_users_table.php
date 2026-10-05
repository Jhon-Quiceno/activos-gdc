<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('perfil', ['administrador', 'usuario'])->default('usuario')->after('password');
            $table->boolean('activo')->default(true)->after('perfil');
            $table->boolean('debe_cambiar_contrasena')->default(true)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['perfil', 'activo', 'debe_cambiar_contrasena']);
        });
    }
};
