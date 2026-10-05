<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrador TIC',
            'email' => 'admin@gobernaciondecordoba.gov.co',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'perfil' => 'administrador',
            'activo' => true,
            'debe_cambiar_contrasena' => true,
        ]);

        User::create([
            'name' => 'Soporte Dirección TIC',
            'email' => 'soporte.tic@gobernaciondecordoba.gov.co',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'perfil' => 'administrador',
            'activo' => true,
            'debe_cambiar_contrasena' => true,
        ]);

        User::create([
            'name' => 'Usuario de Inventario',
            'email' => 'inventario@gobernaciondecordoba.gov.co',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'perfil' => 'usuario',
            'activo' => true,
            'debe_cambiar_contrasena' => true,
        ]);

        User::create([
            'name' => 'Usuario Consulta',
            'email' => 'consulta@gobernaciondecordoba.gov.co',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'perfil' => 'usuario',
            'activo' => true,
            'debe_cambiar_contrasena' => true,
        ]);
    }
}
