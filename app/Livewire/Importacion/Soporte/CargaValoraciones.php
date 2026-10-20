<?php

namespace App\Livewire\Importacion\Soporte;

use App\Livewire\Importacion\InventarioExcel;
use App\Models\Equipo;
use App\Models\User;
use App\Models\ValoracionActivo;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carga de valoraciones desde una matriz FOR.01 diligenciada (Manual GT-M4).
 *
 * Cada fila se cruza con su equipo por «ID ACTIVO» (número de activo, RN-27)
 * o, si la matriz la trae, por código de activo. Confidencialidad, integridad
 * y disponibilidad se toman de las columnas numéricas (1 a 4) o, si no están,
 * de la respuesta en texto. La valoración se crea con
 * ValoracionActivo::registrar(), que aplica RN-22 a RN-25.
 *
 * El FOR.01 tiene encabezados en varias filas (el número de C, I y D está una
 * fila arriba del encabezado principal), así que se lee con encabezados
 * combinados.
 */
final class CargaValoraciones
{
    private const CRITERIOS = ['confidencialidad', 'integridad', 'disponibilidad'];

    /** Palabras de la respuesta (FOR.01 / GT-M4 tablas 3 a 5) → nivel. */
    private const RESPUESTAS = [
        'confidencialidad' => [4 => ['inestimable', 'irreversible'], 3 => ['importante', 'grave'], 2 => ['considerabl'], 1 => ['no es relevante', 'parcial']],
        'integridad' => [4 => ['toda la organizacion', 'irreversibl'], 3 => ['unidades o procesos', 'grave'], 2 => ['actividades servicios o personas', 'considerabl'], 1 => ['no es relevante', 'parcial']],
        'disponibilidad' => [4 => ['nunca'], 3 => ['una hora'], 2 => ['un dia'], 1 => ['semana']],
    ];

    /**
     * @return array{encontrado: bool, filas: array<int, array{numero: int, datos: array<string, ?string>}>, faltantes: array<int, string>}
     */
    public static function leer(string $ruta): array
    {
        foreach (Excel::toArray(new InventarioExcel, $ruta) as $hoja) {
            $indice = null;
            foreach (array_slice($hoja, 0, 30, true) as $i => $fila) {
                if (in_array('id activo', array_map(fn ($v) => LectorHoja::normalizar($v), $fila), true)) {
                    $indice = $i;
                    break;
                }
            }

            if ($indice === null) {
                continue;
            }

            $mapa = self::mapaColumnas($hoja, $indice);
            $filas = [];
            foreach (array_slice($hoja, $indice + 1, null, true) as $posicion => $fila) {
                $datos = [];
                foreach ($mapa as $columna => $clave) {
                    $valor = trim((string) ($fila[$columna] ?? ''));
                    $datos[$clave] = $valor === '' ? null : $valor;
                }

                // Filas de subtítulo («Escenario», «Respuesta») o vacías.
                if (($datos['id_activo'] ?? null) === null && ($datos['codigo_activo'] ?? null) === null) {
                    continue;
                }

                $filas[] = ['numero' => $posicion + 1, 'datos' => $datos];
            }

            return ['encontrado' => true, 'filas' => $filas, 'faltantes' => array_values(array_diff(['id_activo', 'confidencialidad', 'integridad', 'disponibilidad'], $mapa))];
        }

        return ['encontrado' => false, 'filas' => [], 'faltantes' => ['id_activo']];
    }

    /**
     * Columna => clave interna, mirando la fila del encabezado y sus vecinas.
     *
     * @return array<int, string>
     */
    private static function mapaColumnas(array $hoja, int $indice): array
    {
        $norm = fn (int $fila, int $col) => LectorHoja::normalizar($hoja[$fila][$col] ?? null);
        $principales = [
            'id activo' => 'id_activo',
            'codigo de activo' => 'codigo_activo',
            'codigo activo' => 'codigo_activo',
            'contiene datos personales' => 'datos_personales',
            'clasificacion datos personales' => 'clasificacion',
            'infraestructura critica' => 'infraestructura',
            'continuidad del negocio' => 'continuidad',
            'propietario del activo cargo' => 'propietario',
            'custodio tecnico del activo cargo' => 'custodio',
            'custodio del activo cargo' => 'custodio',
            'tipo ubicacion del activo' => 'tipo_ubicacion',
            'tiempo de retencion de los datos anos' => 'retencion',
            'tiempo de retencion de los datos' => 'retencion',
        ];

        $mapa = [];
        $columnas = max(array_map('count', array_slice($hoja, max(0, $indice - 1), 3)));

        for ($col = 0; $col < $columnas; $col++) {
            $titulo = $norm($indice, $col);
            if (isset($principales[$titulo]) && ! in_array($principales[$titulo], $mapa, true)) {
                $mapa[$col] = $principales[$titulo];

                continue;
            }

            foreach (self::CRITERIOS as $criterio) {
                // Columna numérica: el título está en la fila de arriba y el encabezado principal está vacío.
                if ($indice > 0 && $norm($indice - 1, $col) === $criterio && $titulo === '') {
                    $mapa[$col] = $criterio;
                }
                // Columna de respuesta en texto: la siguiente al título del criterio, marcada «Respuesta».
                if ($titulo === $criterio && $norm($indice + 1, $col + 1) === 'respuesta' && ! isset($mapa[$col + 1])) {
                    $mapa[$col + 1] = $criterio.'_respuesta';
                }
                // Plantilla simple: el número va directo bajo el título del criterio.
                if ($titulo === $criterio && $norm($indice + 1, $col + 1) !== 'respuesta' && ! in_array($criterio, $mapa, true)) {
                    $mapa[$col] = $criterio;
                }
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, array{numero: int, datos: array<string, ?string>}>  $filas
     * @return array<int, array<string, mixed>>
     */
    public function analizar(array $filas): array
    {
        $vistos = [];

        return array_map(function (array $fila) use (&$vistos) {
            $d = $fila['datos'];
            $errores = [];

            $equipo = $this->equipo($d);
            if (! $equipo) {
                $errores[] = 'No hay ningún equipo con ese ID de activo ni ese código: no se puede valorar.';
            } elseif (isset($vistos[$equipo->id])) {
                $errores[] = "El equipo ya aparece en la fila {$vistos[$equipo->id]}.";
            } else {
                $vistos[$equipo->id] = $fila['numero'];
            }

            $niveles = [];
            foreach (self::CRITERIOS as $criterio) {
                $niveles[$criterio] = $this->nivel($criterio, $d[$criterio] ?? null, $d[$criterio.'_respuesta'] ?? null);
                if ($niveles[$criterio] === null) {
                    $errores[] = 'Falta la calificación de '.$criterio.' (1 a 4 o la respuesta del FOR.01).';
                }
            }

            $datos = [
                'contiene_datos_personales' => $this->siNo($d['datos_personales'] ?? null),
                'clasificacion_datos' => $this->clasificacion($d['clasificacion'] ?? null),
                'infraestructura_critica' => $this->siNo($d['infraestructura'] ?? null),
                'continuidad_negocio' => $this->siNo($d['continuidad'] ?? null),
                'propietario_cargo' => mb_substr(Normalizar::texto($d['propietario'] ?? null) ?? '', 0, 120),
                'custodio_cargo' => mb_substr(Normalizar::texto($d['custodio'] ?? null) ?? '', 0, 120),
                'tipo_ubicacion' => $this->tipoUbicacion($d['tipo_ubicacion'] ?? null),
                'tiempo_retencion' => ($r = Normalizar::texto($d['retencion'] ?? null)) !== null ? mb_substr($r, 0, 40) : null,
            ] + $niveles;

            if ($datos['propietario_cargo'] === '') {
                $errores[] = 'Falta el propietario del activo (cargo).';
            }
            if ($datos['custodio_cargo'] === '') {
                $errores[] = 'Falta el custodio del activo (cargo).';
            }

            // RN-24 y RN-25: se revisan aquí para mostrarlos en la vista previa.
            if ($equipo && ! in_array(null, $niveles, true)) {
                try {
                    $prueba = new ValoracionActivo($datos + ['equipo_id' => $equipo->id]);
                    $prueba->setRelation('equipo', $equipo);
                    $prueba->validarReglas();
                } catch (ValidationException $e) {
                    array_push($errores, ...array_merge(...array_values($e->errors())));
                }
            }

            $total = array_sum(array_map('intval', $niveles));

            return [
                'numero' => $fila['numero'],
                'resumen' => $equipo
                    ? "N.º {$equipo->numero_activo} · {$equipo->tipoEquipo?->nombre} · ".($equipo->codigo_activo ?? $equipo->serial)
                    : 'ID '.($d['id_activo'] ?? $d['codigo_activo'] ?? '—'),
                'errores' => array_values(array_unique($errores)),
                'advertencias' => $equipo?->valoracionVigente ? ['Ya tiene valoración: esta queda como reclasificación (RF-58).'] : [],
                'dependencia_sin_resolver' => null,
                'calculo' => in_array(null, $niveles, true) ? null : $total.' · '.ValoracionActivo::IMPORTANCIAS[ValoracionActivo::importanciaPara($total)],
                'valores' => ['equipo_id' => $equipo?->id, 'datos' => $datos],
            ];
        }, $filas);
    }

    /**
     * @return array{creadas: int, omitidas: int}
     */
    public function guardar(array $resultados, User $usuario, string $archivo): array
    {
        $resumen = ['creadas' => 0, 'omitidas' => 0];

        foreach ($resultados as $resultado) {
            if ($resultado['errores'] !== []) {
                $resumen['omitidas']++;

                continue;
            }

            ValoracionActivo::registrar(
                equipo: Equipo::with('tipoEquipo')->findOrFail($resultado['valores']['equipo_id']),
                usuario: $usuario,
                datos: $resultado['valores']['datos'],
                descripcion: "Valoración cargada desde la matriz FOR.01 ({$archivo}, fila {$resultado['numero']}).",
            );
            $resumen['creadas']++;
        }

        return $resumen;
    }

    private function equipo(array $d): ?Equipo
    {
        $id = Normalizar::entero(preg_replace('/\D/', '', (string) ($d['id_activo'] ?? '')) ?: null);
        if ($id !== null && $equipo = Equipo::with(['tipoEquipo', 'valoracionVigente'])->where('numero_activo', $id)->first()) {
            return $equipo;
        }

        try {
            $codigo = Normalizar::codigoActivo($d['codigo_activo'] ?? null);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $codigo ? Equipo::with(['tipoEquipo', 'valoracionVigente'])->where('codigo_activo', $codigo)->first() : null;
    }

    private function nivel(string $criterio, ?string $numero, ?string $respuesta): ?int
    {
        foreach ([$numero, $respuesta] as $valor) {
            $texto = Normalizar::texto($valor);
            if ($texto === null) {
                continue;
            }

            $sinDecimal = preg_replace('/\.0+$/', '', $texto);
            if (in_array($sinDecimal, ['1', '2', '3', '4'], true)) {
                return (int) $sinDecimal;
            }

            $clave = LectorHoja::normalizar($texto);
            foreach (self::RESPUESTAS[$criterio] as $nivel => $palabras) {
                foreach ($palabras as $palabra) {
                    if (str_contains($clave, $palabra)) {
                        return $nivel;
                    }
                }
            }
        }

        return null;
    }

    private function siNo(?string $valor): string
    {
        return match (LectorHoja::normalizar($valor)) {
            'si', 's' => 'si',
            'no', 'n' => 'no',
            default => 'no_aplica',
        };
    }

    private function clasificacion(?string $valor): string
    {
        $clave = LectorHoja::normalizar($valor);

        foreach (ValoracionActivo::CLASIFICACIONES_DATOS as $valorInterno => $etiqueta) {
            if ($clave === LectorHoja::normalizar($etiqueta) || $clave === str_replace('_', ' ', $valorInterno)) {
                return $valorInterno;
            }
        }

        return 'no_aplica';
    }

    private function tipoUbicacion(?string $valor): string
    {
        $clave = LectorHoja::normalizar($valor);

        return match (true) {
            str_contains($clave, 'fisic') && str_contains($clave, 'electronic') => 'fisico_electronico',
            str_contains($clave, 'electronic') => 'electronico',
            default => 'fisico',
        };
    }
}
