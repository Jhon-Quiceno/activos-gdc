<?php

namespace App\Livewire\Importacion\Soporte;

use App\Models\Cargo;
use App\Models\DependenciaEquivalencia;
use App\Models\Persona;
use Illuminate\Support\Str;

/**
 * Carga de personas desde la planta de Talento Humano, Central y Educación
 * (RF-55).
 *
 * Minimización (RNF-19, RN-26, Ley 1581): solo se leen las columnas de
 * COLUMNAS. Las demás (salario, RH, estado civil, dirección, hijos, etnia…)
 * no se mapean, así que nunca salen del archivo: no llegan a la memoria de la
 * carga, ni al navegador, ni a la base.
 */
final class CargaPersonas
{
    /**
     * Columnas permitidas: clave interna => encabezados aceptados (normalizados).
     * Cualquier otra columna de la planta se descarta al leer.
     */
    public const COLUMNAS = [
        'documento' => ['cedula', 'numero de documento', 'documento', 'numero documento', 'identificacion'],
        'tipo_documento' => ['tipo de documento', 'tipo documento'],
        'nombre_completo' => ['nombre', 'nombre completo', 'nombres y apellidos', 'apellidos y nombres'],
        'nombres' => ['nombres'],
        'apellidos' => ['apellidos'],
        'cargo' => ['cargo', 'denominacion', 'denominacion del empleo'],
        'codigo' => ['codigo', 'codigo cargo', 'cargo codigo', 'codigo del empleo'],
        'grado' => ['grado'],
        // «REAL UBICACIÓN» es la dependencia donde de verdad trabaja (base-de-datos §4.3).
        'dependencia_real' => ['real ubicacion', 'ubicacion real'],
        'dependencia' => ['dependencia', 'area'],
        'jefe' => ['jefe inmediato', 'jefe'],
        'situacion' => ['activos vacantes y encargos', 'activos vacantes y encargo', 'situacion'],
        'provision' => ['sit', 'tipo de provision', 'provision del empleo'],
        'correo' => ['correo institucional', 'email institucional'],
        'fecha_fin' => ['fecha retiro', 'retiro fecha', 'fecha fin vinculacion', 'fecha de retiro'],
    ];

    public const ORDENES_NOMBRE = [
        'nombres_apellidos' => 'Nombres y luego apellidos (Ana María Pérez Díaz)',
        'apellidos_nombres' => 'Apellidos y luego nombres (PÉREZ DÍAZ ANA MARÍA)',
    ];

    /**
     * @return array{encontrado: bool, filas: array<int, array{numero: int, datos: array<string, ?string>}>, faltantes: array<int, string>}
     */
    public static function leer(string $ruta): array
    {
        foreach ([['cedula'], ['numero de documento'], ['documento']] as $anclas) {
            $lectura = LectorHoja::leer($ruta, $anclas, self::COLUMNAS);
            if ($lectura['encontrado']) {
                return $lectura;
            }
        }

        return ['encontrado' => false, 'filas' => [], 'faltantes' => ['cedula']];
    }

    /**
     * @param  array<int, array{numero: int, datos: array<string, ?string>}>  $filas
     * @param  string  $planta  central | educacion
     * @return array<int, array<string, mixed>>
     */
    public function analizar(array $filas, string $planta, string $ordenNombre = 'nombres_apellidos'): array
    {
        $resultados = [];
        $documentos = [];

        foreach ($filas as $fila) {
            $d = $fila['datos'];
            $errores = [];
            $advertencias = [];

            $situacion = Normalizar::clave($d['situacion'] ?? null);
            $documento = Normalizar::documento($d['documento'] ?? null);

            // Un cargo vacante no tiene persona: no es un error, se omite.
            if ($documento === null && ($situacion === '' || str_contains($situacion, 'vacante'))) {
                $resultados[] = ['numero' => $fila['numero'], 'resumen' => 'Cargo vacante', 'omitida' => true, 'errores' => [], 'advertencias' => [], 'dependencia_sin_resolver' => null, 'valores' => null];

                continue;
            }

            if ($documento === null) {
                $errores[] = 'Falta el número de documento.';
            } elseif (isset($documentos[$documento])) {
                $errores[] = "El documento está repetido en el archivo (fila {$documentos[$documento]}).";
            } else {
                $documentos[$documento] = $fila['numero'];
            }

            [$nombres, $apellidos] = $this->nombres($d, $ordenNombre);
            if ($nombres === null) {
                $errores[] = 'Falta el nombre.';
            }

            $cargoTexto = Normalizar::texto($d['cargo'] ?? null);
            $cargo = $this->cargo(Normalizar::texto($d['codigo'] ?? null), $cargoTexto);
            if (($cargoTexto || ($d['codigo'] ?? null)) && ! $cargo) {
                $advertencias[] = "El cargo «{$cargoTexto}» no está en el Decreto 607: queda sin cargo.";
            }
            $encargo = ($cargoTexto && preg_match('/\(\s*e\s*\)/i', $cargoTexto)) || str_contains($situacion, 'encargo');

            $dependenciaTexto = Normalizar::texto($d['dependencia_real'] ?? null) ?? Normalizar::texto($d['dependencia'] ?? null);
            $dependencia = $dependenciaTexto ? DependenciaEquivalencia::resolver($dependenciaTexto) : null;
            if ($dependenciaTexto && ! $dependencia) {
                $advertencias[] = "La dependencia «{$dependenciaTexto}» no coincide con ninguna oficial: queda sin dependencia (RF-39).";
            }

            $correo = Normalizar::texto($d['correo'] ?? null);
            if ($correo !== null && ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $advertencias[] = 'El correo institucional no es válido: se omite.';
                $correo = null;
            }

            $fechaFin = null;
            try {
                $fechaFin = Normalizar::fecha($d['fecha_fin'] ?? null);
            } catch (\InvalidArgumentException $e) {
                $advertencias[] = $e->getMessage();
            }

            $tipoDocumento = mb_strtoupper(Normalizar::texto($d['tipo_documento'] ?? null) ?? 'CC');
            if (! array_key_exists($tipoDocumento, Persona::TIPOS_DOCUMENTO)) {
                $tipoDocumento = 'CC';
            }

            $existe = $documento !== null && Persona::where('numero_documento', $documento)->exists();
            if ($existe) {
                $advertencias[] = 'Ya existe: se actualizan sus datos de la planta.';
            }

            $resultados[] = [
                'numero' => $fila['numero'],
                'resumen' => trim(($nombres ?? '').' '.($apellidos ?? '')),
                'omitida' => false,
                'errores' => $errores,
                'advertencias' => $advertencias,
                'dependencia_sin_resolver' => $dependenciaTexto && ! $dependencia ? $dependenciaTexto : null,
                'valores' => [
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $documento,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'correo_institucional' => $correo,
                    'cargo_id' => $cargo?->id,
                    'grado' => $this->grado($d['grado'] ?? null),
                    'encargo' => $encargo,
                    'dependencia_id' => $dependencia?->id,
                    'planta' => $planta,
                    'tipo_vinculacion' => 'planta',
                    'tipo_provision' => $this->provision($d['provision'] ?? null),
                    'fecha_fin_vinculacion' => $fechaFin,
                    'jefe' => Normalizar::texto($d['jefe'] ?? null),
                ],
            ];
        }

        return $resultados;
    }

    /**
     * Crea o actualiza las personas (por número de documento) y, en una segunda
     * pasada, enlaza el jefe inmediato por su nombre.
     *
     * @return array{creadas: int, actualizadas: int, omitidas: int, jefes_enlazados: int}
     */
    public function guardar(array $resultados): array
    {
        $resumen = ['creadas' => 0, 'actualizadas' => 0, 'omitidas' => 0, 'jefes_enlazados' => 0];
        $jefes = [];

        foreach ($resultados as $resultado) {
            if ($resultado['omitida'] || $resultado['errores'] !== []) {
                $resumen['omitidas']++;

                continue;
            }

            $valores = $resultado['valores'];
            $jefe = $valores['jefe'];
            unset($valores['jefe']);

            $persona = Persona::updateOrCreate(['numero_documento' => $valores['numero_documento']], $valores + ['activo' => true]);
            $resumen[$persona->wasRecentlyCreated ? 'creadas' : 'actualizadas']++;

            if ($jefe !== null) {
                $jefes[$persona->id] = $jefe;
            }
        }

        if ($jefes !== []) {
            $porNombre = Persona::all()->groupBy(fn (Persona $p) => Normalizar::clave($p->nombreCompleto()));

            foreach ($jefes as $personaId => $nombreJefe) {
                $candidatos = $porNombre->get(Normalizar::clave($nombreJefe));

                // Solo si el nombre identifica a una sola persona: nunca se adivina.
                if ($candidatos && $candidatos->count() === 1 && $candidatos->first()->id !== $personaId) {
                    Persona::whereKey($personaId)->update(['jefe_inmediato_id' => $candidatos->first()->id]);
                    $resumen['jefes_enlazados']++;
                }
            }
        }

        return $resumen;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function nombres(array $d, string $orden): array
    {
        $nombres = Normalizar::texto($d['nombres'] ?? null);
        $apellidos = Normalizar::texto($d['apellidos'] ?? null);

        if ($nombres !== null) {
            return [$this->formatoNombre($nombres), $apellidos !== null ? $this->formatoNombre($apellidos) : null];
        }

        $completo = Normalizar::texto($d['nombre_completo'] ?? null);
        if ($completo === null) {
            return [null, null];
        }

        $palabras = explode(' ', $this->formatoNombre($completo));
        $total = count($palabras);
        if ($total === 1) {
            return [$palabras[0], null];
        }

        // Con 2 palabras: 1 + 1; con 3: un nombre y dos apellidos; con 4 o más: dos apellidos y el resto nombres.
        $cantidadApellidos = $total === 2 ? 1 : 2;

        if ($orden === 'apellidos_nombres') {
            return [implode(' ', array_slice($palabras, $cantidadApellidos)), implode(' ', array_slice($palabras, 0, $cantidadApellidos))];
        }

        return [implode(' ', array_slice($palabras, 0, $total - $cantidadApellidos)), implode(' ', array_slice($palabras, $total - $cantidadApellidos))];
    }

    /** «PÉREZ DÍAZ» → «Pérez Díaz»; un nombre ya escrito con mayúsculas y minúsculas se respeta. */
    private function formatoNombre(string $texto): string
    {
        return mb_strtoupper($texto) === $texto ? Str::title(mb_strtolower($texto)) : $texto;
    }

    private function cargo(?string $codigo, ?string $denominacion): ?Cargo
    {
        $codigo = $codigo !== null ? preg_replace('/\.0+$/', '', $codigo) : null;
        if ($codigo !== null && ctype_digit($codigo)) {
            $porCodigo = Cargo::where('codigo', str_pad($codigo, 3, '0', STR_PAD_LEFT))->first();
            if ($porCodigo) {
                return $porCodigo;
            }
        }

        if ($denominacion === null) {
            return null;
        }

        $clave = Normalizar::clave(preg_replace('/\(\s*e\s*\)|cod\.?\s*\d+.*$|grado.*$/i', '', $denominacion));

        return Cargo::all()->first(fn (Cargo $c) => Normalizar::clave($c->denominacion) === $clave);
    }

    /** «7.0» → «07». */
    private function grado(?string $valor): ?string
    {
        $texto = Normalizar::texto($valor);
        if ($texto === null) {
            return null;
        }

        $numero = preg_replace('/\.0+$/', '', $texto);

        return ctype_digit($numero) ? str_pad($numero, 2, '0', STR_PAD_LEFT) : null;
    }

    /** Columna «SIT.» de la planta: C.A, L.N., P.R, E.P / De periodo. */
    private function provision(?string $valor): string
    {
        $clave = str_replace(' ', '', Normalizar::clave($valor));

        return match (true) {
            in_array($clave, ['ca', 'carreraadministrativa'], true) => 'carrera_administrativa',
            in_array($clave, ['ln', 'librenombramiento', 'librenombramientoyremocion'], true) => 'libre_nombramiento',
            in_array($clave, ['pr', 'provisional'], true) => 'provisional',
            in_array($clave, ['ep', 'deperiodo', 'periodo'], true) => 'periodo',
            $clave === 'temporal' => 'temporal',
            default => 'no_aplica',
        };
    }
}
