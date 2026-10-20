<?php

namespace App\Livewire\Importacion\Soporte;

use App\Models\Equipo;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\HistorialService;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Carga de datos de adquisición desde el archivo de Almacén (RF-52, §12):
 * fecha, valor, proveedor, contrato y factura, cruzando por código de activo.
 *
 * Solo se cambia lo que el archivo trae (una celda vacía no borra nada). Cada
 * equipo actualizado registra un evento «actualizacion_datos» con el valor
 * anterior y el nuevo, igual que la edición manual (RF-12). Las filas que no
 * cruzan con ningún equipo quedan listadas como error.
 */
final class CargaAdquisicion
{
    public const COLUMNAS = [
        'codigo_activo' => ['codigo de activo', 'codigo activo', 'codigo', 'placa', 'numero de inventario'],
        'fecha_adquisicion' => ['fecha de adquisicion', 'fecha adquisicion', 'fecha de compra', 'fecha de ingreso', 'fecha'],
        'valor_adquisicion' => ['valor de adquisicion', 'valor adquisicion', 'valor', 'costo', 'valor historico'],
        'proveedor' => ['proveedor', 'razon social', 'proveedor razon social'],
        'nit' => ['nit', 'nit proveedor', 'nit del proveedor'],
        'numero_contrato' => ['contrato', 'numero de contrato', 'n de contrato', 'no contrato'],
        'numero_factura' => ['factura', 'numero de factura', 'n de factura', 'no factura'],
        'fecha_fin_garantia' => ['fin de garantia', 'fecha fin garantia', 'garantia hasta', 'vencimiento garantia'],
        'vida_util_meses' => ['vida util', 'vida util meses'],
        'forma_ingreso' => ['forma de ingreso', 'tipo de ingreso'],
    ];

    /** Mismas claves que la edición del equipo, para que el historial se vea igual. */
    private const ETIQUETAS = [
        'forma_ingreso', 'fecha_adquisicion', 'valor_adquisicion', 'proveedor',
        'numero_contrato', 'numero_factura', 'fecha_fin_garantia', 'vida_util_meses',
    ];

    public static function leer(string $ruta): array
    {
        foreach ([['codigo de activo'], ['codigo activo'], ['placa']] as $anclas) {
            $lectura = LectorHoja::leer($ruta, $anclas, self::COLUMNAS);
            if ($lectura['encontrado']) {
                return $lectura;
            }
        }

        return ['encontrado' => false, 'filas' => [], 'faltantes' => ['codigo de activo']];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function analizar(array $filas): array
    {
        $vistos = [];

        return array_map(function (array $fila) use (&$vistos) {
            $d = $fila['datos'];
            $errores = [];
            $advertencias = [];
            $intentar = function (callable $paso) use (&$errores) {
                try {
                    return $paso();
                } catch (InvalidArgumentException $e) {
                    $errores[] = $e->getMessage();

                    return null;
                }
            };

            $codigo = $intentar(fn () => Normalizar::codigoActivo($d['codigo_activo'] ?? null));
            $equipos = $codigo ? Equipo::with('proveedor')->where('codigo_activo', $codigo)->get() : collect();

            if ($codigo === null && $errores === []) {
                $errores[] = 'Falta el código de activo.';
            } elseif ($codigo !== null && $equipos->isEmpty()) {
                $errores[] = "No cruza: ningún equipo tiene el código {$codigo}.";
            } elseif ($equipos->count() > 1) {
                // RN-03: un código compartido (All in One y su pantalla) no dice a cuál aplica.
                $errores[] = "El código {$codigo} está en {$equipos->count()} equipos: actualízalos desde su hoja de vida.";
            } elseif ($codigo !== null && isset($vistos[$codigo])) {
                $errores[] = "El código {$codigo} ya aparece en la fila {$vistos[$codigo]}.";
            }
            if ($codigo !== null) {
                $vistos[$codigo] ??= $fila['numero'];
            }

            $formaIngreso = null;
            if ($texto = Normalizar::texto($d['forma_ingreso'] ?? null)) {
                $formaIngreso = collect(Equipo::FORMAS_INGRESO)->search(fn ($etiqueta, $clave) => in_array(Normalizar::clave($texto), [Normalizar::clave($etiqueta), Normalizar::clave($clave)], true)) ?: null;
                if (! $formaIngreso) {
                    $errores[] = "La forma de ingreso «{$texto}» no es válida.";
                }
            }

            $nuevos = array_filter([
                'forma_ingreso' => $formaIngreso,
                'fecha_adquisicion' => $intentar(fn () => Normalizar::fecha($d['fecha_adquisicion'] ?? null)),
                'valor_adquisicion' => $intentar(fn () => Normalizar::valor($d['valor_adquisicion'] ?? null)),
                'numero_contrato' => Normalizar::texto($d['numero_contrato'] ?? null),
                'numero_factura' => Normalizar::texto($d['numero_factura'] ?? null),
                'fecha_fin_garantia' => $intentar(fn () => Normalizar::fecha($d['fecha_fin_garantia'] ?? null)),
                'vida_util_meses' => $intentar(fn () => Normalizar::entero($d['vida_util_meses'] ?? null)),
            ], fn ($valor) => $valor !== null);

            $proveedor = Normalizar::texto($d['proveedor'] ?? null);
            $nit = Normalizar::texto($d['nit'] ?? null);

            $equipo = $equipos->count() === 1 ? $equipos->first() : null;
            $cambios = [];
            if ($equipo && $errores === []) {
                $cambios = $this->cambios($equipo, $nuevos, $proveedor, $nit);
                if ($cambios === []) {
                    $advertencias[] = 'Sin cambios: el equipo ya tiene estos datos.';
                }
                if ($proveedor && ! $this->proveedorExistente($proveedor, $nit)) {
                    $advertencias[] = "Proveedor nuevo: se agrega «{$proveedor}».";
                }
            }

            return [
                'numero' => $fila['numero'],
                'resumen' => $equipo ? "N.º {$equipo->numero_activo} · {$codigo}" : ($codigo ?? '—'),
                'errores' => array_values(array_unique($errores)),
                'advertencias' => $advertencias,
                'dependencia_sin_resolver' => null,
                'cambios' => $cambios,
                'valores' => ['equipo_id' => $equipo?->id, 'nuevos' => $nuevos, 'proveedor' => $proveedor, 'nit' => $nit],
            ];
        }, $filas);
    }

    /**
     * @return array{actualizados: int, sin_cambios: int, omitidos: int, proveedores_nuevos: int}
     */
    public function guardar(array $resultados, User $usuario, string $archivo): array
    {
        $resumen = ['actualizados' => 0, 'sin_cambios' => 0, 'omitidos' => 0, 'proveedores_nuevos' => 0];

        foreach ($resultados as $resultado) {
            if ($resultado['errores'] !== []) {
                $resumen['omitidos']++;

                continue;
            }

            $v = $resultado['valores'];
            $equipo = Equipo::with('proveedor')->findOrFail($v['equipo_id']);
            $cambios = $this->cambios($equipo, $v['nuevos'], $v['proveedor'], $v['nit']);

            if ($cambios === []) {
                $resumen['sin_cambios']++;

                continue;
            }

            $columnas = $v['nuevos'];
            if ($v['proveedor'] !== null) {
                $existente = $this->proveedorExistente($v['proveedor'], $v['nit']);
                if (! $existente) {
                    $resumen['proveedores_nuevos']++;
                }
                $columnas['proveedor_id'] = ($existente ?? Proveedor::create(['razon_social' => $v['proveedor'], 'nit' => $v['nit']]))->id;
            }

            $equipo->update($columnas);

            app(HistorialService::class)->registrar(
                equipo: $equipo,
                tipo: 'actualizacion_datos',
                usuario: $usuario,
                datos: ['valores' => [
                    'antes' => array_map(fn ($c) => $c['antes'], $cambios),
                    'despues' => array_map(fn ($c) => $c['despues'], $cambios),
                ]],
                descripcion: "Datos de adquisición cargados desde Almacén ({$archivo}, fila {$resultado['numero']}).",
            );

            $resumen['actualizados']++;
        }

        return $resumen;
    }

    /**
     * Campos que cambian, con su texto anterior y nuevo.
     *
     * @return array<string, array{antes: ?string, despues: ?string}>
     */
    private function cambios(Equipo $equipo, array $nuevos, ?string $proveedor, ?string $nit): array
    {
        $actual = [
            'forma_ingreso' => $equipo->forma_ingreso,
            'fecha_adquisicion' => $equipo->fecha_adquisicion?->toDateString(),
            'valor_adquisicion' => $equipo->valor_adquisicion,
            'numero_contrato' => $equipo->numero_contrato,
            'numero_factura' => $equipo->numero_factura,
            'fecha_fin_garantia' => $equipo->fecha_fin_garantia?->toDateString(),
            'vida_util_meses' => $equipo->vida_util_meses,
        ];

        $cambios = [];
        foreach ($nuevos as $campo => $valor) {
            $igual = $campo === 'valor_adquisicion'
                ? $actual[$campo] !== null && (float) $actual[$campo] === (float) $valor
                : (string) $actual[$campo] === (string) $valor;

            if (! $igual) {
                $cambios[$campo] = ['antes' => $this->texto($campo, $actual[$campo]), 'despues' => $this->texto($campo, $valor)];
            }
        }

        if ($proveedor !== null) {
            $existente = $this->proveedorExistente($proveedor, $nit);
            if ($existente?->id !== $equipo->proveedor_id || $equipo->proveedor_id === null) {
                $cambios['proveedor'] = ['antes' => $equipo->proveedor?->razon_social, 'despues' => $existente?->razon_social ?? $proveedor];
            }
        }

        return array_intersect_key($cambios, array_flip(self::ETIQUETAS));
    }

    private function texto(string $campo, mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return match ($campo) {
            'forma_ingreso' => Equipo::FORMAS_INGRESO[$valor] ?? (string) $valor,
            'fecha_adquisicion', 'fecha_fin_garantia' => Carbon::parse($valor)->format('d/m/Y'),
            'valor_adquisicion' => '$ '.number_format((float) $valor, fmod((float) $valor, 1.0) === 0.0 ? 0 : 2, ',', '.'),
            'vida_util_meses' => $valor.' meses',
            default => (string) $valor,
        };
    }

    private function proveedorExistente(string $razonSocial, ?string $nit): ?Proveedor
    {
        if ($nit !== null && $porNit = Proveedor::where('nit', $nit)->first()) {
            return $porNit;
        }

        return Proveedor::all()->first(fn (Proveedor $p) => Normalizar::clave($p->razon_social) === Normalizar::clave($razonSocial));
    }
}
