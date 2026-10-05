<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Marca;
use App\Models\MotivoBaja;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use Illuminate\Database\Seeder;

/**
 * Agrupa todos los catálogos base del sistema (datos maestros que no dependen de
 * nada más). Debe ejecutarse antes que cualquier otro seeder.
 */
class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $sedes = collect([
            'Palacio Naín',
            'Morindo',
            'Edificio Victoria Char',
            'Casa de Gobierno',
            'Sede Alcaldía Mayor',
            'Edificio San Jerónimo',
            'Sede Secretaría de Salud',
            'Sede Secretaría de Educación',
            'Centro Administrativo Municipal',
            'Edificio Antiguo Banco de la República',
            'Sede Archivo Central',
        ])->map(fn (string $nombre) => Sede::create(['nombre' => $nombre]));

        $pisos = collect(range(1, 8))
            ->map(fn (int $numero) => Piso::create(['numero' => $numero]));

        $dependencias = collect([
            'Despacho del Gobernador',
            'Secretaría General',
            'Secretaría de Educación',
            'Secretaría de Salud',
            'Secretaría de Hacienda',
            'Secretaría de Infraestructura',
            'Secretaría de Agricultura',
            'Secretaría del Interior',
            'Secretaría de Planeación',
            'Secretaría Jurídica',
            'Dirección TIC',
            'Dirección de Talento Humano',
            'Oficina de Control Interno',
            'Oficina Asesora de Comunicaciones',
            'Oficina de Contratación',
        ])->map(fn (string $nombre) => Dependencia::create(['nombre' => $nombre]));

        $tiposEquipo = collect([
            ['nombre' => 'PC de escritorio', 'familia' => 'computo'],
            ['nombre' => 'Portátil', 'familia' => 'computo'],
            ['nombre' => 'Monitor', 'familia' => 'video'],
            ['nombre' => 'Impresora', 'familia' => 'impresion'],
            ['nombre' => 'Escáner', 'familia' => 'digitalizacion'],
            ['nombre' => 'UPS', 'familia' => 'energia'],
            ['nombre' => 'Switch', 'familia' => 'conectividad'],
            ['nombre' => 'Access Point', 'familia' => 'conectividad'],
            ['nombre' => 'Servidor', 'familia' => 'computo'],
            ['nombre' => 'Video beam', 'familia' => 'proyeccion'],
            ['nombre' => 'Teléfono IP', 'familia' => 'conectividad'],
            ['nombre' => 'Otro', 'familia' => 'computo'],
        ])->map(fn (array $tipo) => TipoEquipo::create($tipo));

        $marcas = collect([
            'HP', 'Dell', 'Lenovo', 'Epson', 'APC', 'TP-Link',
            'Canon', 'Samsung', 'LG', 'Asus', 'Acer', 'Brother',
            'Cisco', 'Ubiquiti', 'Huawei',
        ])->map(fn (string $nombre) => Marca::create(['nombre' => $nombre]));

        collect([
            'Windows 7', 'Windows 8', 'Windows 10 Home', 'Windows 10 Pro',
            'Windows 11 Home', 'Windows 11 Pro', 'Linux', 'N/A',
        ])->each(fn (string $nombre) => SistemaOperativo::create(['nombre' => $nombre]));

        collect([
            'Obsolescencia', 'Daño irreparable', 'Pérdida', 'Robo', 'Donación/Reposición',
        ])->each(fn (string $nombre) => MotivoBaja::create(['nombre' => $nombre]));

        collect([
            ['nombre' => 'RAM', 'es_periferico' => false],
            ['nombre' => 'Disco', 'es_periferico' => false],
            ['nombre' => 'Procesador', 'es_periferico' => false],
            ['nombre' => 'Tarjeta de red', 'es_periferico' => false],
            ['nombre' => 'Tarjeta de video', 'es_periferico' => false],
            ['nombre' => 'Teclado', 'es_periferico' => true],
            ['nombre' => 'Mouse', 'es_periferico' => true],
            ['nombre' => 'Sonido', 'es_periferico' => true],
            ['nombre' => 'Cámara', 'es_periferico' => true],
        ])->each(fn (array $tipo) => TipoComponente::create($tipo));

        // Puestos de trabajo: catálogo de ubicaciones físicas concretas, combinando
        // sede + piso + dependencia, usado por equipos y asignaciones.
        for ($i = 1; $i <= 25; $i++) {
            PuestoTrabajo::create([
                'nombre_o_codigo' => 'PT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'sede_id' => $sedes->random()->id,
                'piso_id' => $pisos->random()->id,
                'dependencia_id' => $dependencias->random()->id,
            ]);
        }
    }
}
