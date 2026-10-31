<?php

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    protected $fillable = [
        'serial',
        'codigo_activo',
        'codigo_activo_justificacion',
        'qr_uuid',
        'tipo_equipo_id',
        'marca_id',
        'modelo_id',
        'descripcion',
        'caracteristicas',
        'proceso_id',
        'propiedad',
        'proveedor_tercero_id',
        'figura_tercero',
        'forma_ingreso',
        'fecha_adquisicion',
        'valor_adquisicion',
        'proveedor_id',
        'numero_contrato',
        'numero_factura',
        'fecha_fin_garantia',
        'vida_util_meses',
        'estado_funcionamiento',
        'observaciones',
        'estado_ciclo_vida',
        'verificacion',
        'importacion_id',
        'fila_origen_importacion',
    ];

    public const FORMAS_INGRESO = [
        'compra' => 'Compra',
        'donacion' => 'Donación',
        'comodato' => 'Comodato',
        'convenio' => 'Convenio',
        'arrendamiento' => 'Arrendamiento',
        'carga_inicial' => 'Carga inicial',
    ];

    /** Estado de funcionamiento (lista cerrada, §9 del análisis). Clave => etiqueta. */
    public const ESTADOS_FUNCIONAMIENTO = [
        'bueno' => 'Bueno',
        'regular' => 'Regular',
        'malo' => 'Malo, requiere cambio de componentes',
        'no_funcional' => 'No funcional',
    ];

    /** Etiqueta legible del estado de funcionamiento. */
    public function estadoFuncionamientoTexto(): ?string
    {
        return self::ESTADOS_FUNCIONAMIENTO[$this->estado_funcionamiento] ?? $this->estado_funcionamiento;
    }

    protected function casts(): array
    {
        return [
            'caracteristicas' => 'array',
            'numero_activo' => 'integer',
            'fecha_adquisicion' => 'date',
            'valor_adquisicion' => 'decimal:2',
            'fecha_fin_garantia' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Equipo $equipo): void {
            // RN-19: el qr_uuid es un identificador permanente e independiente del serial
            // o del código de activo. Se genera automáticamente si no viene dado.
            if (empty($equipo->qr_uuid)) {
                $equipo->qr_uuid = (string) Str::uuid();
            }
        });

        // Un solo hook para no arriesgar que una futura extensión solo actualice
        // creating o updating y se salte la validación en el otro. `! $equipo->exists`
        // reproduce el "siempre" de creating (antes de insertar nunca hay original
        // contra el que comparar); en update se preserva el isDirty de antes: revalida
        // también si solo cambia la justificación (si alguien la borra dejando un
        // código duplicado, tiene que fallar igual).
        // RN-27: el número de activo («ID ACTIVO» del FOR.01) es el consecutivo
        // del equipo. Se asigna justo después de insertar, a partir del id, así
        // dos altas simultáneas nunca obtienen el mismo número; después no cambia.
        static::created(function (Equipo $equipo): void {
            if ($equipo->numero_activo === null) {
                $equipo->numero_activo = $equipo->id;
                $equipo->saveQuietly();
            }
        });

        static::updating(function (Equipo $equipo): void {
            if ($equipo->isDirty('numero_activo') && $equipo->getOriginal('numero_activo') !== null) {
                throw ValidationException::withMessages([
                    'numero_activo' => ['El número de activo no cambia nunca (RN-27).'],
                ]);
            }
        });

        static::saving(function (Equipo $equipo): void {
            if (! $equipo->exists
                || $equipo->isDirty('codigo_activo')
                || $equipo->isDirty('codigo_activo_justificacion')) {
                self::validarJustificacionCodigoActivo($equipo);
            }
        });
    }

    /**
     * Garantía vigente que vence en los próximos `$dias` días (RF-52).
     */
    public function garantiaPorVencer(int $dias = 60): bool
    {
        return $this->fecha_fin_garantia !== null
            && $this->fecha_fin_garantia->isFuture()
            && $this->fecha_fin_garantia->lte(now()->addDays($dias));
    }

    /**
     * Superó su vida útil desde la fecha de adquisición (RF-52).
     */
    public function vidaUtilSuperada(): bool
    {
        $meses = $this->vida_util_meses ?? $this->tipoEquipo?->vida_util_meses;

        return $this->fecha_adquisicion !== null && $meses !== null
            && $this->fecha_adquisicion->copy()->addMonths($meses)->isPast();
    }

    /**
     * Normaliza espacios en el código de activo al guardar, para que la
     * comparación de duplicados (RN-03) y el valor persistido sean consistentes
     * sin importar desde dónde se asigne (formulario, importación, factory...).
     * Un valor que queda vacío tras el trim se guarda como null, igual que si
     * nunca se hubiera indicado código.
     */
    public function setCodigoActivoAttribute(mixed $value): void
    {
        $normalizado = $value === null ? null : trim((string) $value);

        $this->attributes['codigo_activo'] = $normalizado === '' ? null : $normalizado;
    }

    /**
     * RN-03: el código de activo puede repetirse (ej. un All in One y su pantalla
     * integrada comparten código), pero el sistema exige justificación cuando eso
     * pasa. Se valida acá, a nivel de modelo, para que valga sin importar desde qué
     * formulario o importación se cree/edite el equipo (mismo criterio que la
     * inmutabilidad de Evento).
     */
    protected static function validarJustificacionCodigoActivo(Equipo $equipo): void
    {
        // is_null + trim en vez de empty(): empty("0") es true en PHP, así que un
        // código de activo literal "0" quedaría sin validar con el chequeo anterior.
        if (is_null($equipo->codigo_activo) || trim((string) $equipo->codigo_activo) === '') {
            return;
        }

        $codigoActivo = trim((string) $equipo->codigo_activo);

        // lockForUpdate(): sin esto, dos requests concurrentes podrían leer "no
        // existe duplicado" antes de que ninguna haya insertado (TOCTOU) y las dos
        // pasarían la validación. Solo tiene efecto real dentro de una transacción
        // (ver callers: Equipo::create()/save() deben invocarse dentro de
        // DB::transaction()).
        $existeEnOtroEquipo = static::where('codigo_activo', $codigoActivo)
            ->when($equipo->exists, fn ($query) => $query->whereKeyNot($equipo->getKey()))
            ->lockForUpdate()
            ->exists();

        if ($existeEnOtroEquipo && trim((string) $equipo->codigo_activo_justificacion) === '') {
            throw ValidationException::withMessages([
                'codigo_activo' => ['El código de activo ya existe en otro equipo (RN-03): hace falta indicar codigo_activo_justificacion.'],
            ]);
        }
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /** Modelo del catálogo. Se llama así porque `modelo` es el campo viejo de texto. */
    public function modeloEquipo(): BelongsTo
    {
        return $this->belongsTo(ModeloEquipo::class, 'modelo_id');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    /** Dueño del equipo cuando propiedad = tercero (RN-16). */
    public function proveedorTercero(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_tercero_id');
    }

    /** A quién se compró el equipo (RF-52). */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function valoraciones(): HasMany
    {
        return $this->hasMany(ValoracionActivo::class)->latest('id');
    }

    /** Valoración GT-M4 vigente (RN-22: como máximo una). */
    public function valoracionVigente(): HasOne
    {
        return $this->hasOne(ValoracionActivo::class)->where('vigente', true);
    }

    /** Fotos del equipo (RF-06), más reciente primero. */
    public function fotos(): HasMany
    {
        return $this->hasMany(FotoEquipo::class)->latest('id');
    }

    /** Foto principal del equipo, si tiene (RN-22: como máximo una). */
    public function fotoPrincipal(): HasOne
    {
        return $this->hasOne(FotoEquipo::class)->where('es_principal', true);
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class);
    }

    public function configuracionComputo(): HasOne
    {
        return $this->hasOne(ConfiguracionComputo::class);
    }

    public function componentes(): HasMany
    {
        return $this->hasMany(Componente::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function etiquetasQr(): HasMany
    {
        return $this->hasMany(EtiquetaQr::class);
    }

    /**
     * Asignación abierta/actual del equipo (fecha_fin null), si existe.
     */
    public function asignacionActual(): HasOne
    {
        return $this->hasOne(Asignacion::class)->whereNull('fecha_fin')->latestOfMany('fecha_inicio');
    }
}
