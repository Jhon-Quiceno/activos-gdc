<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Orden obligatorio: primero los catálogos (de los que todo lo demás depende
     * por FK), luego los usuarios demo, y por último los equipos de prueba (que
     * dependen de catálogos y usuarios).
     */
    public function run(): void
    {
        $this->call([
            CatalogosSeeder::class,
            UsersSeeder::class,
            EquiposDemoSeeder::class,
        ]);
    }
}
