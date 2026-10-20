<?php

use App\Livewire\Importacion\Index;
use App\Livewire\Importacion\Soporte\PlantillaEquipos;
use App\Models\Asignacion;
use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Importacion;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Carga de equipos desde la plantilla oficial (RF-38 a RF-41). Todo con
 * archivos sintéticos: nunca datos reales.
 */
uses(RefreshDatabase::class);

/** CSV con los títulos de la plantilla oficial y las filas dadas (clave => valor). */
function plantillaCsv(array $filas): UploadedFile
{
    $claves = array_keys(PlantillaEquipos::COLUMNAS);
    $linea = fn (array $valores) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $valores));

    $contenido = $linea(array_map(fn ($c) => PlantillaEquipos::COLUMNAS[$c][0].(PlantillaEquipos::COLUMNAS[$c][1] ? ' *' : ''), $claves))."\n";
    foreach ($filas as $fila) {
        $contenido .= $linea(array_map(fn ($c) => $fila[$c] ?? '', $claves))."\n";
    }

    return UploadedFile::fake()->createWithContent('carga-equipos.csv', $contenido);
}

function filaPlantilla(array $cambios = []): array
{
    return array_merge([
        'tipo_equipo' => 'PC de escritorio',
        'marca' => 'HP',
        'modelo' => 'ProDesk 400 G7',
        'serial' => 'MXL0001',
        'codigo_activo' => 'I1-024147',
        'estado_funcionamiento' => 'Bueno',
        'sede' => 'Palacio de Naín',
        'piso' => '6',
        'dependencia' => 'Dirección de Tecnología, Informática y Comunicaciones (TICS)',
    ], $cambios);
}

function cargaEquipos(UploadedFile $archivo, string $tipo = 'equipos')
{
    return Livewire::actingAs(test()->usuario)->test(Index::class)->set('tipo', $tipo)->set('archivo', $archivo);
}

beforeEach(function () {
    $this->usuario = User::factory()->create();
    $this->pc = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'usa_configuracion_computo' => true]);
    TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
    Marca::create(['nombre' => 'HP']);
    $this->sede = Sede::create(['nombre' => 'Palacio de Naín']);
    $this->piso = Piso::create(['sede_id' => $this->sede->id, 'numero' => 6]);
    $this->proceso = Proceso::create(['nombre' => 'Gestión Tecnológica', 'tipo' => 'apoyo']);
    $this->tic = Dependencia::create(['nombre' => 'Dirección de Tecnología, Informática y Comunicaciones (TICS)', 'nombre_corto' => 'Dirección TIC', 'proceso_id' => $this->proceso->id]);
});

it('la plantilla oficial trae los títulos y las listas desplegables de los catálogos', function () {
    $libro = IOFactory::load(PlantillaEquipos::generar());
    $hoja = $libro->getSheetByName('Equipos');

    expect($hoja->getCell('A1')->getValue())->toBe('Tipo de equipo *')
        ->and($hoja->getCell('A2')->getDataValidation()->getType())->toBe('list')
        ->and($libro->getSheetByName('Listas')->getCell('A2')->getValue())->toBe('Monitor')
        ->and($libro->getSheetByName('Listas')->getSheetState())->toBe('hidden');
});

it('no guarda nada al leer el archivo: solo al confirmar (RF-40)', function () {
    cargaEquipos(plantillaCsv([filaPlantilla()]))->assertSet('resultados.0.errores', []);

    expect(Equipo::count())->toBe(0)->and(Importacion::count())->toBe(0);
});

it('carga el equipo con modelo del catálogo, piso, dependencia, proceso y alta histórica', function () {
    cargaEquipos(plantillaCsv([filaPlantilla(['valor_adquisicion' => '2.350.000', 'fecha_adquisicion' => '15/03/2024'])]))
        ->call('confirmar')
        ->assertHasNoErrors();

    $equipo = Equipo::with(['modeloEquipo', 'asignacionActual'])->sole();
    $alta = Evento::sole();

    expect($equipo->modeloEquipo->nombre)->toBe('ProDesk 400 G7')
        ->and($equipo->fila_origen_importacion)->toBe(2)
        ->and($equipo->importacion->tipo)->toBe('equipos')
        ->and($equipo->proceso_id)->toBe($this->proceso->id)
        ->and($equipo->valor_adquisicion)->toBe('2350000.00')
        ->and($equipo->fecha_adquisicion->toDateString())->toBe('2024-03-15')
        ->and($equipo->verificacion)->toBe('verificado')
        ->and($equipo->asignacionActual->piso_id)->toBe($this->piso->id)
        ->and($equipo->asignacionActual->dependencia_id)->toBe($this->tic->id)
        ->and($alta->tipo)->toBe('alta')
        ->and($alta->es_historico)->toBeTrue()
        ->and($equipo->configuracionComputo)->not->toBeNull();
});

it('una dependencia con su nombre anterior al Decreto 609 se resuelve y una desconocida da error de fila', function () {
    DependenciaEquivalencia::create(['dependencia_id' => $this->tic->id, 'texto' => 'DIRECION DE SISTEMAS', 'origen' => 'FOR.01']);

    $carga = cargaEquipos(plantillaCsv([
        filaPlantilla(['dependencia' => 'direcion  de sistemas']),
        filaPlantilla(['serial' => 'MXL0002', 'codigo_activo' => 'I1-024148', 'dependencia' => 'Oficina que no existe']),
    ]));

    expect($carga->get('resultados.0.errores'))->toBe([])
        ->and($carga->get('resultados.1.errores')[0])->toContain('no coincide con ninguna oficial');

    $carga->call('confirmar');

    expect(Equipo::count())->toBe(1)
        ->and(Asignacion::sole()->dependencia_id)->toBe($this->tic->id);
});

it('desde la pantalla se crea la equivalencia que falta y la fila se corrige (RF-39)', function () {
    $carga = cargaEquipos(plantillaCsv([filaPlantilla(['dependencia' => 'Sistemas Gobernación'])]))
        ->assertSet('pendientes.0.texto', 'Sistemas Gobernación')
        ->set('pendientes.0.dependencia_id', $this->tic->id)
        ->call('guardarEquivalencia', 0)
        ->assertHasNoErrors();

    expect(DependenciaEquivalencia::where('texto', 'Sistemas Gobernación')->value('dependencia_id'))->toBe($this->tic->id)
        ->and($carga->get('resultados.0.errores'))->toBe([])
        ->and($carga->get('pendientes'))->toBe([]);
});

it('un piso que no existe en la sede da error de fila (RN-21)', function () {
    Piso::create(['sede_id' => Sede::create(['nombre' => 'Edificio Morindó'])->id, 'numero' => 8]);

    $carga = cargaEquipos(plantillaCsv([filaPlantilla(['piso' => '8'])]));

    expect($carga->get('resultados.0.errores'))->toContain('El piso «8» no existe en la sede Palacio de Naín (RN-21).');
});

it('sin serial queda con uno provisional y pendiente de verificar (RN-02, RF-41)', function () {
    cargaEquipos(plantillaCsv([filaPlantilla(['serial' => 'N/A'])]))->call('confirmar');

    $equipo = Equipo::sole();

    expect($equipo->serial)->toStartWith('PENDIENTE-')
        ->and($equipo->verificacion)->toBe('pendiente_de_verificar');
});

it('un serial repetido en el archivo o en la base da error de fila', function () {
    Equipo::create(['serial' => 'YA-EXISTE', 'tipo_equipo_id' => $this->pc->id, 'marca_id' => Marca::first()->id, 'propiedad' => 'gobernacion']);

    $carga = cargaEquipos(plantillaCsv([
        filaPlantilla(['serial' => 'DUP-1', 'codigo_activo' => '']),
        filaPlantilla(['serial' => 'dup-1', 'codigo_activo' => '']),
        filaPlantilla(['serial' => 'YA-EXISTE', 'codigo_activo' => '']),
    ]));

    expect($carga->get('resultados.0.errores'))->toContain('El serial DUP-1 está repetido en el archivo.')
        ->and($carga->get('resultados.2.errores'))->toContain('El serial YA-EXISTE ya está registrado.');
});

it('un código mal escrito se normaliza y uno sin forma de código da error', function () {
    $carga = cargaEquipos(plantillaCsv([
        filaPlantilla(['codigo_activo' => 'l1 24147']),
        filaPlantilla(['serial' => 'MXL0002', 'codigo_activo' => 'VNB4513213']),
    ]));

    expect($carga->get('resultados.0.valores.codigo_activo'))->toBe('I1-24147')
        ->and($carga->get('resultados.1.errores')[0])->toContain('no tiene el formato I1-######');
});

it('crea o reutiliza al responsable con los campos v4', function () {
    $existente = Persona::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'numero_documento' => '1067888999']);
    Cargo::create(['codigo' => '219', 'denominacion' => 'Profesional Universitario', 'nivel' => 'profesional']);

    cargaEquipos(plantillaCsv([
        filaPlantilla(['responsable_documento' => '1.067.888.999']),
        filaPlantilla(['serial' => 'MXL0002', 'codigo_activo' => 'I1-024148', 'responsable_documento' => '1000000002', 'responsable_nombres' => 'Luis', 'responsable_apellidos' => 'Gómez', 'responsable_cargo' => '219 · Profesional Universitario', 'responsable_vinculacion' => 'Contratista']),
    ]))->call('confirmar')->assertHasNoErrors();

    $nueva = Persona::where('numero_documento', '1000000002')->sole();

    expect(Equipo::where('serial', 'MXL0001')->first()->asignacionActual->persona_id)->toBe($existente->id)
        ->and($nueva->nombreCompleto())->toBe('Luis Gómez')
        ->and($nueva->cargoOficial->codigo)->toBe('219')
        ->and($nueva->tipo_vinculacion)->toBe('contratista')
        ->and($nueva->dependencia_id)->toBe($this->tic->id)
        ->and(Equipo::where('serial', 'MXL0002')->first()->estado_ciclo_vida)->toBe('en_servicio');
});

it('la simulación hace todo y no deja nada guardado (RF-38)', function () {
    cargaEquipos(plantillaCsv([filaPlantilla()]))
        ->call('simular')
        ->assertHasNoErrors()
        ->assertSet('simulado', true)
        ->assertSet('resumen.creados', 1);

    expect(Equipo::count())->toBe(0)
        ->and(Evento::count())->toBe(0)
        ->and(Importacion::count())->toBe(0);
});

it('un archivo que no es la plantilla se rechaza con un mensaje claro', function () {
    $archivo = UploadedFile::fake()->createWithContent('otra-cosa.csv', "\"columna\",\"otra\"\n\"1\",\"2\"\n");

    cargaEquipos($archivo)->assertHasErrors('archivo');
});

it('registra los periféricos del PC y los ignora en un equipo que no es de cómputo', function () {
    TipoComponente::create(['nombre' => 'Teclado', 'es_periferico' => true]);
    TipoComponente::create(['nombre' => 'Mouse', 'es_periferico' => true]);

    $carga = cargaEquipos(plantillaCsv([
        filaPlantilla(['perifericos' => 'Teclado, Mouse y Parlante']),
        filaPlantilla(['tipo_equipo' => 'Monitor', 'serial' => 'MON-1', 'codigo_activo' => 'I1-024148', 'perifericos' => 'Teclado']),
    ]));

    expect($carga->get('resultados.0.advertencias'))->toContain('El periférico «Parlante» no está en el catálogo: se omite.')
        ->and($carga->get('resultados.1.advertencias'))->toContain('Los periféricos solo se registran en equipos de cómputo: se omiten.');

    $carga->call('confirmar')->assertHasNoErrors();

    expect(Equipo::where('serial', 'MXL0001')->first()->componentes()->with('tipoComponente')->get()->pluck('tipoComponente.nombre')->sort()->values()->all())->toBe(['Mouse', 'Teclado'])
        ->and(Equipo::where('serial', 'MON-1')->first()->componentes()->count())->toBe(0);
});

it('un texto más largo que su columna da error en la fila y no tumba la carga', function () {
    $carga = cargaEquipos(plantillaCsv([
        filaPlantilla(['modelo' => str_repeat('M', 101)]),
        filaPlantilla(['serial' => 'MXL0002', 'codigo_activo' => 'I1-024148']),
    ]));

    expect($carga->get('resultados.0.errores'))->toContain('Modelo tiene 101 caracteres: el máximo es 100.');

    $carga->call('confirmar')->assertHasNoErrors();

    expect(Equipo::pluck('serial')->all())->toBe(['MXL0002']);
});

it('volver a cargar el mismo archivo no duplica los equipos sin serial ni código', function () {
    $archivo = fn () => plantillaCsv([filaPlantilla(['serial' => '', 'codigo_activo' => ''])]);

    cargaEquipos($archivo())->call('confirmar')->assertHasNoErrors();
    $primero = Equipo::sole();

    $otraVez = cargaEquipos($archivo());

    expect($otraVez->get('resultados.0.errores'))->toContain("Esta fila ya se cargó de «carga-equipos.csv» (equipo N.º {$primero->numero_activo}): no se vuelve a crear.");

    $otraVez->call('confirmar');

    expect(Equipo::count())->toBe(1);
});

it('un responsable sin documento se crea una sola vez y se reutiliza en la siguiente carga', function () {
    $sinCedula = ['responsable_nombres' => 'Juan', 'responsable_apellidos' => 'Pérez'];

    cargaEquipos(plantillaCsv([
        filaPlantilla($sinCedula),
        filaPlantilla(['tipo_equipo' => 'Monitor', 'serial' => 'MON-1', 'codigo_activo' => 'I1-024148'] + $sinCedula),
    ]))->call('confirmar')->assertHasNoErrors();

    cargaEquipos(plantillaCsv([
        filaPlantilla(['serial' => 'MXL0003', 'codigo_activo' => 'I1-024149'] + $sinCedula),
    ]))->call('confirmar')->assertHasNoErrors();

    expect(Equipo::count())->toBe(3)
        ->and(Persona::count())->toBe(1)
        ->and(Asignacion::whereNotNull('persona_id')->distinct()->pluck('persona_id')->all())->toBe([Persona::sole()->id]);
});
