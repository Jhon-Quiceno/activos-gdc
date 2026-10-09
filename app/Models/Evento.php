<?php

namespace App\Models;

use Database\Factories\EventoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Representa un hecho de la hoja de vida de un equipo (alta, traslado, cambio de
 * componente, diagnóstico, baja, actualización de datos o anulación/aclaración).
 *
 * RN-06 / RF-12: un Evento es INMUTABLE una vez creado. Nunca se edita ni se borra;
 * cualquier corrección se hace registrando un nuevo evento de tipo
 * "anulacion_aclaracion" que referencia (evento_anulado_id) al evento que queda
 * anulado. Esto preserva la trazabilidad completa de la hoja de vida del activo.
 *
 * Por eso este modelo solo permite create(). Los intentos de update()/delete() (o de
 * save() sobre un registro existente) lanzan una excepción tanto a nivel de instancia
 * como de eventos de Eloquent, para que ningún código de la aplicación pueda "editar
 * el pasado" por accidente.
 *
 * NOTA: las operaciones masivas vía query builder (Evento::where(...)->update(...) o
 * ->delete()) NO disparan eventos de modelo en Eloquent, por lo que esta protección
 * aplica al uso normal por instancia ($evento->update(), $evento->delete(),
 * $evento->save()). No debe usarse el query builder para modificar eventos: usa
 * siempre HistorialService::registrar() para crear nuevos eventos.
 */
class Evento extends Model
{
    /** @use HasFactory<EventoFactory> */
    use HasFactory;

    protected $fillable = [
        'equipo_id',
        'tipo',
        'fecha',
        'descripcion',
        'usuario_id',
        'estado_firma',
        'evento_anulado_id',
        'valores',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'valores' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Evento $evento): void {
            throw new LogicException('Los eventos son inmutables: no se pueden actualizar. Registra un nuevo evento de tipo anulacion_aclaracion.');
        });

        static::deleting(function (Evento $evento): void {
            throw new LogicException('Los eventos son inmutables: no se pueden eliminar. Registra un nuevo evento de tipo anulacion_aclaracion.');
        });
    }

    /**
     * Bloqueado a propósito. Ver el docblock de la clase.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Los eventos son inmutables: no se pueden actualizar. Registra un nuevo evento de tipo anulacion_aclaracion.');
    }

    /**
     * Bloqueado a propósito. Ver el docblock de la clase.
     */
    public function delete(): ?bool
    {
        throw new LogicException('Los eventos son inmutables: no se pueden eliminar. Registra un nuevo evento de tipo anulacion_aclaracion.');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Evento que este evento anula (cuando tipo = anulacion_aclaracion).
     */
    public function eventoAnulado(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_anulado_id');
    }

    /**
     * Eventos de anulación/aclaración que apuntan a este evento.
     */
    public function anulaciones(): HasMany
    {
        return $this->hasMany(Evento::class, 'evento_anulado_id');
    }

    public function cambioComponente(): HasOne
    {
        return $this->hasOne(CambioComponente::class);
    }

    public function diagnostico(): HasOne
    {
        return $this->hasOne(Diagnostico::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function asignacionesOrigen(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'evento_origen_id');
    }
}
