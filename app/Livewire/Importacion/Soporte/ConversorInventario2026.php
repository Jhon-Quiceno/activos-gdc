<?php

namespace App\Livewire\Importacion\Soporte;

use App\Livewire\Importacion\InventarioExcel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Convierte el Excel del inventario 2026 (exportación de Microsoft Forms, una
 * fila por puesto con varios dispositivos) a filas de la plantilla oficial de
 * carga (una fila por equipo). Ese Excel ya no define el modelo (análisis v4,
 * §12): solo se traduce a la plantilla y se carga por el flujo normal.
 *
 * Correspondencia de columnas: dispositivo de procesamiento, video, impresión,
 * conectividad, digitalización y otros → un equipo cada uno, con su marca,
 * modelo, código y serial; sede, piso, dependencia, responsable, sistema
 * operativo y antivirus son de la fila y se repiten en cada equipo.
 *
 * Detecta las cabeceras reales del formulario (preguntas completas) y las
 * cortas de los ejemplos («PC», «Monitor»…). Las filas marcadas como equipo
 * personal se excluyen (RN-01).
 */
final class ConversorInventario2026
{
    /** Categoría del formulario => [palabras de su encabezado, tipo de equipo por defecto]. */
    private const CATEGORIAS = [
        'pc' => [['pc', 'computador', 'equipo principal', 'desktop', 'portatil', 'laptop', 'procesamiento'], 'PC de escritorio'],
        'monitor' => [['monitor', 'pantalla', 'video'], 'Monitor'],
        'impresora' => [['impresora', 'printer', 'impresion'], 'Impresora'],
        'conectividad' => [['conectividad'], 'Switch'],
        'escaner' => [['escaner', 'scanner', 'digitalizacion'], 'Escáner'],
        'otro' => [['otro', 'otros'], 'Otro'],
        'ups' => [['ups'], 'UPS'],
    ];

    /** Palabras de un encabezado que indican que es un dato del dispositivo, no su presencia. */
    private const MODIFICADORES = ['codigo', 'activo', 'marca', 'modelo', 'serial', 'estado', 'sistema', 'version', 'antivirus'];

    /** Tipo escrito en el formulario → tipo del catálogo, cuando el texto no coincide tal cual. */
    private const TIPOS = [
        'pc all in one' => 'All in One',
        'all in one' => 'All in One',
        'portatil' => 'Portátil',
        'laptop' => 'Portátil',
        'pc' => 'PC de escritorio',
        'computador' => 'PC de escritorio',
        'computador de escritorio' => 'PC de escritorio',
        'monitor' => 'Monitor',
        'pantalla' => 'Monitor',
        'impresora' => 'Impresora',
        'multifuncional' => 'Multifuncional',
        'escaner' => 'Escáner',
        'scanner' => 'Escáner',
        'switch' => 'Switch',
        'router' => 'Router',
        'access point' => 'Access Point',
        'video beam' => 'Video beam',
        'videobeam' => 'Video beam',
        'ups' => 'UPS',
        'estabilizadores y ups' => 'UPS',
        'pc desktop' => 'PC de escritorio',
        'pc portatil' => 'Portátil',
        'servidor tipo torre' => 'Servidor físico',
    ];

    /**
     * Sedes del inventario 2026 que no se llaman igual que en el catálogo. Las
     * oficinas se ubican con el PAMP 2026 (Rentas y Cobro Coactivo en Places
     * Mall, Tránsito en el Victoria Char). «Malaria» y «Archivo Histórico» no
     * están en ninguna fuente oficial: quedan con error para que se decidan.
     */
    private const SEDES = [
        'rentas' => 'Places Mall',
        'cobro coactivo' => 'Places Mall',
        'secretaria de transito' => 'Edificio Victoria Char',
    ];

    /**
     * @return array{filas: array<int, array<string, ?string>>, excluidas: int, leidas: int}
     */
    public static function convertir(string $ruta): array
    {
        $hoja = Excel::toArray(new InventarioExcel, $ruta)[0] ?? [];
        if ($hoja === []) {
            return ['filas' => [], 'excluidas' => 0, 'leidas' => 0];
        }

        $encabezados = array_map(fn ($valor, $i) => LectorHoja::normalizar($valor) ?: 'columna '.$i, $hoja[0], array_keys($hoja[0]));
        $filas = [];
        $excluidas = 0;
        $leidas = 0;

        foreach (array_slice($hoja, 1) as $indice => $valores) {
            $fila = [];
            foreach ($encabezados as $i => $clave) {
                $fila[$clave] = Normalizar::texto(isset($valores[$i]) ? (string) $valores[$i] : null);
            }

            if (array_filter($fila) === []) {
                continue;
            }

            $leidas++;
            $numeroOrigen = $indice + 2;

            if (self::esPersonal($fila)) {
                $excluidas++;

                continue;
            }

            foreach (self::equiposDeFila($fila) as $equipo) {
                $filas[] = $equipo + self::datosDeFila($fila) + ['observaciones' => "Inventario 2026, fila {$numeroOrigen}."];
            }
        }

        return ['filas' => self::depurarIdentificadores($filas), 'excluidas' => $excluidas, 'leidas' => $leidas];
    }

    /**
     * Arreglos de identificación que solo tienen sentido en este Excel:
     * - en la columna de código a veces se escribió el serial («VNB4513213»):
     *   si no hay serial, pasa a serial;
     * - un «serial» que se repite en varios equipos («M27527-161», 38 veces) es
     *   un número de parte, no un serial: se deja en observaciones y el equipo
     *   queda con serial provisional y por verificar, en vez de bloquear la fila.
     */
    private static function depurarIdentificadores(array $filas): array
    {
        foreach ($filas as &$fila) {
            $codigo = $fila['codigo_activo'];
            if ($codigo !== null && ! Normalizar::pareceCodigo($codigo) && preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9\- ]{6,}$/i', $codigo)) {
                $fila['serial'] ??= $codigo;
                $fila['codigo_activo'] = null;
            }
        }
        unset($fila);

        $conteo = array_count_values(array_filter(array_map(fn ($f) => Normalizar::serial($f['serial']), $filas)));

        foreach ($filas as &$fila) {
            $serial = Normalizar::serial($fila['serial']);
            if ($serial !== null && $conteo[$serial] > 1) {
                $fila['observaciones'] .= " Serial repetido en el inventario: {$fila['serial']}.";
                $fila['serial'] = null;
            }
        }
        unset($fila);

        return $filas;
    }

    /**
     * Los dispositivos de la fila, cada uno como fila de plantilla.
     *
     * @return array<int, array<string, ?string>>
     */
    private static function equiposDeFila(array $fila): array
    {
        $equipos = [];
        foreach (self::CATEGORIAS as $categoria => [$palabras, $tipoPorDefecto]) {
            $presencia = self::presencia($fila, $palabras);
            $codigo = self::modificador($fila, $palabras, ['codigo', 'activo']);
            $serial = self::modificador($fila, $palabras, ['serial']);
            $marca = self::modificador($fila, $palabras, ['marca']);
            $modelo = self::modificador($fila, $palabras, ['modelo']);

            // El PC cuenta si trae cualquier dato propio; el resto, solo si su columna de presencia trae algo.
            if ($categoria === 'pc' ? $presencia === null && $codigo === null && $serial === null && $marca === null && $modelo === null : $presencia === null) {
                continue;
            }

            $equipos[] = [
                'tipo_equipo' => self::tipo($presencia, $tipoPorDefecto),
                'marca' => $marca,
                'modelo' => $modelo,
                'serial' => $serial,
                'codigo_activo' => $codigo,
                'estado_funcionamiento' => self::estado(self::modificador($fila, $palabras, ['estado']) ?? self::conTokens($fila, ['estado', 'funcionamiento'])),
            ];
        }

        return $equipos;
    }

    /** Datos que comparten todos los equipos de la fila. */
    private static function datosDeFila(array $fila): array
    {
        $tercero = Str::contains(Str::lower(implode(' ', array_filter($fila))), ['tercero', 'externo', 'comodato']);
        $antivirus = self::conTokens($fila, ['antivirus']);
        $piso = self::conTokens($fila, ['piso']);

        $sede = self::conTokens($fila, ['sede']);
        $documento = self::conTokens($fila, ['cedula']);

        return [
            'sede' => $sede !== null ? (self::SEDES[LectorHoja::normalizar($sede)] ?? $sede) : null,
            // «Piso 3» → «3»; un nombre («Local 44») se deja igual.
            'piso' => $piso !== null && preg_match('/(\d+)/', $piso, $m) ? $m[1] : $piso,
            'dependencia' => self::conTokens($fila, ['dependencia']),
            'propiedad' => $tercero ? 'Tercero' : 'Gobernación',
            'propietario_tercero' => $tercero ? self::conTokens($fila, ['propietario']) : null,
            'figura_tercero' => $tercero ? 'Comodato' : null,
            // Un texto sin números («Funcionario no estaba») no es un documento.
            'responsable_documento' => $documento !== null && preg_match('/\d{5,}/', preg_replace('/[\s.,]/', '', $documento)) ? $documento : null,
            'responsable_nombres' => self::conTokens($fila, ['nombre', 'responsable']),
            'responsable_vinculacion' => self::vinculacion(self::conTokens($fila, ['vinculacion'])),
            'sistema_operativo' => self::sistemaOperativo(self::conTokens($fila, ['sistema', 'operativo'])),
            'antivirus' => $antivirus,
            'forma_ingreso' => 'Carga inicial',
        ];
    }

    private static function presencia(array $fila, array $palabras): ?string
    {
        foreach ($fila as $clave => $valor) {
            if ($valor !== null && self::contieneAlguna($clave, $palabras) && ! self::contieneAlguna($clave, self::MODIFICADORES)) {
                return $valor;
            }
        }

        return null;
    }

    private static function modificador(array $fila, array $palabras, array $modificador): ?string
    {
        foreach ($fila as $clave => $valor) {
            if ($valor !== null && self::contieneAlguna($clave, $palabras) && self::contieneAlguna($clave, $modificador)) {
                return $valor;
            }
        }

        return null;
    }

    private static function conTokens(array $fila, array $tokens): ?string
    {
        foreach ($fila as $clave => $valor) {
            if ($valor !== null && collect($tokens)->every(fn ($t) => str_contains($clave, $t))) {
                return $valor;
            }
        }

        return null;
    }

    private static function contieneAlguna(string $clave, array $palabras): bool
    {
        return collect($palabras)->contains(fn ($p) => str_contains($clave, $p));
    }

    private static function tipo(?string $escrito, string $porDefecto): string
    {
        if ($escrito === null) {
            return $porDefecto;
        }

        $clave = LectorHoja::normalizar($escrito);

        return self::TIPOS[$clave] ?? match (true) {
            str_starts_with($clave, 'impresora') => 'Impresora',
            str_contains($clave, 'portatil') || str_contains($clave, 'laptop') => 'Portátil',
            str_contains($clave, 'all in one') || str_contains($clave, 'todo en uno') => 'All in One',
            str_contains($clave, 'servidor') => 'Servidor físico',
            str_contains($clave, 'desktop') || str_contains($clave, 'escritorio') => 'PC de escritorio',
            default => $escrito,
        };
    }

    /**
     * «W10», «Windows 10pro», «Wwindows 10» → nombre del catálogo. Sin edición
     * escrita se toma Pro, la que usa la Gobernación en sus equipos. Las
     * respuestas que no son un sistema («No estaba funcionario») quedan vacías.
     */
    private static function sistemaOperativo(?string $texto): ?string
    {
        $clave = str_replace(' ', '', LectorHoja::normalizar($texto));

        if (in_array($clave, ['linux', 'ubuntu'], true)) {
            return 'Linux';
        }

        // «windows10pro», «wwindows10», «w10», «11»: una o más «w», «in» o «indows» opcional y la versión.
        if (! preg_match('/^(?:w+(?:in(?:dows)?)?)?(7|8|10|11)(.*)$/', $clave, $m)) {
            return null;
        }

        [$version, $resto] = [$m[1], $m[2]];

        return match ($version) {
            '7' => 'Windows 7',
            '8' => 'Windows 8',
            default => 'Windows '.$version.(str_contains($resto, 'home') ? ' Home' : ' Pro'),
        };
    }

    /** Texto libre del formulario → una de las opciones de la plantilla. */
    private static function estado(?string $texto): ?string
    {
        if ($texto === null) {
            return 'Bueno';
        }

        $clave = LectorHoja::normalizar($texto);

        return match (true) {
            str_contains($clave, 'no funcional') || str_contains($clave, 'no funciona') || str_contains($clave, 'dañad') || str_contains($clave, 'danad') => 'No funcional',
            str_contains($clave, 'malo') || str_contains($clave, 'mal estado') || str_contains($clave, 'cambio') => 'Malo, requiere cambio de componentes',
            str_contains($clave, 'regular') => 'Regular',
            default => 'Bueno',
        };
    }

    private static function vinculacion(?string $texto): ?string
    {
        $clave = LectorHoja::normalizar($texto);

        return match (true) {
            str_contains($clave, 'contratista') => 'Contratista',
            str_contains($clave, 'pasante') || str_contains($clave, 'practicante') => 'Pasante',
            str_contains($clave, 'planta') || str_contains($clave, 'funcionario') => 'Planta',
            default => null,
        };
    }

    private static function esPersonal(array $fila): bool
    {
        return Str::contains(Str::lower(implode(' ', array_filter($fila))), ['personal', 'no es de la gobernacion', 'no es de la gobernación']);
    }
}
