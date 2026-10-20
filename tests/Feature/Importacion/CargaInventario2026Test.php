<?php

use App\Livewire\Importacion\Index;
use App\Livewire\Importacion\Soporte\ConversorInventario2026;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Inventario 2026 (Microsoft Forms) convertido a la plantilla oficial y cargado
 * por el flujo normal (día 7, §12). Cabeceras reales del formulario (texto de
 * las preguntas, público) con filas inventadas.
 */
uses(RefreshDatabase::class);

function formsCsv(array $filas): UploadedFile
{
    $encabezados = [
        'ID', 'Correo electrónico', 'Nombre',
        'Dispositivo de procesamiento', 'Marca dispositivo de procesamiento', 'Modelo dispositivo de procesamiento', 'Código activo dispositivo de procesamiento',
        'Dispositivo de video', 'Marca del dispositivo de video', 'Modelo del dispositivo de video', 'Código Activo del dispositivo de video',
        'Dispositivo de impresión', 'Marca del dispositivo de impresión', 'Modelo del dispositivo de impresión', 'Código activo del dispositivo de impresión',
        'Versión Sistema operativo dispositivo de procesamiento (responda N/A si no aplica)',
        'El dispositivo cuenta con antivirus (responda N/A si no aplica)',
        '¿El equipo cuenta con Teclado?', '¿El equipo cuenta con Mouse?',
        'Describa el estado de funcionamiento actual del equipo', 'Describa las Observaciones adicionales del equipo',
        'Sede donde se encuentra el equipo', 'Piso donde se encuentra el equipo', 'Dependencia responsable del equipo',
        'Nombre del responsable del equipo', 'Numero de Cedula del responsable del equipo', 'Tipo de Vinculacion',
    ];
    $linea = fn (array $valores) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $valores));

    $contenido = $linea($encabezados)."\n";
    foreach ($filas as $fila) {
        $contenido .= $linea(array_map(fn ($e) => $fila[$e] ?? '', $encabezados))."\n";
    }

    return UploadedFile::fake()->createWithContent('inventario-2026.csv', $contenido);
}

function filaForms(array $cambios = []): array
{
    return array_merge([
        'ID' => '1',
        'Correo electrónico' => 'anonymous',
        'Dispositivo de procesamiento' => 'PC All in One',
        'Marca dispositivo de procesamiento' => 'Acer',
        'Modelo dispositivo de procesamiento' => 'Aspire C24',
        'Código activo dispositivo de procesamiento' => 'I1 003912',
        'Dispositivo de video' => 'Monitor',
        'Marca del dispositivo de video' => 'Acer',
        'Modelo del dispositivo de video' => 'V226',
        'Código Activo del dispositivo de video' => 'l1-003913',
        'Versión Sistema operativo dispositivo de procesamiento (responda N/A si no aplica)' => 'Windows 10 Pro',
        'El dispositivo cuenta con antivirus (responda N/A si no aplica)' => 'N/A',
        'Describa el estado de funcionamiento actual del equipo' => 'Partes y componentes en buen estado',
        'Sede donde se encuentra el equipo' => 'Palacio de Naín',
        'Piso donde se encuentra el equipo' => 'Piso 6',
        'Dependencia responsable del equipo' => 'DIRECION DE SISTEMAS',
        'Nombre del responsable del equipo' => 'Persona De Prueba',
        'Numero de Cedula del responsable del equipo' => '1000000001',
        'Tipo de Vinculacion' => 'Funcionario de planta',
    ], $cambios);
}

beforeEach(function () {
    $this->usuario = User::factory()->create();
    TipoEquipo::create(['nombre' => 'All in One', 'familia' => 'computo', 'usa_configuracion_computo' => true]);
    TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'usa_configuracion_computo' => true]);
    TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
    TipoEquipo::create(['nombre' => 'Impresora', 'familia' => 'impresion']);
    Marca::create(['nombre' => 'Acer']);
    $sede = Sede::create(['nombre' => 'Palacio de Naín']);
    $this->piso = Piso::create(['sede_id' => $sede->id, 'numero' => 6]);
    $this->tic = Dependencia::create(['nombre' => 'Dirección de Tecnología, Informática y Comunicaciones (TICS)']);
    DependenciaEquivalencia::create(['dependencia_id' => $this->tic->id, 'texto' => 'DIRECION DE SISTEMAS', 'origen' => 'FOR.01']);
});

/** Ruta con extensión .csv (el lector de Excel decide el formato por la extensión). */
function rutaCsv(UploadedFile $archivo): string
{
    $ruta = tempnam(sys_get_temp_dir(), 'inventario').'.csv';
    copy($archivo->getRealPath(), $ruta);

    return $ruta;
}

it('convierte una fila del formulario en un equipo de plantilla por cada dispositivo', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([filaForms()])));

    expect($conversion['filas'])->toHaveCount(2)
        ->and($conversion['filas'][0])->toMatchArray(['tipo_equipo' => 'All in One', 'marca' => 'Acer', 'codigo_activo' => 'I1 003912', 'piso' => '6', 'estado_funcionamiento' => 'Bueno'])
        ->and($conversion['filas'][1])->toMatchArray(['tipo_equipo' => 'Monitor', 'modelo' => 'V226'])
        ->and($conversion['filas'][0]['observaciones'])->toBe('Inventario 2026, fila 2.');
});

it('excluye los equipos personales y no los cuenta (RN-01)', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([
        filaForms(),
        filaForms(['Describa las Observaciones adicionales del equipo' => 'Equipo personal del funcionario']),
    ])));

    expect($conversion['leidas'])->toBe(2)
        ->and($conversion['excluidas'])->toBe(1)
        ->and($conversion['filas'])->toHaveCount(2);
});

it('carga el inventario por el flujo normal: códigos normalizados, dependencia por equivalencia y un solo responsable por fila', function () {
    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('tipo', 'inventario')
        ->set('archivo', formsCsv([filaForms()]))
        ->assertSet('resultados.0.errores', [])
        ->assertSet('resultados.1.errores', [])
        ->call('confirmar')
        ->assertHasNoErrors();

    $allInOne = Equipo::where('codigo_activo', 'I1-003912')->sole();
    $monitor = Equipo::where('codigo_activo', 'I1-003913')->sole();

    expect($allInOne->tipoEquipo->nombre)->toBe('All in One')
        ->and($allInOne->asignacionActual->dependencia_id)->toBe($this->tic->id)
        ->and($allInOne->asignacionActual->piso_id)->toBe($this->piso->id)
        ->and($allInOne->serial)->toStartWith('PENDIENTE-')
        ->and($allInOne->verificacion)->toBe('pendiente_de_verificar')
        ->and(Persona::count())->toBe(1)
        ->and($monitor->asignacionActual->persona_id)->toBe($allInOne->asignacionActual->persona_id)
        ->and(Persona::sole()->tipo_vinculacion)->toBe('planta');
});

it('arregla lo propio del inventario: tipos, sistema operativo, sede, serial en el código y seriales repetidos', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([
        filaForms([
            'Dispositivo de procesamiento' => 'PC Desktop',
            'Código activo dispositivo de procesamiento' => 'VNB4513213',
            'Dispositivo de video' => 'Monitor',
            'Código Activo del dispositivo de video' => 'I1-003788 Pc todo en Uno',
            'Versión Sistema operativo dispositivo de procesamiento (responda N/A si no aplica)' => 'W10',
            'Sede donde se encuentra el equipo' => 'Rentas',
            'Numero de Cedula del responsable del equipo' => 'FUNCIONARIO NO ESTABA',
        ]),
        filaForms(['ID' => '2', 'Dispositivo de impresión' => 'Impresora Laser', 'Código activo dispositivo de procesamiento' => '020410', 'Código Activo del dispositivo de video' => '']),
    ])));

    [$pc, $monitor] = $conversion['filas'];

    expect($pc['tipo_equipo'])->toBe('PC de escritorio')
        ->and($pc['serial'])->toBe('VNB4513213')
        ->and($pc['codigo_activo'])->toBeNull()
        ->and($pc['sistema_operativo'])->toBe('Windows 10 Pro')
        ->and($pc['sede'])->toBe('Places Mall')
        ->and($pc['responsable_documento'])->toBeNull()
        ->and($monitor['codigo_activo'])->toBe('I1-003788 Pc todo en Uno')
        ->and(collect($conversion['filas'])->pluck('tipo_equipo'))->toContain('Impresora');
});

it('un serial que se repite en varios equipos pasa a observaciones en vez de bloquear la fila', function () {
    // Dos monitores con el mismo «serial» (en realidad un número de parte) y uno con serial propio.
    $filas = [
        ['serial' => 'M27527-161', 'codigo_activo' => null, 'observaciones' => 'Inventario 2026, fila 2.'],
        ['serial' => 'm27527-161', 'codigo_activo' => null, 'observaciones' => 'Inventario 2026, fila 3.'],
        ['serial' => 'CN-UNICO', 'codigo_activo' => null, 'observaciones' => 'Inventario 2026, fila 4.'],
    ];

    $depuradas = (new ReflectionMethod(ConversorInventario2026::class, 'depurarIdentificadores'))->invoke(null, $filas);

    expect($depuradas[0]['serial'])->toBeNull()
        ->and($depuradas[0]['observaciones'])->toContain('Serial repetido en el inventario: M27527-161')
        ->and($depuradas[1]['serial'])->toBeNull()
        ->and($depuradas[2]['serial'])->toBe('CN-UNICO');
});

it('una sede escrita sin palabras de relleno se reconoce («Palacio Nain» es «Palacio de Naín»)', function () {
    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('tipo', 'inventario')
        ->set('archivo', formsCsv([filaForms(['Sede donde se encuentra el equipo' => 'Palacio Nain'])]))
        ->assertSet('resultados.0.errores', []);
});

it('el inventario convertido se puede descargar como plantilla', function () {
    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('tipo', 'inventario')
        ->set('archivo', formsCsv([filaForms()]))
        ->call('descargarInventarioConvertido')
        ->assertFileDownloaded('inventario-2026-en-plantilla.xlsx');
});

it('un piso con nombre se deja igual y uno con número queda solo el número', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([
        filaForms(['Sede donde se encuentra el equipo' => 'Rentas', 'Piso donde se encuentra el equipo' => 'Local 44']),
        filaForms(['ID' => '2', 'Piso donde se encuentra el equipo' => '3er piso']),
    ])));

    expect($conversion['filas'][0])->toMatchArray(['sede' => 'Places Mall', 'piso' => 'Local 44'])
        ->and($conversion['filas'][2]['piso'])->toBe('3');
});

it('«Dirección de Personal» o un «disco externo» no vuelven personal ni de tercero al equipo', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([
        filaForms(['Dependencia responsable del equipo' => 'DIRECCION DE PERSONAL']),
        filaForms(['ID' => '2', 'Describa las Observaciones adicionales del equipo' => 'Tiene un disco externo conectado']),
        filaForms(['ID' => '3', 'Describa las Observaciones adicionales del equipo' => 'Equipo en comodato con el proveedor']),
    ])));

    expect($conversion['excluidas'])->toBe(0)
        ->and($conversion['filas'])->toHaveCount(6)
        ->and($conversion['filas'][0]['propiedad'])->toBe('Gobernación')
        ->and($conversion['filas'][2]['propiedad'])->toBe('Gobernación')
        ->and($conversion['filas'][4]['propiedad'])->toBe('Tercero');
});

it('un código de activo mal escrito no se vuelve serial', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([
        filaForms(['Código activo dispositivo de procesamiento' => 'I1-2410']),
    ])));

    expect($conversion['filas'][0])->toMatchArray(['codigo_activo' => 'I1-2410', 'serial' => null]);
});

it('los periféricos marcados con SI van solo en el PC de la fila', function () {
    $conversion = ConversorInventario2026::convertir(rutaCsv(formsCsv([
        filaForms(['¿El equipo cuenta con Teclado?' => 'SI', '¿El equipo cuenta con Mouse?' => 'NO']),
    ])));

    expect($conversion['filas'][0]['perifericos'])->toBe('Teclado')
        ->and($conversion['filas'][1]['perifericos'])->toBeNull();
});
