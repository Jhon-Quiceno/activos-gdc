<?php

use App\Livewire\Importacion\Index;
use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Importacion;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Carga de personas desde la planta (RF-55, RNF-19, RN-26). Las plantas reales
 * nunca se usan en pruebas: estos archivos son sintéticos, con las mismas
 * cabeceras y columnas sensibles inventadas.
 */
uses(RefreshDatabase::class);

/** Planta sintética: títulos arriba, encabezados y columnas sensibles como en el archivo real. */
function plantaCsv(array $filas): UploadedFile
{
    $encabezados = ['No', 'PLANTA', 'SEXO', 'CEDULA', 'NOMBRE', 'DEPENDENCIA', 'REAL UBICACIÓN', 'CARGO', 'ACTIVOS, VACANTES Y ENCARGOS', 'SIT.', 'CODIGO', 'GRADO', 'SALARIO', 'RH', 'DIRECCION RESIDENCIA', 'ESTADO CIVIL', 'CORREO PERSONAL', 'CORREO INSTITUCIONAL', 'JEFE INMEDIATO'];
    $linea = fn (array $valores) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $valores));

    $contenido = $linea(['GOBERNACION DE CORDOBA'])."\n".$linea(['PLANTA CENTRAL'])."\n".$linea($encabezados)."\n";
    foreach ($filas as $fila) {
        $contenido .= $linea(array_map(fn ($e) => $fila[$e] ?? '', $encabezados))."\n";
    }

    return UploadedFile::fake()->createWithContent('planta-sintetica.csv', $contenido);
}

function filaPlanta(array $cambios = []): array
{
    return array_merge([
        'No' => '1',
        'PLANTA' => 'Central',
        'SEXO' => 'F',
        'CEDULA' => '1000000001',
        'NOMBRE' => 'Ana María Pérez Díaz',
        'DEPENDENCIA' => 'Secretaría de Gestión Administrativa',
        'REAL UBICACIÓN' => 'Dirección TICS',
        'CARGO' => 'Profesional Universitario (E)',
        'ACTIVOS, VACANTES Y ENCARGOS' => 'Encargo',
        'SIT.' => 'C.A',
        'CODIGO' => '219.0',
        'GRADO' => '7.0',
        'SALARIO' => '7798826',
        'RH' => 'O+',
        'DIRECCION RESIDENCIA' => 'Calle Falsa 123',
        'ESTADO CIVIL' => 'Soltera',
        'CORREO PERSONAL' => 'personal@example.com',
        'CORREO INSTITUCIONAL' => 'ana.perez@cordoba.gov.co',
        'JEFE INMEDIATO' => '',
    ], $cambios);
}

function cargaPlanta(UploadedFile $archivo, string $orden = 'nombres_apellidos')
{
    return Livewire::actingAs(test()->usuario)->test(Index::class)
        ->set('tipo', 'personas')
        ->set('ordenNombre', $orden)
        ->set('archivo', $archivo);
}

beforeEach(function () {
    $this->usuario = User::factory()->create();
    $this->tic = Dependencia::create(['nombre' => 'Dirección de Tecnología, Informática y Comunicaciones (TICS)', 'nombre_corto' => 'Dirección TICS']);
    Cargo::create(['codigo' => '219', 'denominacion' => 'Profesional Universitario', 'nivel' => 'profesional']);
    Cargo::create(['codigo' => '407', 'denominacion' => 'Auxiliar Administrativo', 'nivel' => 'asistencial']);
});

it('una planta con salario, RH, dirección y estado civil no guarda ninguno (comprobado en la base)', function () {
    $carga = cargaPlanta(plantaCsv([filaPlanta()]));

    // Tampoco llegan al navegador: el estado del componente no los trae.
    $estado = json_encode($carga->get('resultados'));
    foreach (['7798826', 'O+', 'Calle Falsa 123', 'Soltera', 'personal@example.com'] as $sensible) {
        expect($estado)->not->toContain($sensible);
    }

    $carga->call('confirmar')->assertHasNoErrors();

    // Ninguna columna de ninguna tabla relacionada guarda esos valores.
    $fila = (array) DB::table('personas')->sole();
    $importacion = json_encode(Importacion::sole()->toArray());
    foreach (['7798826', 'O+', 'Calle Falsa 123', 'Soltera', 'personal@example.com'] as $sensible) {
        expect(in_array($sensible, $fila, true))->toBeFalse()
            ->and($importacion)->not->toContain($sensible);
    }
});

it('guarda solo los datos de RF-18 con la dependencia real, el cargo por código y el encargo', function () {
    cargaPlanta(plantaCsv([filaPlanta()]))->call('confirmar');

    $persona = Persona::sole();

    expect($persona->numero_documento)->toBe('1000000001')
        ->and($persona->nombres)->toBe('Ana María')
        ->and($persona->apellidos)->toBe('Pérez Díaz')
        ->and($persona->cargoTexto())->toBe('Profesional Universitario (E)')
        ->and($persona->grado)->toBe('07')
        ->and($persona->dependencia_id)->toBe($this->tic->id)
        ->and($persona->planta)->toBe('central')
        ->and($persona->tipo_vinculacion)->toBe('planta')
        ->and($persona->tipo_provision)->toBe('carrera_administrativa')
        ->and($persona->correo_institucional)->toBe('ana.perez@cordoba.gov.co');
});

it('el nombre en mayúsculas con apellidos primero se separa bien', function () {
    cargaPlanta(plantaCsv([filaPlanta(['NOMBRE' => 'PÉREZ DÍAZ ANA MARÍA'])]), 'apellidos_nombres')->call('confirmar');

    expect(Persona::sole()->nombres)->toBe('Ana María')
        ->and(Persona::sole()->apellidos)->toBe('Pérez Díaz');
});

it('los cargos vacantes se omiten sin error', function () {
    $carga = cargaPlanta(plantaCsv([
        filaPlanta(),
        filaPlanta(['CEDULA' => '', 'NOMBRE' => '', 'ACTIVOS, VACANTES Y ENCARGOS' => 'Vacante']),
    ]));

    expect($carga->get('resultados.1.omitida'))->toBeTrue()
        ->and($carga->get('resultados.1.errores'))->toBe([]);

    $carga->call('confirmar');

    expect(Persona::count())->toBe(1);
});

it('una dependencia escrita con su nombre anterior se resuelve por equivalencia', function () {
    DependenciaEquivalencia::create(['dependencia_id' => $this->tic->id, 'texto' => 'Dirección técnica de sistemas', 'origen' => 'Decreto 609']);

    cargaPlanta(plantaCsv([filaPlanta(['REAL UBICACIÓN' => 'DIRECCIÓN TÉCNICA DE SISTEMAS'])]))->call('confirmar');

    expect(Persona::sole()->dependencia_id)->toBe($this->tic->id);
});

it('actualiza a la persona que ya existe y enlaza el jefe inmediato por su nombre', function () {
    Persona::create(['nombres' => 'Ana', 'numero_documento' => '1000000001']);

    cargaPlanta(plantaCsv([
        filaPlanta(['JEFE INMEDIATO' => 'Luis Gómez Ruiz']),
        filaPlanta(['CEDULA' => '1000000002', 'NOMBRE' => 'Luis Gómez Ruiz', 'CARGO' => 'Auxiliar Administrativo', 'CODIGO' => '407']),
    ]))->call('confirmar')->assertSet('resumen.creadas', 1)->assertSet('resumen.actualizadas', 1)->assertSet('resumen.jefes_enlazados', 1);

    $ana = Persona::where('numero_documento', '1000000001')->sole();

    expect($ana->nombres)->toBe('Ana María')
        ->and($ana->jefeInmediato->nombreCompleto())->toBe('Luis Gómez Ruiz');
});

it('un documento repetido en el archivo da error de fila', function () {
    $carga = cargaPlanta(plantaCsv([filaPlanta(), filaPlanta(['NOMBRE' => 'Otra Persona'])]));

    expect($carga->get('resultados.1.errores'))->toContain('El documento está repetido en el archivo (fila 4).');
});

it('volver a cargar la planta no borra lo que la nueva no trae o no reconoce', function () {
    $tic = $this->tic;
    $persona = Persona::create([
        'numero_documento' => '1000000001', 'nombres' => 'Ana María', 'apellidos' => 'Pérez Díaz',
        'dependencia_id' => $tic->id, 'correo_institucional' => 'ana.perez@cordoba.gov.co',
    ]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('tipo', 'personas')
        ->set('archivo', plantaCsv([filaPlanta(['DEPENDENCIA' => 'Oficina desconocida', 'REAL UBICACIÓN' => '', 'CORREO INSTITUCIONAL' => ''])]))
        ->call('confirmar')
        ->assertHasNoErrors()
        ->assertSet('resumen.actualizadas', 1);

    $persona->refresh();

    expect($persona->dependencia_id)->toBe($tic->id)
        ->and($persona->correo_institucional)->toBe('ana.perez@cordoba.gov.co')
        ->and($persona->grado)->toBe('07');
});

it('con la situación vacía, una fila con nombre y sin documento es un error y no un cargo vacante', function () {
    $carga = Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('tipo', 'personas')
        ->set('archivo', plantaCsv([filaPlanta(['CEDULA' => '', 'ACTIVOS, VACANTES Y ENCARGOS' => ''])]));

    expect($carga->get('resultados.0.omitida'))->toBeFalse()
        ->and($carga->get('resultados.0.errores'))->toContain('Falta el número de documento.');
});
