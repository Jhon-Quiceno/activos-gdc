<?php

use App\Livewire\Importacion\Index;
use App\Models\Componente;
use App\Models\ConfiguracionComputo;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Importacion;
use App\Models\Marca;
use App\Models\Piso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Importación del inventario 2026 desde Excel (RF-38 a RF-41; RN-02, RN-03, RN-11).
 *
 * Usamos CSV (uno de los formatos aceptados por el validador: xlsx,xls,csv) para no
 * depender de binarios .xlsx en el repo: Maatwebsite\Excel lee ambos igual y el
 * componente no distingue el formato de origen.
 */
uses(RefreshDatabase::class);

/**
 * Construye el contenido CSV que Excel::toArray() va a parsear. Los encabezados deben
 * quedar en el mismo formato "humano" que trae el inventario real: Index::clave()
 * normaliza (minúsculas, sin tildes, sin signos) antes de buscar los alias.
 */
function csvInventario(array $encabezados, array $filas): UploadedFile
{
    $linea = fn (array $valores) => implode(',', array_map(
        fn ($valor) => '"'.str_replace('"', '""', (string) $valor).'"',
        $valores
    ));

    $contenido = implode("\n", array_merge([$linea($encabezados)], array_map($linea, $filas)))."\n";

    return UploadedFile::fake()->createWithContent('inventario.csv', $contenido);
}

beforeEach(function () {
    $this->usuario = User::factory()->create();

    // Catálogos mínimos: igual que en el inventario real, dejamos solo UNA opción
    // oficial por campo para poder detectar cuando algo no calza con ninguna.
    $this->pc = TipoEquipo::create(['nombre' => 'PC de Escritorio', 'familia' => 'computo']);
    TipoEquipo::create(['nombre' => 'Monitor', 'familia' => 'video']);
    TipoEquipo::create(['nombre' => 'Impresora', 'familia' => 'impresion']);
    TipoEquipo::create(['nombre' => 'Otro', 'familia' => 'energia']);

    $this->marca = Marca::create(['nombre' => 'HP']);
    $this->sede = Sede::create(['nombre' => 'Palacio Naín']);
    $this->piso = Piso::create(['numero' => 3]);
    $this->dependencia = Dependencia::create(['nombre' => 'Dirección TIC']);

    // Encabezados comunes a la mayoría de los casos: un bloque PC, un bloque Monitor,
    // un bloque Impresora y los tres periféricos, más ubicación.
    $this->encabezados = [
        'PC', 'PC Codigo', 'PC Serial', 'PC Marca', 'PC Modelo', 'PC Estado',
        'Monitor', 'Monitor Codigo', 'Monitor Serial',
        'Impresora', 'Impresora Codigo',
        'Teclado', 'Mouse', 'Sonido',
        'Sede', 'Piso', 'Dependencia',
        'Observaciones', 'Propietario',
    ];
});

/**
 * Fila base con los tres bloques (pc, monitor, impresora) y ubicación válida; los
 * campos se pueden sobreescribir por posición usando los índices de $this->encabezados.
 */
function filaBase(array $cambios = []): array
{
    $base = [
        'PC' => '', 'PC Codigo' => '', 'PC Serial' => 'PC-SN-0001', 'PC Marca' => 'HP', 'PC Modelo' => '', 'PC Estado' => 'Bueno',
        'Monitor' => 'Si', 'Monitor Codigo' => '', 'Monitor Serial' => 'MON-SN-0001',
        'Impresora' => 'Si', 'Impresora Codigo' => '',
        'Teclado' => 'TEC-0001', 'Mouse' => 'MOU-0001', 'Sonido' => 'SON-0001',
        'Sede' => 'Palacio Naín', 'Piso' => '3', 'Dependencia' => 'Dirección TIC',
        'Observaciones' => '', 'Propietario' => '',
    ];

    return array_values(array_merge($base, $cambios));
}

it('divide una fila con pc, monitor e impresora en tres equipos distintos (RF-38)', function () {
    $archivo = csvInventario($this->encabezados, [filaBase()]);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    expect($testable->filas)->toHaveCount(1)
        ->and($testable->filas[0]['equipos'])->toHaveCount(3)
        ->and($testable->kpis['equipos_detectados'])->toBe(3)
        ->and($testable->filas[0]['numero'])->toBe(2); // fila 1 = encabezado

    $testable->call('confirmar')->assertHasNoErrors();

    expect(Equipo::count())->toBe(3)
        ->and(Equipo::distinct()->pluck('fila_origen_importacion')->all())->toBe([2])
        // RF-38: cada evento de alta pasa por HistorialService, nunca se inserta a mano.
        ->and(Evento::where('tipo', 'alta')->count())->toBe(3)
        ->and(Evento::where('usuario_id', $this->usuario->id)->count())->toBe(3);
});

it('excluye los equipos marcados como personales y no los cuenta como detectados (RN-01)', function () {
    $filas = [
        filaBase(),
        filaBase(['PC Serial' => 'PC-SN-0002', 'Monitor' => '', 'Impresora' => '', 'Observaciones' => 'Es personal, no es de la Gobernación']),
    ];
    $archivo = csvInventario($this->encabezados, $filas);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    expect($testable->filas)->toHaveCount(1) // la fila personal no entra a la vista previa ni se arma en equipos
        ->and($testable->kpis['filas_leidas'])->toBe(2) // cuenta las filas leídas del archivo, incluida la personal
        ->and($testable->kpis['excluidos'])->toBe(1);

    $testable->call('confirmar')->assertHasNoErrors();

    expect(Equipo::count())->toBe(3); // solo los de la fila no-personal
});

it('normaliza el codigo de activo quitando espacios, guiones y cambiando L1 por I1 (RN-03, RF-39)', function (string $escrito, string $esperado) {
    $archivo = csvInventario($this->encabezados, [
        filaBase(['PC Codigo' => $escrito, 'Monitor' => '', 'Impresora' => '']),
    ]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    expect(Equipo::sole()->codigo_activo)->toBe($esperado);
})->with([
    'espacios sueltos' => [' I1 24147 ', 'I1-024147'],
    'L1 en vez de I1' => ['L1-24148', 'I1-024148'],
    'sin guion ni espacios' => ['i124149', 'I1-024149'],
]);

it('un codigo de activo marcado como N/A o no legible queda vacio, no literal (RN-03)', function (string $escrito) {
    $archivo = csvInventario($this->encabezados, [
        filaBase(['PC Codigo' => $escrito, 'Monitor' => '', 'Impresora' => '']),
    ]);

    Livewire::actingAs($this->usuario)->test(Index::class)
        ->set('archivo', $archivo)
        ->call('confirmar')
        ->assertHasNoErrors();

    expect(Equipo::sole()->codigo_activo)->toBeNull();
})->with(['N/A', 'No se ve', 'No legible', 'Sin codigo']);

it('una fila sin serial principal queda pendiente de verificar en vez de bloquear la carga (RN-02, D-06)', function () {
    $archivo = csvInventario($this->encabezados, [
        filaBase(['PC Serial' => '', 'Monitor' => '', 'Impresora' => '']),
    ]);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    // RF-40: la advertencia se ve en la vista previa ANTES de confirmar.
    expect(collect($testable->instance()->advertenciasPorFila())->pluck('tipo'))->toContain('sin_serial');

    $testable->call('confirmar')->assertHasNoErrors();

    $equipo = Equipo::sole();
    expect($equipo->verificacion)->toBe('pendiente_de_verificar')
        ->and($equipo->serial)->toStartWith('PENDIENTE-2-');
});

it('detecta un codigo de activo duplicado entre dos equipos del mismo lote antes de confirmar (RN-03, RF-40)', function () {
    $filas = [
        filaBase(['PC Serial' => 'PC-SN-0010', 'PC Codigo' => 'I1-030001', 'Monitor' => '', 'Impresora' => '']),
        filaBase(['PC Serial' => 'PC-SN-0011', 'PC Codigo' => 'I1-030001', 'Monitor' => '', 'Impresora' => '']),
    ];
    $archivo = csvInventario($this->encabezados, $filas);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    // Vista previa: el duplicado debe advertirse dentro del MISMO lote, no solo
    // contra lo que ya existe en la base (todavía no hay nada persistido aquí).
    $tipos = collect($testable->instance()->advertenciasPorFila())->pluck('tipo');
    expect($tipos)->toContain('repetido')
        ->and(Equipo::count())->toBe(0); // nada se persiste solo por previsualizar
});

it('bloquea la confirmacion si dos equipos no-AllInOne comparten codigo de activo, sin romper con un error de base de datos (RN-03)', function () {
    $filas = [
        filaBase(['PC Serial' => 'PC-SN-0020', 'PC Codigo' => 'I1-030002', 'Monitor' => '', 'Impresora' => '']),
        filaBase(['PC Serial' => 'PC-SN-0021', 'PC Codigo' => 'I1-030002', 'Monitor' => '', 'Impresora' => '']),
    ];
    $archivo = csvInventario($this->encabezados, $filas);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    $testable->call('confirmar')->assertHasErrors('archivo');

    // Nada debe quedar a medias: ni los equipos, ni el registro de importación.
    expect(Equipo::count())->toBe(0)
        ->and(Importacion::count())->toBe(0);
});

it('bloquea la confirmacion con un mensaje claro si el codigo ya existe en un equipo fuera del archivo (RN-03)', function () {
    // Caso NO cubierto por codigosRepetidosEnLote(): el duplicado no está
    // dentro del mismo archivo, sino contra un equipo ya persistido antes de
    // esta importación. Sin captura explícita, Equipo::create() deja pasar
    // la ValidationException del modelo sin contexto de fila.
    Equipo::factory()->create(['codigo_activo' => 'I1-030099', 'tipo_equipo_id' => $this->pc->id]);

    $archivo = csvInventario($this->encabezados, [
        filaBase(['PC Serial' => 'PC-SN-0030', 'PC Codigo' => 'I1-030099', 'Monitor' => '', 'Impresora' => '']),
    ]);

    $testable = Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    $testable->call('confirmar')->assertHasErrors('archivo');

    // Rollback completo: ni el nuevo equipo ni la importación quedan a medias.
    expect(Equipo::count())->toBe(1) // solo el que ya existía antes
        ->and(Importacion::count())->toBe(0);
});

it('no persiste nada al leer el archivo: solo al confirmar explicitamente (RF-40)', function () {
    $archivo = csvInventario($this->encabezados, [filaBase()]);

    Livewire::actingAs($this->usuario)->test(Index::class)->set('archivo', $archivo);

    expect(Equipo::count())->toBe(0)
        ->and(Componente::count())->toBe(0)
        ->and(ConfiguracionComputo::count())->toBe(0)
        ->and(Importacion::count())->toBe(0)
        ->and(Evento::count())->toBe(0);
});

it(
    'hallazgo: una dependencia o sede fuera de la lista oficial no produce un error claro, '
    .'se asocia por aproximacion de texto al unico catalogo existente (RN-11, sin corregir: requiere decidir un umbral)',
    function () {
        // Catálogo real: solo existe UNA sede y UNA dependencia (las del beforeEach).
        // El Excel real trae 175 variantes de dependencia (D-07) que no siempre se
        // acercan a la oficial; aquí usamos un valor sin relación alguna.
        $archivo = csvInventario($this->encabezados, [
            filaBase([
                'Sede' => 'Base Espacial Marciana',
                'Dependencia' => 'Ministerio de Asuntos Inexistentes',
                'Monitor' => '', 'Impresora' => '',
            ]),
        ]);

        Livewire::actingAs($this->usuario)->test(Index::class)
            ->set('archivo', $archivo)
            ->call('confirmar')
            ->assertHasNoErrors(); // RN-11 esperaría un error claro; hoy no lo hay.

        $equipo = Equipo::sole();

        // Index::sugerencia() hace match por distancia de Levenshtein SIN umbral: como
        // solo hay una sede y una dependencia en el catálogo, cualquier texto —sin
        // importar cuán distinto sea— termina asociado a esa única opción, en silencio.
        expect($equipo->puesto_trabajo_id)->not->toBeNull();

        $puesto = PuestoTrabajo::find($equipo->puesto_trabajo_id);
        expect($puesto->sede_id)->toBe($this->sede->id)
            ->and($puesto->dependencia_id)->toBe($this->dependencia->id);
    }
);
