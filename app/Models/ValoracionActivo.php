<?php

namespace App\Models;

use App\Services\HistorialService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Valoración de un activo según el Manual GT-M4 (4.2.1 a 4.2.6).
 *
 * Reglas que se cumplen aquí, sin importar desde qué pantalla o carga se cree:
 * - RN-22: `total`, `importancia` y `requiere_gestion_riesgo` se calculan; si
 *   alguien los manda, se sobrescriben. Una sola valoración vigente por equipo.
 * - RN-23: requiere gestión de riesgos si algún criterio vale 4 o la
 *   importancia es alta o muy alta.
 * - RN-24: sin datos personales, la clasificación es «no aplica»; con datos
 *   personales, la clasificación es obligatoria.
 * - RN-25: los activos de tipo soporte (UPS, aires, plantas) no se valoran.
 *
 * Es histórica e inmutable: una reclasificación crea otra fila con
 * `registrar()`. Lo único que cambia en una fila existente es `vigente`.
 */
class ValoracionActivo extends Model
{
    public const UPDATED_AT = null;

    /** Respuestas de la escala de confidencialidad (GT-M4 tabla 3 y FOR.01). */
    public const ESCALA_CONFIDENCIALIDAD = [
        4 => 'Daño inestimable o irreversible: se podría afectar la imagen de la entidad o hay violación de procedimientos legales.',
        3 => 'Sería importante: afectaría temporal o gravemente la operación del proceso.',
        2 => 'Divulgación afectaría considerablemente: la información solo la conoce personal de algunas áreas.',
        1 => 'No es relevante: afectación parcial o de ninguna manera a la entidad.',
    ];

    /** Respuestas de la escala de integridad (GT-M4 tabla 4 y FOR.01). */
    public const ESCALA_INTEGRIDAD = [
        4 => 'Toda la organización: la vulneración de la integridad afectaría irreversiblemente a la entidad.',
        3 => 'Unidades o procesos: la vulneración de la integridad afectaría gravemente a la entidad.',
        2 => 'Actividades, servicios o personas: la vulneración afectaría considerablemente a la entidad.',
        1 => 'No es relevante: la vulneración afectaría parcialmente o de ninguna manera a la entidad.',
    ];

    /** Respuestas de la escala de disponibilidad (GT-M4 tabla 5 y FOR.01). */
    public const ESCALA_DISPONIBILIDAD = [
        4 => 'El activo nunca puede estar indisponible: su carencia afectaría irreversiblemente a la entidad.',
        3 => 'Puede estar indisponible máximo una hora: su carencia afectaría gravemente a la entidad.',
        2 => 'Puede estar indisponible máximo un día: su carencia afectaría considerablemente a la entidad.',
        1 => 'Puede estar indisponible una semana o más: su carencia afectaría parcialmente o de ninguna manera.',
    ];

    public const IMPORTANCIAS = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'muy_alta' => 'Muy alta',
    ];

    public const CLASIFICACIONES_DATOS = [
        'publico' => 'Público',
        'privado' => 'Privado',
        'semiprivado' => 'Semiprivado',
        'sensible' => 'Sensible',
        'reservado' => 'Reservado',
        'clasificado' => 'Clasificado',
        'no_aplica' => 'No aplica',
    ];

    /** Controles de monitoreo de la hoja «Datos» del FOR.01. */
    public const CONTROLES_MONITOREO = [
        'Cámaras de vigilancia',
        'Sensor de humo',
        'Sensor de temperatura',
        'Sensor de humedad',
        'No se cuenta con control de monitoreo',
    ];

    protected $table = 'valoraciones_activo';

    protected $fillable = [
        'equipo_id',
        'evento_id',
        'levantamiento_id',
        'contiene_datos_personales',
        'clasificacion_datos',
        'infraestructura_critica',
        'continuidad_negocio',
        'propietario_cargo',
        'custodio_cargo',
        'tipo_ubicacion',
        'tiempo_retencion',
        'confidencialidad',
        'integridad',
        'disponibilidad',
        'controles_monitoreo',
        'vigente',
        'fecha',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'confidencialidad' => 'integer',
            'integridad' => 'integer',
            'disponibilidad' => 'integer',
            'total' => 'integer',
            'requiere_gestion_riesgo' => 'boolean',
            'vigente' => 'boolean',
            'fecha' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ValoracionActivo $valoracion): void {
            $valoracion->validarReglas();
            $valoracion->calcular();
        });

        static::updating(function (ValoracionActivo $valoracion): void {
            if (array_diff(array_keys($valoracion->getDirty()), ['vigente']) !== []) {
                throw new LogicException('Una valoración es histórica: para reclasificar se registra una nueva (RF-58).');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Las valoraciones no se borran: son parte de la hoja de vida.');
        });
    }

    /**
     * Registra la valoración de un equipo con su evento `valoracion` y deja
     * no vigente la anterior, todo en una transacción con el equipo bloqueado
     * (dos guardados simultáneos no pueden dejar dos vigentes).
     *
     * @param  array<string, mixed>  $datos  Campos de la valoración (sin equipo, usuario, evento ni cálculos).
     */
    public static function registrar(
        Equipo $equipo,
        User $usuario,
        array $datos,
        ?string $descripcion = null,
        ?LevantamientoActivos $levantamiento = null,
    ): self {
        return DB::transaction(function () use ($equipo, $usuario, $datos, $descripcion, $levantamiento) {
            Equipo::whereKey($equipo->id)->lockForUpdate()->first();

            $anterior = static::where('equipo_id', $equipo->id)->where('vigente', true)->first();

            $valoracion = new static(array_merge($datos, [
                'equipo_id' => $equipo->id,
                'usuario_id' => $usuario->id,
                'levantamiento_id' => $levantamiento?->id,
                'fecha' => $datos['fecha'] ?? now()->toDateString(),
                'vigente' => true,
            ]));
            $valoracion->setRelation('equipo', $equipo);
            $valoracion->validarReglas();
            $valoracion->calcular();

            $evento = app(HistorialService::class)->registrar(
                $equipo,
                'valoracion',
                $usuario,
                ['valores' => [
                    'antes' => $anterior?->resumen(),
                    'despues' => $valoracion->resumen(),
                ]],
                $descripcion ?? ($anterior ? 'Reclasificación del activo' : 'Valoración del activo'),
            );

            $anterior?->update(['vigente' => false]);

            $valoracion->evento_id = $evento->id;
            $valoracion->save();

            return $valoracion;
        });
    }

    /**
     * Importancia según la suma C + I + D (GT-M4 tabla 6).
     */
    public static function importanciaPara(int $total): string
    {
        return match (true) {
            $total >= 10 => 'muy_alta',
            $total >= 7 => 'alta',
            $total >= 4 => 'media',
            default => 'baja',
        };
    }

    public function calcular(): void
    {
        $this->total = (int) $this->confidencialidad + (int) $this->integridad + (int) $this->disponibilidad;
        $this->importancia = self::importanciaPara($this->total);
        $this->requiere_gestion_riesgo = in_array(4, [
            (int) $this->confidencialidad,
            (int) $this->integridad,
            (int) $this->disponibilidad,
        ], true) || in_array($this->importancia, ['alta', 'muy_alta'], true);
    }

    /**
     * @throws ValidationException
     */
    public function validarReglas(): void
    {
        $errores = [];

        foreach (['confidencialidad', 'integridad', 'disponibilidad'] as $criterio) {
            $valor = $this->{$criterio};

            if (! is_numeric($valor) || (int) $valor < 1 || (int) $valor > 4) {
                $errores[$criterio] = ['La calificación debe estar entre 1 y 4 (GT-M4).'];
            }
        }

        if ($this->contiene_datos_personales === 'si' && in_array($this->clasificacion_datos, [null, '', 'no_aplica'], true)) {
            $errores['clasificacion_datos'] = ['Si el activo contiene datos personales, hay que clasificarlos (RN-24).'];
        }

        if ($this->contiene_datos_personales !== 'si' && ! in_array($this->clasificacion_datos, [null, '', 'no_aplica'], true)) {
            $errores['clasificacion_datos'] = ['Sin datos personales, la clasificación debe ser «No aplica» (RN-24).'];
        }

        $equipo = $this->relationLoaded('equipo') ? $this->equipo : Equipo::with('tipoEquipo')->find($this->equipo_id);

        if ($equipo?->tipoEquipo?->tipo_activo === 'soporte') {
            $errores['equipo_id'] = ['Los activos de soporte (UPS, aires, plantas) no se valoran (RN-25).'];
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        $this->clasificacion_datos = $this->clasificacion_datos ?: 'no_aplica';
    }

    /**
     * Datos de la valoración para guardar en `eventos.valores` (antes/después).
     *
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        return [
            'confidencialidad' => (int) $this->confidencialidad,
            'integridad' => (int) $this->integridad,
            'disponibilidad' => (int) $this->disponibilidad,
            'total' => (int) $this->total,
            'importancia' => $this->importancia,
            'requiere_gestion_riesgo' => (bool) $this->requiere_gestion_riesgo,
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function levantamiento(): BelongsTo
    {
        return $this->belongsTo(LevantamientoActivos::class, 'levantamiento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
