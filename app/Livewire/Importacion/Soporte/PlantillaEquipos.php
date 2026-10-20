<?php

namespace App\Livewire\Importacion\Soporte;

use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoEquipo;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Plantilla oficial de carga de equipos (RF-38): una fila por equipo, con las
 * columnas del modelo v4 y listas desplegables de los catálogos, para que no
 * entre ningún catálogo como texto libre (§12, criterios de aceptación).
 */
final class PlantillaEquipos
{
    public const ESTADOS = ['Bueno', 'Regular', 'Malo, requiere cambio de componentes', 'No funcional'];

    public const PROPIEDADES = ['Gobernación', 'Tercero'];

    public const FIGURAS = ['Comodato', 'Convenio', 'Proveedor', 'Arrendamiento'];

    /**
     * Clave interna => [título en el Excel, obligatoria, lista desplegable o null, ayuda].
     *
     * @var array<string, array{0: string, 1: bool, 2: ?string, 3: string}>
     */
    public const COLUMNAS = [
        'tipo_equipo' => ['Tipo de equipo', true, 'tipos', 'Del catálogo de tipos.'],
        'marca' => ['Marca', true, 'marcas', 'Del catálogo de marcas.'],
        'modelo' => ['Modelo', false, null, 'Si no existe para esa marca y tipo, se crea al cargar.'],
        'serial' => ['Serial', false, null, 'Vacío = serial provisional y queda pendiente de verificar (RN-02).'],
        'codigo_activo' => ['Código de activo', false, null, 'Formato I1-######.'],
        'justificacion_codigo' => ['Justificación del código repetido', false, null, 'Solo si el código ya está en otro equipo (RN-03).'],
        'descripcion' => ['Descripción', false, null, ''],
        'estado_funcionamiento' => ['Estado de funcionamiento', true, 'estados', ''],
        'propiedad' => ['Propiedad', false, 'propiedades', 'Vacío = Gobernación.'],
        'propietario_tercero' => ['Propietario (tercero)', false, 'proveedores', 'Obligatorio si la propiedad es Tercero (RN-16). Si no existe, se crea.'],
        'figura_tercero' => ['Figura del tercero', false, 'figuras', 'Obligatoria si la propiedad es Tercero.'],
        'sede' => ['Sede', true, 'sedes', ''],
        'piso' => ['Piso', true, null, 'Número (3) o nombre del piso (Local 44). Debe existir en la sede (RN-21).'],
        'dependencia' => ['Dependencia', true, 'dependencias', 'Nombre oficial o un nombre anterior que esté en la tabla de equivalencias.'],
        'proceso' => ['Proceso', false, 'procesos', 'Vacío = el de la dependencia.'],
        'responsable_tipo_documento' => ['Tipo de documento del responsable', false, 'tipos_documento', 'Vacío = CC.'],
        'responsable_documento' => ['Documento del responsable', false, null, 'Si ya existe la persona, se reutiliza.'],
        'responsable_nombres' => ['Nombres del responsable', false, null, ''],
        'responsable_apellidos' => ['Apellidos del responsable', false, null, ''],
        'responsable_cargo' => ['Cargo del responsable', false, 'cargos', 'Código o denominación del Decreto 607.'],
        'responsable_vinculacion' => ['Vinculación del responsable', false, 'vinculaciones', ''],
        'sistema_operativo' => ['Sistema operativo', false, 'sistemas', 'Solo cómputo.'],
        'procesador' => ['Procesador', false, null, 'Solo cómputo.'],
        'memoria_ram' => ['Memoria RAM', false, null, 'Solo cómputo.'],
        'disco' => ['Disco', false, null, 'Solo cómputo. Ej.: SSD 512GB.'],
        'perifericos' => ['Periféricos', false, null, 'Solo cómputo. Ej.: Teclado, Mouse, Sonido.'],
        'antivirus' => ['Antivirus', false, null, 'Sí, No o el nombre del producto.'],
        'nombre_red' => ['Nombre de red', false, null, ''],
        'ofimatica' => ['Ofimática', false, null, ''],
        'dominio' => ['Dominio', false, null, ''],
        'direccion_ip' => ['Dirección IP', false, null, ''],
        'direccion_mac' => ['Dirección MAC', false, null, ''],
        'forma_ingreso' => ['Forma de ingreso', false, 'formas_ingreso', 'Vacío = Carga inicial.'],
        'fecha_adquisicion' => ['Fecha de adquisición', false, null, 'AAAA-MM-DD o DD/MM/AAAA.'],
        'valor_adquisicion' => ['Valor de adquisición (COP)', false, null, 'Ej.: 2350000 o 2.350.000.'],
        'proveedor' => ['Proveedor', false, 'proveedores', 'A quién se compró. Si no existe, se crea.'],
        'numero_contrato' => ['N.º de contrato', false, null, ''],
        'numero_factura' => ['N.º de factura', false, null, ''],
        'fecha_fin_garantia' => ['Fin de la garantía', false, null, ''],
        'vida_util_meses' => ['Vida útil (meses)', false, null, 'Vacío = la del tipo de equipo.'],
        'observaciones' => ['Observaciones', false, null, ''],
    ];

    /**
     * Encabezados que acepta el lector: el título y la propia clave.
     *
     * @return array<string, array<int, string>>
     */
    public static function encabezadosAceptados(): array
    {
        $aceptados = [];
        foreach (self::COLUMNAS as $clave => [$titulo]) {
            $aceptados[$clave] = array_values(array_unique([LectorHoja::normalizar($titulo), LectorHoja::normalizar($clave)]));
        }

        return $aceptados;
    }

    /** Encabezados que debe tener la fila de títulos para reconocer la plantilla. */
    public static function anclas(): array
    {
        return [LectorHoja::normalizar('Tipo de equipo'), LectorHoja::normalizar('Sede')];
    }

    /**
     * Valores de cada lista desplegable, tomados de los catálogos.
     *
     * @return array<string, array<int, string>>
     */
    public static function listas(): array
    {
        return [
            'tipos' => TipoEquipo::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
            'marcas' => Marca::orderBy('nombre')->pluck('nombre')->all(),
            'estados' => self::ESTADOS,
            'propiedades' => self::PROPIEDADES,
            'figuras' => self::FIGURAS,
            'proveedores' => Proveedor::orderBy('razon_social')->pluck('razon_social')->all(),
            'sedes' => Sede::where('activa', true)->orderBy('nombre')->pluck('nombre')->all(),
            'dependencias' => Dependencia::where('activa', true)->orderBy('nombre')->pluck('nombre')->all(),
            'procesos' => Proceso::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
            'tipos_documento' => array_keys(Persona::TIPOS_DOCUMENTO),
            'cargos' => Cargo::orderBy('codigo')->get()->map(fn (Cargo $c) => $c->etiqueta())->all(),
            'vinculaciones' => array_values(Persona::VINCULACIONES),
            'sistemas' => SistemaOperativo::orderBy('nombre')->pluck('nombre')->all(),
            'formas_ingreso' => array_values(Equipo::FORMAS_INGRESO),
        ];
    }

    /**
     * Genera el .xlsx y devuelve la ruta del archivo temporal.
     *
     * @param  array<int, array<string, ?string>>  $filas  Filas ya llenas (p. ej. el inventario 2026 convertido).
     */
    public static function generar(array $filas = []): string
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet()->setTitle('Equipos');
        $listas = self::listas();

        // Hoja oculta con los valores de las listas: una columna por lista.
        $hojaListas = $libro->createSheet()->setTitle('Listas');
        $rangos = [];
        $columnaLista = 1;
        foreach ($listas as $nombre => $valores) {
            $letra = Coordinate::stringFromColumnIndex($columnaLista++);
            $hojaListas->setCellValue("{$letra}1", $nombre);
            foreach (array_values($valores) as $i => $valor) {
                $hojaListas->setCellValueExplicit("{$letra}".($i + 2), $valor, DataType::TYPE_STRING);
            }
            $rangos[$nombre] = "Listas!\${$letra}\$2:\${$letra}\$".max(2, count($valores) + 1);
        }
        $hojaListas->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $ultimaFila = max(500, count($filas) + 50);
        $columna = 1;
        foreach (self::COLUMNAS as $clave => [$titulo, $obligatoria, $lista, $ayuda]) {
            $letra = Coordinate::stringFromColumnIndex($columna++);
            $hoja->setCellValue("{$letra}1", $titulo.($obligatoria ? ' *' : ''));
            $hoja->getColumnDimension($letra)->setWidth(max(14, mb_strlen($titulo) + 4));

            if ($ayuda !== '') {
                $hoja->getComment("{$letra}1")->getText()->createText($ayuda);
            }

            if ($lista !== null && isset($rangos[$lista])) {
                $validacion = $hoja->getCell("{$letra}2")->getDataValidation();
                $validacion->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_WARNING)
                    ->setAllowBlank(true)
                    ->setShowDropDown(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Valor fuera del catálogo')
                    ->setError('Elige un valor de la lista.')
                    ->setFormula1($rangos[$lista]);
                $hoja->setDataValidation("{$letra}2:{$letra}{$ultimaFila}", $validacion);
            }

            foreach (array_values($filas) as $i => $fila) {
                if (($fila[$clave] ?? null) !== null) {
                    $hoja->setCellValueExplicit("{$letra}".($i + 2), (string) $fila[$clave], DataType::TYPE_STRING);
                }
            }
        }

        $ultimaLetra = Coordinate::stringFromColumnIndex(count(self::COLUMNAS));
        $hoja->getStyle("A1:{$ultimaLetra}1")->getFont()->setBold(true);
        $hoja->getStyle("A1:{$ultimaLetra}1")->getFill()->setFillType('solid')->getStartColor()->setRGB('DCE6F2');
        $hoja->freezePane('A2');
        $libro->setActiveSheetIndex(0);

        // tempnam() crea el archivo: se renombra a .xlsx para no dejar uno vacío por descarga.
        $base = tempnam(sys_get_temp_dir(), 'plantilla');
        $ruta = $base.'.xlsx';
        rename($base, $ruta);
        (new Xlsx($libro))->save($ruta);

        return $ruta;
    }
}
