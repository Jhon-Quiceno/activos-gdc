<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Support\Facades\Date;

/**
 * Único punto de entrada para registrar hechos en la hoja de vida de un equipo.
 *
 * RN-06 / RF-12: los registros de la tabla `eventos` son inmutables (ver
 * App\Models\Evento). Por eso NINGÚN otro lugar de la aplicación debe crear un
 * Evento directamente con `Evento::create()`. Todos los módulos (altas, traslados,
 * cambios de componente, diagnósticos, bajas, actualizaciones de datos y
 * anulaciones/aclaraciones) deben pasar siempre por este servicio para que la
 * hoja de vida quede completa, consistente y auditable.
 *
 * Este servicio solo implementa el núcleo de registro (crear el Evento base). La
 * lógica de negocio específica de cada tipo de evento (por ejemplo, crear también
 * la fila en `cambios_componente` o en `diagnosticos`, mover la asignación, cambiar
 * el estado_ciclo_vida del equipo, etc.) la implementa cada módulo por separado,
 * normalmente en la misma transacción en la que se llama a `registrar()`.
 */
class HistorialService
{
    /**
     * Registra un nuevo evento en la hoja de vida de un equipo.
     *
     * Contrato:
     * - Crea siempre un Evento nuevo (nunca actualiza uno existente: los eventos son
     *   inmutables).
     * - El `usuario_id` del evento se asigna automáticamente a partir del usuario
     *   autenticado que se pasa en `$usuario` (RF-11): el autor de un evento nunca se
     *   infiere de otra forma ni se puede sobrescribir después.
     * - `$datos` permite pasar campos adicionales válidos del modelo Evento (por
     *   ejemplo `estado_firma`) sin tener que ampliar la firma del método cada vez
     *   que se agregue un campo opcional.
     * - `$eventoAnulado` solo se usa cuando `$tipo === 'anulacion_aclaracion'`, y
     *   enlaza este evento con el evento que queda anulado.
     *
     * @param  Equipo  $equipo  Equipo al que pertenece el evento.
     * @param  string  $tipo  Uno de: alta, traslado_responsable, cambio_componente,
     *                        diagnostico, baja, actualizacion_datos, anulacion_aclaracion.
     * @param  User  $usuario  Usuario autenticado que origina el evento (autor, RF-11).
     * @param  array<string, mixed>  $datos  Campos adicionales opcionales del Evento
     *                                       (p. ej. 'estado_firma').
     * @param  string|null  $descripcion  Descripción libre del evento.
     * @param  Evento|null  $eventoAnulado  Evento que este evento anula/aclara, si aplica.
     * @return Evento El evento recién creado.
     */
    public function registrar(
        Equipo $equipo,
        string $tipo,
        User $usuario,
        array $datos = [],
        ?string $descripcion = null,
        ?Evento $eventoAnulado = null,
    ): Evento {
        return Evento::create(array_merge([
            'equipo_id' => $equipo->id,
            'tipo' => $tipo,
            'fecha' => Date::now(),
            'descripcion' => $descripcion,
            'usuario_id' => $usuario->id,
            'evento_anulado_id' => $eventoAnulado?->id,
        ], $datos));
    }
}
