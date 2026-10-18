<?php

use App\Models\Asignacion;
use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Marca;
use App\Models\ModeloEquipo;
use App\Models\Municipio;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\UbicacionDependencia;
use App\Models\User;
use App\Models\ValoracionActivo;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

/*
 * Núcleo del modelo de datos v4 (docs/base-de-datos.md): catálogos oficiales,
 * árbol de dependencias (RN-20, RF-60), migración en dos tiempos (campos viejos
 * sincronizados), número de activo (RN-27), ubicación por piso (RN-21) y
 * valoración GT-M4 (RF-56 a RF-58, RN-22 a RN-25).
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    (new CatalogosSeeder)->run();
    $this->usuario = User::factory()->create();
});

function dependencia(string $nombre): Dependencia
{
    return Dependencia::where('nombre', $nombre)->firstOrFail();
}

function equipoDeTipo(string $tipo, array $atributos = []): Equipo
{
    return Equipo::factory()->create(array_merge([
        'tipo_equipo_id' => TipoEquipo::where('nombre', $tipo)->value('id'),
    ], $atributos));
}

function datosValoracion(array $cambios = []): array
{
    return array_merge([
        'contiene_datos_personales' => 'no',
        'clasificacion_datos' => 'no_aplica',
        'infraestructura_critica' => 'no',
        'continuidad_negocio' => 'no',
        'propietario_cargo' => 'Director Administrativo, Financiero, Técnico u Operativo',
        'custodio_cargo' => 'Ingeniero de Soporte',
        'confidencialidad' => 2,
        'integridad' => 2,
        'disponibilidad' => 2,
    ], $cambios);
}

describe('catálogos oficiales', function () {
    it('siembra las cifras de docs/base-de-datos.md §5', function () {
        expect(Dependencia::count())->toBe(78)
            ->and(Sede::count())->toBe(11)
            ->and(Cargo::count())->toBe(23)
            ->and(Municipio::count())->toBe(30)
            ->and(Proceso::count())->toBe(17)
            ->and(TipoEquipo::count())->toBe(24);
    });

    it('cada piso pertenece a una sede y cada ubicación apunta a un piso real', function () {
        expect(Piso::whereNull('sede_id')->count())->toBe(0);

        $palacio = Sede::where('nombre', 'Palacio de Naín')->firstOrFail();
        expect($palacio->pisos->pluck('numero')->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8]);

        $tics = dependencia('Dirección de Tecnología, Informática y Comunicaciones (TICS)');
        expect($tics->pisos->map(fn (Piso $p) => $p->sede->nombre.' '.$p->numero)->all())->toBe(['Palacio de Naín 6']);
    });

    it('la Casa Refugio tiene la dirección reservada (RN-28)', function () {
        $refugio = Sede::where('nombre', 'Casa Refugio')->firstOrFail();

        expect($refugio->direccion_reservada)->toBeTrue()
            ->and($refugio->direccionVisible())->toBeNull();
    });

    it('los tipos de soporte se distinguen del hardware (GT-M4 tabla 2)', function () {
        expect(TipoEquipo::where('tipo_activo', 'soporte')->pluck('nombre')->sort()->values()->all())
            ->toBe(['Aire acondicionado (cuarto técnico)', 'Planta eléctrica', 'UPS']);
    });
});

describe('árbol de dependencias (RN-20, RF-60)', function () {
    it('una secretaría incluye sus direcciones y áreas a cualquier profundidad', function () {
        $hacienda = dependencia('Secretaría de Hacienda');

        $nombres = Dependencia::whereIn('id', Dependencia::idsDeRama($hacienda->id))->pluck('nombre');

        expect($nombres)->toHaveCount(8)
            ->toContain('Dirección de Rentas', 'Grupo Anticontrabando')
            ->not->toContain('Secretaría de Educación');
    });

    it('ordena el árbol con su profundidad para el selector', function () {
        $arbol = Dependencia::arbol()->keyBy('nombre');

        expect($arbol['Despacho del Gobernador']->profundidad)->toBe(0)
            ->and($arbol['Secretaría de Hacienda']->profundidad)->toBe(1)
            ->and($arbol['Dirección de Rentas']->profundidad)->toBe(2)
            ->and($arbol['Grupo Anticontrabando']->profundidad)->toBe(3);
    });

    it('no permite ciclos en el árbol', function () {
        $hacienda = dependencia('Secretaría de Hacienda');
        $rentas = dependencia('Dirección de Rentas');

        $hacienda->update(['padre_id' => $rentas->id]);
    })->throws(ValidationException::class);

    it('muestra la ruta con los nombres cortos', function () {
        expect(dependencia('Grupo Anticontrabando')->ruta())
            ->toBe('Secretaría de Hacienda › Dirección de Rentas › Grupo Anticontrabando');
    });

    it('resuelve variantes y nombres anteriores al Decreto 609 sin adivinar (RF-39)', function () {
        expect(DependenciaEquivalencia::resolver('DIRECION DE SISTEMAS')?->nombre)
            ->toBe('Dirección de Tecnología, Informática y Comunicaciones (TICS)')
            ->and(DependenciaEquivalencia::resolver('  direccion de rentas ')?->nombre)->toBe('Dirección de Rentas')
            ->and(DependenciaEquivalencia::resolver('SED Nómina')?->nombre)->toBe('Nómina')
            ->and(DependenciaEquivalencia::resolver('Secretaría de Agricultura'))->toBeNull();
    });
});

describe('migración en dos tiempos', function () {
    it('una persona nueva llena también los campos viejos', function () {
        $cargo = Cargo::where('codigo', '219')->firstOrFail();

        $persona = Persona::create([
            'numero_documento' => '1067000001',
            'nombres' => 'Ana María',
            'apellidos' => 'Pérez Gómez',
            'cargo_id' => $cargo->id,
            'tipo_vinculacion' => 'planta',
        ]);

        expect($persona->nombre)->toBe('Ana María Pérez Gómez')
            ->and($persona->cedula)->toBe('1067000001')
            ->and($persona->cargo)->toBe('Profesional Universitario')
            ->and($persona->cargoTexto())->toBe('Profesional Universitario');
    });

    it('el código viejo que escribe nombre y cédula sigue llenando los campos nuevos', function () {
        $persona = Persona::create(['nombre' => 'Juan Pérez', 'cedula' => '123456', 'tipo_vinculacion' => 'contratista']);

        expect($persona->nombres)->toBe('Juan Pérez')
            ->and($persona->numero_documento)->toBe('123456');

        $persona->update(['nombre' => 'Juan Carlos Pérez']);

        expect($persona->fresh()->nombres)->toBe('Juan Carlos Pérez');
    });

    it('la asignación toma la sede del piso (RN-21)', function () {
        $piso = Sede::where('nombre', 'Edificio Morindó')->firstOrFail()->pisos->first();
        $otraSede = Sede::where('nombre', 'Palacio de Naín')->firstOrFail();

        $asignacion = Asignacion::create([
            'equipo_id' => equipoDeTipo('Monitor')->id,
            'sede_id' => $otraSede->id,
            'piso_id' => $piso->id,
            'fecha_inicio' => now()->toDateString(),
        ]);

        expect($asignacion->sede_id)->toBe($piso->sede_id);
    });

    it('modelo y propietario de tercero siguen a sus catálogos', function () {
        $tipo = TipoEquipo::where('nombre', 'Monitor')->firstOrFail();
        $modelo = ModeloEquipo::create(['marca_id' => Marca::where('nombre', 'HP')->value('id'), 'tipo_equipo_id' => $tipo->id, 'nombre' => 'P22v G4']);
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Comodante S.A.S.']);

        $equipo = equipoDeTipo('Monitor', [
            'modelo_id' => $modelo->id,
            'propiedad' => 'tercero',
            'proveedor_tercero_id' => $proveedor->id,
        ]);

        expect($equipo->modelo)->toBe('P22v G4')
            ->and($equipo->propietario_tercero)->toBe('Comodante S.A.S.');
    });
});

describe('número de activo (RN-27)', function () {
    it('se asigna al crear y no cambia nunca', function () {
        $equipo = equipoDeTipo('PC de escritorio');

        expect($equipo->numero_activo)->toBe($equipo->id);

        $equipo->numero_activo = 999;
        $equipo->save();
    })->throws(ValidationException::class);
});

describe('valoración GT-M4 (RF-56 a RF-58)', function () {
    it('calcula total e importancia y registra el evento a nombre del usuario (RN-22)', function () {
        $equipo = equipoDeTipo('Servidor físico');

        $valoracion = ValoracionActivo::registrar($equipo, $this->usuario, datosValoracion([
            'confidencialidad' => 3, 'integridad' => 3, 'disponibilidad' => 3, 'total' => 1, 'importancia' => 'baja',
        ]));

        expect($valoracion->total)->toBe(9)
            ->and($valoracion->importancia)->toBe('alta')
            ->and($valoracion->requiere_gestion_riesgo)->toBeTrue()
            ->and($valoracion->evento->tipo)->toBe('valoracion')
            ->and($valoracion->evento->usuario_id)->toBe($this->usuario->id);
    });

    it('un criterio en 4 manda a gestión de riesgos aunque la importancia sea media (RN-23)', function () {
        $valoracion = ValoracionActivo::registrar(equipoDeTipo('PC de escritorio'), $this->usuario, datosValoracion([
            'confidencialidad' => 4, 'integridad' => 1, 'disponibilidad' => 1,
        ]));

        expect($valoracion->importancia)->toBe('media')
            ->and($valoracion->requiere_gestion_riesgo)->toBeTrue();
    });

    it('sin criterios altos ni importancia alta no va a gestión de riesgos', function () {
        $valoracion = ValoracionActivo::registrar(equipoDeTipo('Monitor'), $this->usuario, datosValoracion());

        expect($valoracion->total)->toBe(6)
            ->and($valoracion->requiere_gestion_riesgo)->toBeFalse();
    });

    it('reclasificar deja una sola valoración vigente y guarda el antes y el después', function () {
        $equipo = equipoDeTipo('PC de escritorio');

        $primera = ValoracionActivo::registrar($equipo, $this->usuario, datosValoracion());
        $segunda = ValoracionActivo::registrar($equipo, $this->usuario, datosValoracion(['disponibilidad' => 4]));

        expect($primera->fresh()->vigente)->toBeFalse()
            ->and($equipo->valoracionVigente->id)->toBe($segunda->id)
            ->and(ValoracionActivo::where('equipo_id', $equipo->id)->where('vigente', true)->count())->toBe(1)
            ->and($segunda->evento->valores['antes']['total'])->toBe(6)
            ->and($segunda->evento->valores['despues']['total'])->toBe(8);
    });

    it('exige clasificar los datos personales cuando los hay, y «no aplica» cuando no (RN-24)', function (array $cambios) {
        ValoracionActivo::registrar(equipoDeTipo('PC de escritorio'), $this->usuario, datosValoracion($cambios));
    })->with([
        'con datos sin clasificar' => [['contiene_datos_personales' => 'si', 'clasificacion_datos' => 'no_aplica']],
        'sin datos pero clasificados' => [['contiene_datos_personales' => 'no', 'clasificacion_datos' => 'sensible']],
    ])->throws(ValidationException::class);

    it('no valora activos de soporte (RN-25)', function () {
        ValoracionActivo::registrar(equipoDeTipo('UPS'), $this->usuario, datosValoracion());
    })->throws(ValidationException::class);

    it('rechaza calificaciones fuera de 1 a 4', function () {
        ValoracionActivo::registrar(equipoDeTipo('PC de escritorio'), $this->usuario, datosValoracion(['integridad' => 5]));
    })->throws(ValidationException::class);

    it('si una regla falla no deja evento suelto en la hoja de vida', function () {
        $equipo = equipoDeTipo('UPS');

        try {
            ValoracionActivo::registrar($equipo, $this->usuario, datosValoracion());
        } catch (ValidationException) {
        }

        expect(Evento::where('equipo_id', $equipo->id)->where('tipo', 'valoracion')->count())->toBe(0);
    });

    it('es histórica: no se edita ni se borra', function () {
        $valoracion = ValoracionActivo::registrar(equipoDeTipo('PC de escritorio'), $this->usuario, datosValoracion());

        expect(fn () => $valoracion->update(['confidencialidad' => 4]))->toThrow(LogicException::class)
            ->and(fn () => $valoracion->delete())->toThrow(LogicException::class);
    });
});

describe('componentes y rutas compartidas', function () {
    // En una petición web Laravel comparte $errors con todas las vistas; aquí se
    // renderiza el componente suelto, así que se comparte a mano.
    beforeEach(fn () => view()->share('errors', new ViewErrorBag));

    it('el selector de dependencias muestra el árbol oficial', function () {
        $html = Blade::render('<x-ui.dependencia-select name="dependenciaId" />');

        expect($html)->toContain('Secretaría de Hacienda')
            ->toContain('Grupo Anticontrabando')
            ->toContain('Buscar dependencia');
    });

    it('el selector de ubicación ofrece los pisos de cada sede y las dependencias del piso', function () {
        $html = Blade::render('<x-ui.ubicacion-select sede="sedeId" piso="pisoId" dependencia="dependenciaId" />');

        expect($html)->toContain('Places Mall')
            ->toContain('Local 44')
            ->toContain('En este piso')
            ->not->toContain('Calle 27 No. 3-28');
    });

    it('la ruta de levantamientos responde con sesión iniciada', function () {
        $this->actingAs($this->usuario)
            ->get(route('levantamientos.index'))
            ->assertOk()
            ->assertSee('Levantamiento de activos');
    });

    it('UbicacionDependencia guarda una sola fila por dependencia y piso', function () {
        $ubicacion = UbicacionDependencia::first();

        UbicacionDependencia::create(['dependencia_id' => $ubicacion->dependencia_id, 'piso_id' => $ubicacion->piso_id]);
    })->throws(UniqueConstraintViolationException::class);
});
