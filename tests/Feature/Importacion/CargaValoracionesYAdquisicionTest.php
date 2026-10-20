<?php

use App\Livewire\Importacion\Index;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\ValoracionActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Carga de valoraciones desde la matriz FOR.01 (GT-M4) y de datos de
 * adquisición desde Almacén (RF-52, §12). Archivos sintéticos.
 */
uses(RefreshDatabase::class);

function csvDe(string $nombre, array $filas): UploadedFile
{
    $linea = fn (array $valores) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $valores));

    return UploadedFile::fake()->createWithContent($nombre, implode("\n", array_map($linea, $filas))."\n");
}

/** Matriz FOR.01 con el mismo acomodo del formato oficial: encabezados en tres filas. */
function for01(array $filas): UploadedFile
{
    $arriba = array_fill(0, 24, '');
    [$arriba[21], $arriba[22], $arriba[23]] = ['Confidencialidad', 'Integridad', 'Disponibilidad'];

    $encabezado = ['ID ACTIVO', 'Sucursal', 'Proceso', 'Area o Dependencia', 'Nombre Activo', 'Descripcion Activo', 'Tipo de Activo',
        'Contiene datos Personales', 'Clasificacion Datos Personales', 'Infraestructura Crítica', 'Continuidad del Negocio',
        'Propietario del Activo (Cargo)', 'Custodio Técnico del Activo (Cargo)', 'Tipo Ubicación del Activo', 'Tiempo de Retención de los Datos (años)',
        'CONFIDENCIALIDAD', '', 'INTEGRIDAD', '', 'DISPONIBILIDAD', '', '', '', ''];

    $subtitulos = array_fill(0, 24, '');
    [$subtitulos[15], $subtitulos[16], $subtitulos[17], $subtitulos[18], $subtitulos[19], $subtitulos[20]] = ['Escenario (C)', 'Respuesta', 'Escenario (I)', 'Respuesta', 'Escenario (D)', 'Respuesta'];

    $datos = array_map(fn (array $f) => [
        $f['id'], 'Palacio NAIN', 'Gestión Tecnológica', 'DIRECION DE SISTEMAS', 'Equipo', '', 'Hardware',
        $f['datos'] ?? 'NO', $f['clasificacion'] ?? '', 'NO', 'SI', 'Director de Sistemas', 'Ingeniero de Soporte', 'Físico', 'Indeterminado',
        '¿Qué pasaría…?', $f['rc'] ?? '', '¿Qué pasaría…?', $f['ri'] ?? '', '¿Cuánto tiempo…?', $f['rd'] ?? '',
        $f['c'] ?? '', $f['i'] ?? '', $f['d'] ?? '',
    ], $filas);

    return csvDe('FOR01-sintetico.csv', [['PROCESO GESTIÓN DE SEGURIDAD DE LA INFORMACION'], $arriba, $encabezado, $subtitulos, ...$datos]);
}

function cargar(string $tipo, UploadedFile $archivo)
{
    return Livewire::actingAs(test()->usuario)->test(Index::class)->set('tipo', $tipo)->set('archivo', $archivo);
}

beforeEach(function () {
    $this->usuario = User::factory()->create();
    $marca = Marca::create(['nombre' => 'HP']);
    $pc = TipoEquipo::create(['nombre' => 'PC de escritorio', 'familia' => 'computo', 'tipo_activo' => 'hardware']);
    $ups = TipoEquipo::create(['nombre' => 'UPS', 'familia' => 'energia', 'tipo_activo' => 'soporte']);

    $this->pc = Equipo::create(['serial' => 'SN-PC', 'codigo_activo' => 'I1-024147', 'tipo_equipo_id' => $pc->id, 'marca_id' => $marca->id, 'propiedad' => 'gobernacion'])->fresh();
    $this->ups = Equipo::create(['serial' => 'SN-UPS', 'codigo_activo' => 'I1-024148', 'tipo_equipo_id' => $ups->id, 'marca_id' => $marca->id, 'propiedad' => 'gobernacion'])->fresh();
});

// --- Valoraciones (FOR.01) ---

it('crea la valoración del FOR.01 con su evento, cruzando por ID ACTIVO', function () {
    cargar('valoraciones', for01([['id' => $this->pc->numero_activo, 'c' => '4', 'i' => '2', 'd' => '2']]))
        ->assertSet('resultados.0.errores', [])
        ->call('confirmar')
        ->assertHasNoErrors();

    $valoracion = ValoracionActivo::sole();

    expect($valoracion->equipo_id)->toBe($this->pc->id)
        ->and($valoracion->total)->toBe(8)
        ->and($valoracion->importancia)->toBe('alta')
        ->and($valoracion->requiere_gestion_riesgo)->toBeTrue()
        ->and($valoracion->propietario_cargo)->toBe('Director de Sistemas')
        ->and($valoracion->evento->tipo)->toBe('valoracion');
});

it('sin columnas numéricas pasa la respuesta en texto a 1–4', function () {
    $carga = cargar('valoraciones', for01([[
        'id' => $this->pc->numero_activo,
        'rc' => 'Daño inestimable o irreversible. Se podría afectar la imagen de la organización',
        'ri' => 'Actividades, servicios o personas. vulneración de su integridad afecta',
        'rd' => 'Activo puede estar indisponible por un día. Su carencia afectaría considerablemente',
    ]]));

    expect($carga->get('resultados.0.valores.datos'))->toMatchArray(['confidencialidad' => 4, 'integridad' => 2, 'disponibilidad' => 2]);
});

it('una fila sin equipo, un activo de soporte o datos personales sin clasificar dan error de fila', function () {
    $carga = cargar('valoraciones', for01([
        ['id' => 99999, 'c' => '1', 'i' => '1', 'd' => '1'],
        ['id' => $this->ups->numero_activo, 'c' => '1', 'i' => '1', 'd' => '1'],
        ['id' => $this->pc->numero_activo, 'c' => '1', 'i' => '1', 'd' => '1', 'datos' => 'SI'],
    ]));

    expect($carga->get('resultados.0.errores')[0])->toContain('No hay ningún equipo')
        ->and(implode(' ', $carga->get('resultados.1.errores')))->toContain('RN-25')
        ->and(implode(' ', $carga->get('resultados.2.errores')))->toContain('RN-24');

    $carga->call('confirmar');

    expect(ValoracionActivo::count())->toBe(0);
});

// --- Adquisición (Almacén) ---

it('actualiza la adquisición cruzando por código y deja el antes y después en el historial', function () {
    $carga = cargar('adquisicion', csvDe('almacen.csv', [
        ['Código de activo', 'Fecha de adquisición', 'Valor', 'Proveedor', 'NIT', 'Contrato', 'Factura'],
        ['I1 024147', '15/03/2024', '$ 2.350.000', 'Tecnología del Sinú S.A.S.', '900123456', 'CD-045-2024', 'FE-9981'],
        ['I1-999999', '15/03/2024', '1000', '', '', '', ''],
    ]));

    expect($carga->get('resultados.1.errores'))->toContain('No cruza: ningún equipo tiene el código I1-999999.');

    $carga->call('confirmar')->assertHasNoErrors()->assertSet('resumen.actualizados', 1)->assertSet('resumen.proveedores_nuevos', 1);

    $equipo = $this->pc->fresh();
    $evento = Evento::sole();

    expect($equipo->valor_adquisicion)->toBe('2350000.00')
        ->and($equipo->fecha_adquisicion->toDateString())->toBe('2024-03-15')
        ->and($equipo->proveedor->nit)->toBe('900123456')
        ->and($evento->tipo)->toBe('actualizacion_datos')
        ->and($evento->valores['despues'])->toMatchArray(['valor_adquisicion' => '$ 2.350.000', 'fecha_adquisicion' => '15/03/2024', 'numero_contrato' => 'CD-045-2024'])
        ->and($evento->valores['antes']['valor_adquisicion'])->toBeNull();
});

it('una celda vacía no borra lo que ya tenía el equipo y sin cambios no hay evento', function () {
    $proveedor = Proveedor::create(['razon_social' => 'Tecnología del Sinú S.A.S.']);
    $this->pc->update(['numero_contrato' => 'CD-001', 'valor_adquisicion' => 1000000, 'proveedor_id' => $proveedor->id]);

    cargar('adquisicion', csvDe('almacen.csv', [
        ['Código de activo', 'Valor', 'Contrato', 'Proveedor'],
        ['I1-024147', '1.000.000', '', 'Tecnología del Sinú S.A.S.'],
    ]))->call('confirmar')->assertSet('resumen.sin_cambios', 1);

    expect($this->pc->fresh()->numero_contrato)->toBe('CD-001')
        ->and(Evento::count())->toBe(0);
});
