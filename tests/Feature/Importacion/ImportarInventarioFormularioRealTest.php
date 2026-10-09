<?php

use App\Livewire\Importacion\Index;
use App\Models\Asignacion;
use App\Models\Componente;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * El inventario 2026 real es una exportación de Google Forms: sus encabezados
 * son preguntas completas ("Dispositivo de procesamiento", "¿Equipo cuenta
 * con Teclado?"...), no las palabras sueltas que usan los fixtures de
 * ImportarInventarioTest. Este archivo reproduce esas cabeceras reales
 * (texto de las preguntas del formulario, público) con filas de datos
 * inventadas — nunca con datos de personas reales — para probar que el
 * importador las reconoce.
 */
uses(RefreshDatabase::class);

function csvFormularioReal(array $filas): UploadedFile
{
    $encabezados = [
        'ID', 'Hora de inicio', 'Hora de finalización', 'Correo electrónico', 'Nombre', 'Hora de la última modificación',
        'Dispositivo de procesamiento', "Marca dispositivo de procesamiento\n", 'Modelo dispositivo de procesamiento', 'Código activo dispositivo de procesamiento',
        'Dispositivo de video', 'Marca del dispositivo de video', 'Modelo del dispositivo de video', 'Código Activo del dispositivo de video',
        'Dispositivo de impresión', 'Marca del dispositivo de impresión', 'Modelo del dispositivo de impresión', 'Código activo del dispositivo de impresión',
        'Dispositivo de conectividad', 'Marca del dispositivo de conectividad', 'Modelo del dispositivo de conectividad', 'Código Activo del dispositivo de conectividad',
        'Dispositivo de digitalización', 'Marca del dispositivo de digitalización', 'Modelo del dispositivo de digitalización', 'Código Activo del dispositivo de digitalización',
        'Otros Dispositivos', 'Marca otros dispositivo', 'Modelo otros dispositivo', 'Código Activo otros dispositivos',
        'Versión Sistema operativo dispositivo de procesamiento (responda N/A si no aplica)',
        "\n¿Equipo cuenta con Teclado?", 'Describa el estado del Teclado (responda N/A si no aplica)', 'Marca del Teclado (responda N/A si no aplica)', 'Serial del Teclado: (responda N/A si no aplica)',
        'El dispositivo cuenta con antivirus (responda N/A si no aplica)',
        '¿Equipo cuenta con Mouse?: ', 'Describa el estado del Mouse: (responda N/A si no aplica)', 'Marca del Mouse: (responda N/A si no aplica)', 'Serial del Mouse: (responda N/A si no aplica)',
        'El equipo cuenta con dispositivo de sonido?', 'Describa el estado del dispositivo de sonido: (responda N/A si no aplica)', 'Marca del dispositivo de sonido: (responda N/A si no aplica)', 'Serial del dispositivo de sonido: (responda N/A si no aplica)',
        "\nDescriba el estado de funcionamiento actual del equipo", 'Describa las Observaciones adicionales del equipo',
        'Sede donde se encuentra el equipo', "\nPiso donde se encuentra el equipo", 'Dependencia responsable del equipo',
        'Nombre del responsable del equipo', 'Numero de Cedula del responsable del equipo', 'Tipo de Vinculacion',
        'Nombre de quien realiza el inventario',
    ];

    $linea = fn (array $valores) => implode(',', array_map(
        fn ($valor) => '"'.str_replace('"', '""', (string) $valor).'"',
        $valores
    ));

    $contenido = implode("\n", array_merge([$linea($encabezados)], array_map($linea, $filas)))."\n";

    return UploadedFile::fake()->createWithContent('inventario.csv', $contenido);
}

/** Fila con los 53 campos del formulario real, en el mismo orden que csvFormularioReal(). */
function filaFormularioReal(array $cambios = []): array
{
    $base = [
        'ID' => '1', 'Hora de inicio' => '', 'Hora de finalización' => '', 'Correo electrónico' => 'anonymous', 'Nombre' => '', 'Hora de la última modificación' => '',
        'procesamiento' => 'PC All in One', 'marca_procesamiento' => 'Acer', 'modelo_procesamiento' => 'Ar5B22', 'codigo_procesamiento' => 'I1-003912',
        'video' => 'Monitor', 'marca_video' => 'Acer', 'modelo_video' => 'Ar5B22', 'codigo_video' => 'I1-003913',
        'impresion' => '', 'marca_impresion' => '', 'modelo_impresion' => '', 'codigo_impresion' => '',
        'conectividad' => '', 'marca_conectividad' => '', 'modelo_conectividad' => '', 'codigo_conectividad' => '',
        'digitalizacion' => '', 'marca_digitalizacion' => '', 'modelo_digitalizacion' => '', 'codigo_digitalizacion' => '',
        'otros' => '', 'marca_otros' => '', 'modelo_otros' => '', 'codigo_otros' => '',
        'sistema_operativo' => 'Windows 10 pro',
        'cuenta_teclado' => 'SI', 'estado_teclado' => 'Bueno', 'marca_teclado' => 'Acer', 'serial_teclado' => 'TEC-0001',
        'antivirus' => 'N/A',
        'cuenta_mouse' => 'SI', 'estado_mouse' => 'Bueno', 'marca_mouse' => 'Acer', 'serial_mouse' => 'MOU-0001',
        'cuenta_sonido' => 'NO', 'estado_sonido' => '', 'marca_sonido' => '', 'serial_sonido' => '',
        'estado_general' => 'Partes y componentes en buen estado', 'observaciones' => '',
        'sede' => 'Palacio Naín', 'piso' => 'Piso 3', 'dependencia' => 'Dirección TIC',
        'responsable_nombre' => 'Persona de Prueba', 'responsable_cedula' => '1000000001', 'vinculacion' => 'Funcionario de planta',
        'inventariador' => 'Quien hizo el inventario',
    ];

    return array_values(array_merge($base, $cambios));
}

beforeEach(function () {
    $this->usuario = User::factory()->create();

    TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo']);
    TipoEquipo::create(['nombre' => 'Servidor', 'familia' => 'computo']);
    TipoEquipo::create(['nombre' => 'Otro', 'familia' => 'computo']);
    TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
    TipoEquipo::create(['nombre' => 'Impresora', 'familia' => 'impresion']);
    TipoEquipo::create(['nombre' => 'Switch', 'familia' => 'conectividad']);
    TipoEquipo::create(['nombre' => 'Access Point', 'familia' => 'conectividad']);
    TipoEquipo::create(['nombre' => 'Escáner', 'familia' => 'digitalizacion']);
    TipoEquipo::create(['nombre' => 'UPS', 'familia' => 'energia']);
    TipoEquipo::create(['nombre' => 'Video beam', 'familia' => 'proyeccion']);

    Marca::create(['nombre' => 'Acer']);
    $this->sede = Sede::create(['nombre' => 'Palacio Naín']);
    $this->piso = Piso::create(['numero' => 3]);
    $this->dependencia = Dependencia::create(['nombre' => 'Dirección TIC']);
});

it('reconoce pc, monitor y periféricos con las cabeceras reales del formulario (no las cortas de prueba)', function () {
    $archivo = csvFormularioReal([filaFormularioReal()]);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    // Con el formato corto (header "PC") esto ya funcionaba; lo importante
    // acá es que el header largo real también detecta pc + monitor.
    expect($testable->filas[0]['equipos'])->toHaveCount(2);

    $testable->call('confirmar')->assertHasNoErrors();

    $pc = Equipo::where('codigo_activo', 'I1-003912')->sole();
    $monitor = Equipo::where('codigo_activo', 'I1-003913')->sole();

    expect($pc->tipoEquipo->nombre)->toBe('PC de escritorio') // antes del fix: "Access Point"
        ->and($monitor->tipoEquipo->nombre)->toBe('Monitor')
        ->and($pc->marca->nombre)->toBe('Acer');
});

it('detecta teclado y mouse por el flag SI/NO, no por si el serial vino con texto (RN-02)', function () {
    $archivo = csvFormularioReal([filaFormularioReal()]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    $nombresComponentes = Componente::with('tipoComponente')->get()->pluck('tipoComponente.nombre');

    expect($nombresComponentes)->toContain('Teclado')->toContain('Mouse')
        ->and($nombresComponentes)->not->toContain('Sonido'); // "cuenta con sonido" = NO
});

it('un valor "No se ve" en la marca de un periférico queda vacío, no guardado literal', function () {
    $archivo = csvFormularioReal([filaFormularioReal(['marca_mouse' => 'No se ve'])]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    $mouse = Componente::whereHas('tipoComponente', fn ($q) => $q->where('nombre', 'Mouse'))->sole();

    expect($mouse->marca)->toBeNull();
});

it('detecta conectividad y otros dispositivos, categorías que no existían antes de este fix', function () {
    $archivo = csvFormularioReal([
        filaFormularioReal([
            'procesamiento' => '', 'marca_procesamiento' => '', 'modelo_procesamiento' => '', 'codigo_procesamiento' => '',
            'video' => '', 'marca_video' => '', 'modelo_video' => '', 'codigo_video' => '',
            'conectividad' => 'Access Point', 'marca_conectividad' => 'Ubiquiti', 'codigo_conectividad' => 'I1-050001',
            'otros' => 'Estabilizadores y UPS', 'marca_otros' => 'APC', 'codigo_otros' => 'I1-050002',
            'cuenta_teclado' => 'N/A', 'cuenta_mouse' => 'N/A', 'cuenta_sonido' => 'N/A',
        ]),
    ]);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    expect($testable->filas[0]['equipos'])->toHaveCount(2);

    $testable->call('confirmar')->assertHasNoErrors();

    $conectividad = Equipo::where('codigo_activo', 'I1-050001')->sole();
    $otro = Equipo::where('codigo_activo', 'I1-050002')->sole();

    expect($conectividad->tipoEquipo->nombre)->toBe('Access Point')
        // Antes del fix, "Estabilizadores y UPS" (frase larga) emparejaba con
        // "Servidor" por pura distancia de edición en vez de con "UPS".
        ->and($otro->tipoEquipo->nombre)->toBe('UPS');
});

it('captura el responsable del equipo (nombre, cedula y vinculacion normalizada) al importar', function () {
    $archivo = csvFormularioReal([filaFormularioReal()]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    $persona = Persona::sole();
    $asignacion = Asignacion::where('persona_id', $persona->id)->first();

    expect($persona->nombre)->toBe('Persona de Prueba')
        ->and($persona->cedula)->toBe('1000000001')
        ->and($persona->tipo_vinculacion)->toBe('planta') // "Funcionario de planta" -> enum valido
        ->and($asignacion)->not->toBeNull()
        ->and($asignacion->sede_id)->toBe($this->sede->id);
});

it('sin responsable en el archivo, la asignacion queda sin persona en vez de romper', function () {
    $archivo = csvFormularioReal([
        filaFormularioReal(['responsable_nombre' => '', 'responsable_cedula' => '', 'vinculacion' => '']),
    ]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    expect(Persona::count())->toBe(0);

    $asignaciones = Asignacion::whereIn('equipo_id', Equipo::pluck('id'))->get();
    expect($asignaciones)->not->toBeEmpty();
    expect($asignaciones->first()->persona_id)->toBeNull();
});

it('una sola persona por fila, no una por cada equipo que contenga (pc + monitor comparten responsable)', function () {
    $archivo = csvFormularioReal([filaFormularioReal()]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    expect(Equipo::count())->toBe(2) // pc + monitor de la misma fila
        ->and(Persona::count())->toBe(1)
        ->and(Asignacion::count())->toBe(2);
});
