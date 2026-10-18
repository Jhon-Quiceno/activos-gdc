<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Marca;
use App\Models\MotivoBaja;
use App\Models\Municipio;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\PuestoTrabajo;
use App\Models\Sede;
use App\Models\SistemaOperativo;
use App\Models\TipoComponente;
use App\Models\TipoEquipo;
use App\Models\UbicacionDependencia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Catálogos base con los valores OFICIALES del modelo v4 (docs/base-de-datos.md §5):
 * procesos del FOR.01, municipios de Córdoba, sedes y pisos del Programa Anual de
 * Mantenimiento 2026, árbol de dependencias del Decreto 609 de 2023 con sus
 * equivalencias, ubicación de cada dependencia por piso, cargos del Decreto 607
 * y los catálogos del activo. Debe ejecutarse antes que cualquier otro seeder.
 *
 * Lo marcado «por confirmar» en base-de-datos.md se siembra con el supuesto
 * documentado; si cambia, se corrige aquí.
 */
class CatalogosSeeder extends Seeder
{
    /** @var array<string, Dependencia> Dependencias por ruta: «Secretaría de Hacienda/Dirección de Rentas». */
    private array $dependencias = [];

    /** @var array<string, array<int, Piso>> Pisos por nombre de sede y número. */
    private array $pisos = [];

    public function run(): void
    {
        $procesos = $this->procesos();
        $this->municipios();
        $this->sedesYPisos();
        $this->dependencias($procesos);
        $this->equivalencias();
        $this->ubicaciones();
        $this->cargos();
        $this->tiposEquipo();
        $this->marcas();
        $this->sistemasOperativos();
        $this->motivosBaja();
        $this->tiposComponente();
        $this->puestosTrabajo();
    }

    /**
     * @return Collection<string, Proceso>
     */
    private function procesos(): Collection
    {
        // El tipo de cada proceso está por confirmar con el mapa de procesos del SIG.
        return collect([
            ['Planificación Departamental', 'estrategico'],
            ['Gestión de Mejora Continua y Planificación del SIG', 'estrategico'],
            ['Gestión de Comunicaciones', 'estrategico'],
            ['Gestión del Desarrollo Político', 'misional'],
            ['Gestión del Desarrollo Social', 'misional'],
            ['Gestión del Desarrollo Territorial', 'misional'],
            ['Gestión del Riesgo y Desarrollo Ambiental', 'misional'],
            ['Gestión del Desarrollo Económico', 'misional'],
            ['Atención al Ciudadano', 'apoyo'],
            ['Gestión Documental', 'apoyo'],
            ['Contratación', 'apoyo'],
            ['Gestión de Hacienda', 'apoyo'],
            ['Gestión del Talento Humano', 'apoyo'],
            ['Gestión Jurídica', 'apoyo'],
            ['Gestión de Recursos Físicos', 'apoyo'],
            ['Gestión Tecnológica y Sistemas de Información', 'apoyo'],
            ['Evaluación y Control', 'evaluacion_control'],
        ])->mapWithKeys(fn (array $p) => [$p[0] => Proceso::create(['nombre' => $p[0], 'tipo' => $p[1]])]);
    }

    private function municipios(): void
    {
        // DIVIPOLA, departamento 23. Verificar contra la versión vigente del DANE.
        $municipios = [
            '23001' => 'Montería', '23068' => 'Ayapel', '23079' => 'Buenavista', '23090' => 'Canalete',
            '23162' => 'Cereté', '23168' => 'Chimá', '23182' => 'Chinú', '23189' => 'Ciénaga de Oro',
            '23300' => 'Cotorra', '23350' => 'La Apartada', '23417' => 'Lorica', '23419' => 'Los Córdobas',
            '23464' => 'Momil', '23466' => 'Montelíbano', '23500' => 'Moñitos', '23555' => 'Planeta Rica',
            '23570' => 'Pueblo Nuevo', '23574' => 'Puerto Escondido', '23580' => 'Puerto Libertador',
            '23586' => 'Purísima', '23660' => 'Sahagún', '23670' => 'San Andrés de Sotavento',
            '23672' => 'San Antero', '23675' => 'San Bernardo del Viento', '23678' => 'San Carlos',
            '23682' => 'San José de Uré', '23686' => 'San Pelayo', '23807' => 'Tierralta',
            '23815' => 'Tuchín', '23855' => 'Valencia',
        ];

        foreach ($municipios as $codigo => $nombre) {
            Municipio::create(['codigo_dane' => $codigo, 'nombre' => $nombre]);
        }
    }

    private function sedesYPisos(): void
    {
        $monteria = Municipio::where('codigo_dane', '23001')->value('id');

        // [nombre, dirección, barrio, tipo, tenencia, reservada, pisos (número => nombre)]
        $sedes = [
            ['Palacio de Naín', 'Calle 27 No. 3-28', null, 'administrativa', 'propia', false, [1, 2, 3, 4, 5, 6, 7, 8]],
            ['Edificio Morindó', 'Calle 29 No. 2-43', null, 'administrativa', 'por_confirmar', false, [4, 6, 7, 8]],
            ['Edificio Victoria Char', 'Calle 26 No. 3-20', null, 'administrativa', 'por_confirmar', false, [1, 2, 3, 4, 5]],
            ['Places Mall', 'Carrera 6 No. 65-24, Local 44', null, 'administrativa', 'por_confirmar', false, [2 => 'Local 44']],
            ['Sede Secretaría de Cultura', 'Calle 29 No. 5-64', null, 'cultural', 'por_confirmar', false, [2]],
            ['Biblioparque', null, null, 'cultural', 'por_confirmar', false, [2]],
            ['Casa de la Mujer', 'Calle 27 No. 6-49', 'Centro', 'social', 'por_confirmar', false, [1]],
            // RN-28: refugio para mujeres víctimas de violencia; la dirección nunca se muestra.
            ['Casa Refugio', null, 'Costa de Oro', 'social', 'por_confirmar', true, [1]],
            ['Laboratorio de Salud Pública', 'Carrera 15 No. 22-151', 'Costa de Oro', 'salud', 'por_confirmar', false, [1]],
            ['CRUE', 'Calle 42 No. 8-15', null, 'salud', 'por_confirmar', false, [1]],
            // Aparece en la hoja «Datos» del FOR.01 pero no en el PAMP 2026: por confirmar si sigue en uso.
            ['Suricentro', null, null, 'administrativa', 'por_confirmar', false, []],
        ];

        foreach ($sedes as [$nombre, $direccion, $barrio, $tipo, $tenencia, $reservada, $pisos]) {
            $sede = Sede::create([
                'nombre' => $nombre,
                'direccion' => $direccion,
                'barrio' => $barrio,
                'municipio_id' => $monteria,
                'tipo' => $tipo,
                'tenencia' => $tenencia,
                'direccion_reservada' => $reservada,
            ]);

            foreach ($pisos as $clave => $valor) {
                [$numero, $etiqueta] = is_string($valor) ? [$clave, $valor] : [$valor, null];
                $this->pisos[$nombre][$numero] = Piso::create(['sede_id' => $sede->id, 'numero' => $numero, 'nombre' => $etiqueta]);
            }
        }
    }

    /**
     * Árbol del Decreto 609 de 2023 (78 dependencias). Cada nodo hereda el
     * proceso y la planta de su padre salvo que indique otros.
     *
     * @param  Collection<string, Proceso>  $procesos
     */
    private function dependencias(Collection $procesos): void
    {
        $area = fn (string $nombre, array $extra = []) => ['nombre' => $nombre, 'tipo' => 'area'] + $extra;
        $direccion = fn (string $nombre, array $extra = []) => ['nombre' => $nombre, 'tipo' => 'direccion'] + $extra;
        $secretaria = fn (string $nombre, string $proceso, array $hijas = [], array $extra = []) => ['nombre' => $nombre, 'tipo' => 'secretaria', 'proceso' => $proceso, 'hijas' => $hijas] + $extra;
        $sed = fn (string $nombre, string $corto) => ['nombre' => $nombre, 'tipo' => 'area', 'corto' => $corto];

        $arbol = [
            'nombre' => 'Despacho del Gobernador', 'tipo' => 'despacho', 'corto' => 'Despacho', 'proceso' => 'Planificación Departamental',
            'hijas' => [
                ['nombre' => 'Secretaría General', 'tipo' => 'secretaria'],
                ['nombre' => 'Oficina de Asesores', 'tipo' => 'oficina'],
                ['nombre' => 'Oficina Asesora Jurídica', 'tipo' => 'oficina', 'proceso' => 'Gestión Jurídica'],
                ['nombre' => 'Oficina de Control Interno', 'tipo' => 'oficina', 'proceso' => 'Evaluación y Control'],
                ['nombre' => 'Oficina de Control Interno Disciplinario', 'tipo' => 'oficina', 'proceso' => 'Evaluación y Control'],
                $direccion('Dirección de Ambiente y Gestión del Riesgo', ['proceso' => 'Gestión del Riesgo y Desarrollo Ambiental']),
                $direccion('Dirección de Protocolo, Prensa y Comunicaciones', ['proceso' => 'Gestión de Comunicaciones', 'corto' => 'Dirección de Prensa']),
                ['nombre' => 'Departamento Administrativo de Planeación', 'tipo' => 'departamento_administrativo', 'sigla' => 'DAP', 'hijas' => [
                    $direccion('Dirección Departamental de Regalías'),
                    $direccion('Dirección Departamental de Proyectos'),
                ]],
                $secretaria('Secretaría de Hacienda', 'Gestión de Hacienda', [
                    $direccion('Dirección Financiera de Presupuesto'),
                    $direccion('Dirección de Contabilidad'),
                    $direccion('Dirección de Tesorería'),
                    $direccion('Dirección de Rentas', ['hijas' => [$area('Grupo Anticontrabando')]]),
                    $direccion('Dirección de Cobro Persuasivo y Jurisdicción Coactiva', ['corto' => 'Dirección de Cobro Coactivo']),
                    $direccion('Dirección Administrativa de Seguimiento y Control al Proceso de Ley 550', ['corto' => 'Dirección Ley 550']),
                ]),
                $secretaria('Secretaría de Gestión Administrativa', 'Gestión de Recursos Físicos', [
                    $direccion('Dirección de Talento Humano', ['proceso' => 'Gestión del Talento Humano', 'hijas' => [
                        $area('Nómina'),
                        $area('Seguridad y Salud en el Trabajo'),
                        $area('Historias Laborales'),
                        $area('Bienestar y Capacitación'),
                    ]]),
                    $direccion('Dirección Administrativa de Contratación', ['proceso' => 'Contratación', 'corto' => 'Dirección de Contratación']),
                    $direccion('Dirección de Tecnología, Informática y Comunicaciones (TICS)', ['proceso' => 'Gestión Tecnológica y Sistemas de Información', 'corto' => 'Dirección TICS', 'sigla' => 'TICS']),
                    $direccion('Dirección Administrativa de Atención al Ciudadano', ['proceso' => 'Atención al Ciudadano', 'corto' => 'Atención al Ciudadano']),
                    $area('Almacén General'),
                    $area('Gestión Documental', ['proceso' => 'Gestión Documental', 'hijas' => [$area('Archivo (Fondo Acumulado)')]]),
                    // Por confirmar: el PAMP lo ubica bajo Hacienda; la planta, bajo Gestión Administrativa.
                    $area('Pensiones (Fondo Territorial de Pensiones)', ['proceso' => 'Gestión del Talento Humano', 'corto' => 'Fondo Territorial de Pensiones']),
                ]),
                $secretaria('Secretaría de Infraestructura', 'Gestión del Desarrollo Territorial', [
                    $direccion('Dirección Administrativa y Financiera de Infraestructura'),
                    $direccion('Dirección Técnica y Administrativa de Infraestructura'),
                    $direccion('Dirección Departamental de Vivienda', ['corto' => 'Dirección de Vivienda']),
                ]),
                $secretaria('Secretaría de Educación', 'Gestión del Desarrollo Social', [
                    $sed('Calidad Educativa', 'SED Calidad'),
                    $sed('Cobertura', 'SED Cobertura'),
                    $sed('Inspección y Vigilancia', 'SED Inspección y Vigilancia'),
                    $sed('Talento Humano (Recurso Humano)', 'SED Recurso Humano'),
                    $sed('Nómina', 'SED Nómina'),
                    $sed('Escalafón', 'SED Escalafón'),
                    $sed('Jurídica', 'SED Jurídica'),
                    $sed('Planeación', 'SED Planeación'),
                    $sed('Financiera', 'SED Financiera'),
                    $sed('Administrativa', 'SED Administrativa'),
                    $sed('Servicio de Atención al Ciudadano (SAC)', 'SED SAC'),
                    $sed('Archivo de Educación', 'SED Archivo'),
                    $direccion('Dirección de Seguridad Alimentaria (PAE)', ['corto' => 'Seguridad Alimentaria (PAE)']),
                ], ['planta' => 'educacion', 'sigla' => 'SED']),
                $secretaria('Secretaría de Desarrollo de la Salud', 'Gestión del Desarrollo Social', [
                    $direccion('Dirección Administrativa de Inspección, Vigilancia y Control'),
                    $area('Área Financiera'),
                    $area('Aseguramiento'),
                    $area('Salud Pública', ['hijas' => [$area('Laboratorio de Salud Pública')]]),
                    $area('Salud y Riesgos Laborales'),
                    $area('Autorizaciones, Radicación de Cuentas Médicas y Auditoría'),
                    $area('RETHUS'),
                    $area('Promoción Social'),
                    $area('Planeación en Salud'),
                    $area('Centro Regulador de Urgencias y Emergencias (CRUE)', ['corto' => 'CRUE']),
                ], ['corto' => 'Secretaría de Salud']),
                $secretaria('Secretaría del Interior y Participación Ciudadana', 'Gestión del Desarrollo Político', [
                    $direccion('Dirección Técnica de Atención a las Víctimas del Conflicto Armado', ['corto' => 'Dirección de Víctimas']),
                    $direccion('Dirección Administrativa de Discapacidad', ['corto' => 'Dirección de Discapacidad']),
                    $area('Oficina de Pasaportes'),
                ], ['corto' => 'Secretaría del Interior']),
                $secretaria('Secretaría de Desarrollo Económico y Agroindustrial', 'Gestión del Desarrollo Económico', [
                    $direccion('Dirección de Turismo y Artesanías'),
                ]),
                $secretaria('Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor', 'Gestión del Desarrollo Social', [
                    $area('Casa de la Mujer'),
                    $area('Casa Refugio'),
                ], ['corto' => 'Secretaría de la Mujer']),
                // Por confirmar: Biblioparque bajo Cultura.
                $secretaria('Secretaría de Cultura', 'Gestión del Desarrollo Social', [$area('Biblioparque')]),
                $secretaria('Secretaría de Juventud', 'Gestión del Desarrollo Social'),
                $secretaria('Secretaría de Tránsito y Movilidad', 'Gestión del Desarrollo Territorial'),
                $secretaria('Secretaría de Competitividad y Cooperación Internacional', 'Gestión del Desarrollo Económico', [
                    $direccion('Dirección de Ciencia, Tecnología e Innovación'),
                ], ['corto' => 'Secretaría de Competitividad']),
                // Creada por el Decreto 609 de 2023.
                $secretaria('Secretaría de Minas y Energía', 'Gestión del Desarrollo Económico'),
            ],
        ];

        $this->crearDependencia($arbol, null, '', $procesos, null, 'central');
    }

    /**
     * @param  array<string, mixed>  $nodo
     * @param  Collection<string, Proceso>  $procesos
     */
    private function crearDependencia(array $nodo, ?Dependencia $padre, string $rutaPadre, Collection $procesos, ?int $procesoPadre, string $plantaPadre): void
    {
        $procesoId = isset($nodo['proceso']) ? $procesos[$nodo['proceso']]->id : $procesoPadre;
        $planta = $nodo['planta'] ?? $plantaPadre;

        $dependencia = Dependencia::create([
            'padre_id' => $padre?->id,
            'tipo' => $nodo['tipo'],
            'nombre' => $nodo['nombre'],
            'nombre_corto' => $nodo['corto'] ?? null,
            'sigla' => $nodo['sigla'] ?? null,
            'proceso_id' => $procesoId,
            'planta' => $planta,
            'acto_administrativo' => 'Decreto 609 de 2023',
        ]);

        // La raíz no entra en la ruta: «Secretaría de Hacienda/Dirección de Rentas».
        $ruta = $padre === null ? '' : ltrim($rutaPadre.'/'.$nodo['nombre'], '/');
        $this->dependencias[$ruta === '' ? $nodo['nombre'] : $ruta] = $dependencia;

        foreach ($nodo['hijas'] ?? [] as $hija) {
            $this->crearDependencia($hija, $dependencia, $ruta, $procesos, $procesoId, $planta);
        }
    }

    private function equivalencias(): void
    {
        // Nombres anteriores al Decreto 609 (art. 3) y variantes del FOR.01, el PAMP y la planta.
        $equivalencias = [
            'Secretaría de Gestión Administrativa/Dirección de Tecnología, Informática y Comunicaciones (TICS)' => [
                'Dirección Técnica de Sistemas', 'DIRECION DE SISTEMAS', 'Dirección de Sistemas', 'Dirección TIC', 'Dirección TICS',
            ],
            'Secretaría de Competitividad y Cooperación Internacional/Dirección de Ciencia, Tecnología e Innovación' => [
                'Dirección de Tecnologías de la Información y Telecomunicaciones', 'DIRECCION DE TECNOLOGIAS DE LA INFORMACION Y TLECOMUNICACIONES',
            ],
            'Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor' => [
                'Secretaría de Mujer, Género y Desarrollo Social', 'Secretaria para la equidad de la mujer, infancia, adolescencia y persona mayor',
            ],
            'Secretaría de Tránsito y Movilidad' => ['Secretaría de Tránsito y Transporte', 'DESPACHO SECRETARÍA DE TRANSITO Y TRANSPORTE'],
            'Dirección de Protocolo, Prensa y Comunicaciones' => ['Dirección Técnica de Prensa', 'DIRECCION TECNICA DE PRENSA Y COMUNICACIONES', 'Dirección Prensa y Protocolo'],
            'Secretaría de Gestión Administrativa/Dirección de Talento Humano' => ['Dirección Administrativa de Personal', 'DIRECCION DE PERSONAL'],
            'Secretaría de Gestión Administrativa/Dirección de Talento Humano/Nómina' => ['Nómina Central', 'Nómina Administrativa'],
            'Secretaría de Hacienda/Dirección de Tesorería' => ['Dirección Técnica de Información Financiera', 'DIRECCION DE INFORMACION FINANCIERA'],
            'Secretaría de Hacienda/Dirección de Rentas' => ['Dirección Financiera de Gestión de Ingresos', 'DIRECCION DE GESTION DE INGRESOS'],
            'Secretaría de Hacienda/Dirección de Contabilidad' => ['Director Financiero con funciones de Contador'],
            'Secretaría de Desarrollo de la Salud/Dirección Administrativa de Inspección, Vigilancia y Control' => ['DIRECCION DE CONTROLY VIGILANCIA'],
            'Oficina de Control Interno' => ['Control Interno Administrativo'],
            'Secretaría de Educación/Servicio de Atención al Ciudadano (SAC)' => ['SAC Educación'],
        ];

        foreach ($equivalencias as $ruta => $textos) {
            foreach ($textos as $texto) {
                DependenciaEquivalencia::create([
                    'dependencia_id' => $this->dependencia($ruta)->id,
                    'texto' => $texto,
                    'origen' => 'Decreto 609 / FOR.01 / PAMP 2026',
                ]);
            }
        }
    }

    private function ubicaciones(): void
    {
        // Tablas «Palacio NAIN» y «Sedes EXTERNAS» del Programa Anual de Mantenimiento 2026.
        $ubicaciones = [
            'Palacio de Naín' => [
                8 => ['Secretaría de Gestión Administrativa/Dirección Administrativa de Contratación', 'Secretaría de Educación/Escalafón', 'Secretaría de Gestión Administrativa/Gestión Documental/Archivo (Fondo Acumulado)', 'Secretaría del Interior y Participación Ciudadana/Dirección Administrativa de Discapacidad'],
                7 => ['Secretaría de Infraestructura', 'Secretaría de Desarrollo Económico y Agroindustrial/Dirección de Turismo y Artesanías', 'Secretaría de Gestión Administrativa/Dirección de Talento Humano/Nómina', 'Secretaría de Gestión Administrativa/Dirección de Talento Humano'],
                6 => ['Secretaría de Gestión Administrativa/Dirección de Tecnología, Informática y Comunicaciones (TICS)', 'Secretaría de Gestión Administrativa/Dirección de Talento Humano/Nómina', 'Oficina de Control Interno', 'Dirección de Ambiente y Gestión del Riesgo', 'Secretaría de Gestión Administrativa'],
                5 => ['Secretaría de Educación', 'Secretaría de Educación/Calidad Educativa', 'Secretaría de Educación/Talento Humano (Recurso Humano)', 'Secretaría de Educación/Nómina', 'Secretaría de Educación/Cobertura', 'Secretaría de Educación/Inspección y Vigilancia', 'Secretaría de Educación/Jurídica', 'Secretaría de Educación/Planeación'],
                4 => ['Secretaría de Infraestructura/Dirección Departamental de Vivienda', 'Departamento Administrativo de Planeación', 'Oficina de Control Interno Disciplinario', 'Secretaría del Interior y Participación Ciudadana/Dirección Técnica de Atención a las Víctimas del Conflicto Armado', 'Secretaría de Gestión Administrativa/Gestión Documental', 'Secretaría del Interior y Participación Ciudadana'],
                3 => ['Despacho del Gobernador', 'Oficina de Asesores', 'Dirección de Protocolo, Prensa y Comunicaciones', 'Oficina Asesora Jurídica'],
                2 => ['Secretaría de Hacienda/Dirección de Tesorería', 'Secretaría de Gestión Administrativa/Almacén General', 'Secretaría de Educación/Dirección de Seguridad Alimentaria (PAE)', 'Secretaría de Hacienda', 'Secretaría de Gestión Administrativa/Pensiones (Fondo Territorial de Pensiones)', 'Secretaría de Hacienda/Dirección Financiera de Presupuesto', 'Secretaría de Hacienda/Dirección Administrativa de Seguimiento y Control al Proceso de Ley 550', 'Secretaría de Hacienda/Dirección de Contabilidad'],
                1 => ['Secretaría de Gestión Administrativa/Dirección de Talento Humano/Historias Laborales', 'Secretaría de Gestión Administrativa/Dirección Administrativa de Atención al Ciudadano', 'Secretaría de Educación/Servicio de Atención al Ciudadano (SAC)', 'Secretaría de Gestión Administrativa/Dirección de Talento Humano/Seguridad y Salud en el Trabajo'],
            ],
            'Edificio Morindó' => [
                8 => ['Secretaría de Desarrollo de la Salud'],
                7 => ['Secretaría de Desarrollo de la Salud/Área Financiera', 'Secretaría de Desarrollo de la Salud/Aseguramiento', 'Secretaría de Desarrollo de la Salud/Salud Pública', 'Secretaría de Desarrollo de la Salud/Salud y Riesgos Laborales'],
                6 => ['Secretaría de Desarrollo de la Salud/Autorizaciones, Radicación de Cuentas Médicas y Auditoría', 'Secretaría de Desarrollo de la Salud/RETHUS', 'Secretaría de Desarrollo de la Salud/Promoción Social'],
                4 => ['Secretaría de Educación/Archivo de Educación'],
            ],
            'Edificio Victoria Char' => [
                5 => ['Secretaría de Tránsito y Movilidad', 'Secretaría de Desarrollo Económico y Agroindustrial'],
                4 => ['Secretaría de Juventud', 'Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor'],
                3 => ['Secretaría de Minas y Energía', 'Secretaría de Competitividad y Cooperación Internacional'],
                2 => ['Secretaría de Hacienda/Dirección de Rentas/Grupo Anticontrabando'],
                1 => ['Secretaría de Desarrollo de la Salud/RETHUS', 'Secretaría del Interior y Participación Ciudadana/Oficina de Pasaportes'],
            ],
            'Places Mall' => [
                2 => ['Secretaría de Hacienda/Dirección de Cobro Persuasivo y Jurisdicción Coactiva', 'Secretaría de Hacienda/Dirección de Rentas'],
            ],
            'Sede Secretaría de Cultura' => [2 => ['Secretaría de Cultura']],
            'Biblioparque' => [2 => ['Secretaría de Cultura/Biblioparque']],
            'Casa de la Mujer' => [1 => ['Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor/Casa de la Mujer']],
            'Casa Refugio' => [1 => ['Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor/Casa Refugio']],
            'Laboratorio de Salud Pública' => [1 => ['Secretaría de Desarrollo de la Salud/Salud Pública/Laboratorio de Salud Pública']],
            'CRUE' => [1 => ['Secretaría de Desarrollo de la Salud/Centro Regulador de Urgencias y Emergencias (CRUE)']],
        ];

        foreach ($ubicaciones as $sede => $pisos) {
            foreach ($pisos as $numero => $rutas) {
                foreach ($rutas as $ruta) {
                    UbicacionDependencia::firstOrCreate([
                        'dependencia_id' => $this->dependencia($ruta)->id,
                        'piso_id' => $this->pisos[$sede][$numero]->id,
                    ]);
                }
            }
        }
    }

    private function cargos(): void
    {
        // Nomenclatura del Decreto 607 de 2023 (arts. 11 a 15).
        $cargos = [
            ['001', 'Gobernador', 'directivo'],
            ['006', 'Jefe de Oficina', 'directivo'],
            ['009', 'Director Administrativo, Financiero, Técnico u Operativo', 'directivo'],
            ['020', 'Secretario de Despacho', 'directivo'],
            ['055', 'Director de Departamento Administrativo', 'directivo'],
            ['105', 'Asesor', 'asesor'],
            ['115', 'Jefe de Oficina Asesora Jurídica', 'asesor'],
            ['215', 'Almacenista General', 'profesional'],
            ['219', 'Profesional Universitario', 'profesional'],
            ['222', 'Profesional Especializado', 'profesional'],
            ['237', 'Profesional Universitario Área Salud', 'profesional'],
            ['242', 'Profesional Especializado Área Salud', 'profesional'],
            ['314', 'Técnico Operativo', 'tecnico'],
            ['323', 'Técnico Área Salud', 'tecnico'],
            ['367', 'Técnico Administrativo', 'tecnico'],
            ['407', 'Auxiliar Administrativo', 'asistencial'],
            ['412', 'Auxiliar Área Salud', 'asistencial'],
            ['430', 'Secretario Ejecutivo del Despacho del Gobernador', 'asistencial'],
            ['440', 'Secretario', 'asistencial'],
            ['470', 'Auxiliar de Servicios Generales', 'asistencial'],
            ['477', 'Celador', 'asistencial'],
            [null, 'Contratista de prestación de servicios', 'profesional'],
            [null, 'Pasante', 'asistencial'],
        ];

        foreach ($cargos as [$codigo, $denominacion, $nivel]) {
            Cargo::create(['codigo' => $codigo, 'denominacion' => $denominacion, 'nivel' => $nivel]);
        }
    }

    private function tiposEquipo(): void
    {
        // [nombre, familia, tipo de activo (GT-M4 tabla 2), usa configuración de cómputo]
        $tipos = [
            ['PC de escritorio', 'computo', 'hardware', true],
            ['Portátil', 'computo', 'hardware', true],
            ['All in One', 'computo', 'hardware', true],
            ['Servidor físico', 'computo', 'hardware', true],
            ['Monitor', 'video', 'hardware', false],
            ['Impresora', 'impresion', 'hardware', false],
            ['Multifuncional', 'impresion', 'hardware', false],
            ['Escáner', 'digitalizacion', 'hardware', false],
            ['Switch', 'conectividad', 'hardware', false],
            ['Router', 'conectividad', 'hardware', false],
            ['Access Point', 'conectividad', 'hardware', false],
            ['Firewall', 'conectividad', 'hardware', false],
            ['NAS (almacenamiento en red)', 'computo', 'hardware', false],
            ['Videograbador (DVR/NVR)', 'seguridad_electronica', 'hardware', false],
            ['Cámara de vigilancia', 'seguridad_electronica', 'hardware', false],
            ['Control de acceso (biométrico)', 'seguridad_electronica', 'hardware', false],
            ['Video beam', 'proyeccion', 'hardware', false],
            ['Parlante / sistema de audio', 'proyeccion', 'hardware', false],
            ['Teléfono IP', 'telefonia', 'hardware', false],
            ['Planta telefónica', 'telefonia', 'hardware', false],
            ['UPS', 'energia', 'soporte', false],
            ['Planta eléctrica', 'energia', 'soporte', false],
            ['Aire acondicionado (cuarto técnico)', 'climatizacion', 'soporte', false],
            // Respaldo del importador cuando un tipo no coincide con ninguno (Importacion\Index::tipoId).
            ['Otro', 'computo', 'hardware', false],
        ];

        foreach ($tipos as [$nombre, $familia, $tipoActivo, $computo]) {
            TipoEquipo::create([
                'nombre' => $nombre,
                'familia' => $familia,
                'tipo_activo' => $tipoActivo,
                'usa_configuracion_computo' => $computo,
            ]);
        }
    }

    private function marcas(): void
    {
        collect([
            'HP', 'Dell', 'Lenovo', 'Epson', 'APC', 'TP-Link', 'Canon', 'Samsung', 'LG', 'Asus', 'Acer',
            'Brother', 'Cisco', 'Ubiquiti', 'Huawei', 'Kyocera', 'Ricoh', 'Xerox', 'Hikvision', 'Dahua',
            'ZKTeco', 'Fortinet', 'MikroTik', 'Synology', 'Grandstream', 'Yealink', 'Panasonic',
        ])->each(fn (string $nombre) => Marca::create(['nombre' => $nombre]));
    }

    private function sistemasOperativos(): void
    {
        // Fin de soporte del fabricante: alimenta el reporte de obsolescencia.
        $sistemas = [
            'Windows 7' => '2020-01-14',
            'Windows 8' => '2023-01-10',
            'Windows 10 Home' => '2025-10-14',
            'Windows 10 Pro' => '2025-10-14',
            'Windows 11 Home' => null,
            'Windows 11 Pro' => null,
            'Windows Server 2016' => '2027-01-12',
            'Windows Server 2019' => '2029-01-09',
            'Windows Server 2022' => '2031-10-14',
            'Linux' => null,
            'N/A' => null,
        ];

        foreach ($sistemas as $nombre => $finSoporte) {
            SistemaOperativo::create(['nombre' => $nombre, 'fin_soporte' => $finSoporte]);
        }
    }

    private function motivosBaja(): void
    {
        collect([
            'Obsolescencia', 'Daño irreparable', 'Pérdida', 'Hurto', 'Devolución al propietario (tercero)', 'Reposición',
        ])->each(fn (string $nombre) => MotivoBaja::create(['nombre' => $nombre]));
    }

    private function tiposComponente(): void
    {
        // [nombre, periférico, obligatorio en cómputo (RF-14)]
        $tipos = [
            ['RAM', false, true],
            ['Disco', false, true],
            ['Procesador', false, true],
            ['Tarjeta de red', false, false],
            ['Tarjeta de video', false, false],
            ['Fuente de poder', false, false],
            ['Teclado', true, false],
            ['Mouse', true, false],
            ['Sonido', true, false],
            ['Cámara', true, false],
        ];

        foreach ($tipos as [$nombre, $periferico, $obligatorio]) {
            TipoComponente::create(['nombre' => $nombre, 'es_periferico' => $periferico, 'obligatorio_en_computo' => $obligatorio]);
        }
    }

    private function puestosTrabajo(): void
    {
        // Puestos de ejemplo (RF-05 es Fase 3): cada uno en un piso real y con una
        // dependencia que funciona en ese piso según el PAMP.
        UbicacionDependencia::query()->inRandomOrder()->take(25)->get()
            ->each(function (UbicacionDependencia $ubicacion, int $i): void {
                PuestoTrabajo::create([
                    'nombre_o_codigo' => 'PT-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'piso_id' => $ubicacion->piso_id,
                    'dependencia_id' => $ubicacion->dependencia_id,
                ]);
            });
    }

    private function dependencia(string $ruta): Dependencia
    {
        return $this->dependencias[$ruta]
            ?? throw new \RuntimeException("CatalogosSeeder: no existe la dependencia «{$ruta}».");
    }
}
