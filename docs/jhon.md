# Jhon — Líder: base de datos, piezas compartidas, Reportes, Administración e Inicio

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Sigues como líder: eres el único que crea migraciones, cuidas las piezas compartidas (`HistorialService`, `Evento`, componentes `x-ui.*`, layout) y revisas y fusionas los PR. Desde esta fase también son tuyos los bloques que en la Fase 2 eran de Alex (Reportes) y de Manuel (Administración e Inicio). Tu requerimiento propio es **RF-13** (tipos de evento configurables), y además creas **todas las migraciones** que necesitan los requerimientos de Juan José.

## Antes del día 1

- Pasar `develop` a `main` y etiquetar `v2.0`.
- Replicar la versión 4.2 del análisis en `docs/analisis-requerimientos-v4.docx` (nota en §1.3 del análisis).

## Migraciones de la fase, en orden

Detalle de columnas en [base-de-datos.md §10](./base-de-datos.md#10-fase-3--cambios-de-esquema-los-crea-el-líder). Cada una con su `down()`, sus modelos y factories al día, y Pest completo en verde antes de fusionar.

| Orden | Migración | Qué hace | Habilita | Día |
|---|---|---|---|---|
| 1 | **F3-1** `ajustar_puestos_trabajo` | `puestos_trabajo.activo` y `unique(piso_id, nombre_o_codigo)`. Decidir y documentar `equipos.puesto_trabajo_id` (recomendado: borrarlo; la fuente de verdad es `asignaciones.puesto_trabajo_id`). Ajustar `PuestoTrabajo` (`activo` en `$fillable`, cast) y el seeder. | RF-05 de Juan José | 1 |
| 2 | **F3-2** `create_fotos_equipo_table` | Tabla `fotos_equipo` + modelo `FotoEquipo` (relación `$equipo->fotos`, `fotoPrincipal`), regla de una sola principal por equipo en el modelo y factory. | RF-06 de Juan José | 2 |
| 3 | **F3-3** `create_tipos_evento_table` | Tabla `tipos_evento` sembrada con los 8 tipos del sistema; `eventos.tipo` de `enum` a `varchar(40)` con llave foránea a `tipos_evento.clave`, conservando las claves actuales. Modelo `TipoEvento`. | RF-13 (tuyo) | 3–4 |

RF-16, RF-17, RF-31 y RF-42 **no** necesitan migración. Si Juan José pide un campo en el camino, se crea el mismo día.

## RF-13 — Configurar nuevos tipos de evento sin desarrollo

| Días | Tareas | Entregable |
|---|---|---|
| 3–4 | **F3-3** y adaptar `HistorialService::registrar()`: valida que el tipo exista en `tipos_evento` y esté activo; si `requiere_firma`, el evento nace «pendiente de firma» (RF-30). Los 8 tipos del sistema conservan su comportamiento actual (traslado, baja, valoración y componente siguen con su lógica propia). | Eventos sobre `tipos_evento`, Pest en verde. |
| 5 | Etiquetas de tipo de evento leídas de `TipoEvento` (no de arreglos fijos) donde hoy están escritas a mano: tablero de inicio (`$tipoEventoLabels` en `dashboard.blade.php`), reportes de Historial, Traslados y Pendientes de firma, y el PDF de la hoja de vida. | Un tipo nuevo se ve con su nombre en todas partes. |
| 6 | Pantalla en **Administración** (pestaña nueva o sección de Listas): crear, renombrar, describir, marcar «requiere firma» y desactivar tipos configurables. Los del sistema se ven pero no se editan ni se desactivan. Nunca se borra un tipo usado. | Tipos de evento administrables. |
| 7 | Componente compartido para **registrar un evento de un tipo configurable** (fecha, descripción y adjuntos opcionales) sobre un equipo, que Juan José monta en la hoja de vida el día 8. Pasa por `HistorialService::registrar()`. | Componente listo para usar. |
| 8 | Revisión de PR y correcciones. | — |

## Ajustes de Reportes y de Administración/Inicio

- **Sí hay ajustes, y todos salen de RF-13:** las etiquetas de tipo de evento del tablero, de los reportes de eventos y del PDF de la hoja de vida (día 5), y la pantalla de tipos de evento en Administración (día 6).
- **No surge ningún otro ajuste obligatorio** de Reportes ni de Inicio por los requerimientos de Juan José. Opcional, si sobra tiempo: columna «Puesto» en los reportes por sede y piso (RF-05) y una tarjeta de «pendientes de firma por antigüedad» en el tablero (RF-31).

## Pruebas mínimas

- Un tipo configurable nuevo se registra sobre un equipo y aparece con su nombre en el historial, en el reporte de Historial por equipo y en el PDF.
- Un tipo con «requiere firma» nace «pendiente de firma» y aparece en la campanita.
- No se puede desactivar ni renombrar la clave de un tipo del sistema, ni borrar un tipo con eventos.
- `HistorialService::registrar()` rechaza un tipo inexistente o desactivado.
- Después de F3-3 la suite completa sigue en verde sin cambiar el código que registra traslados, bajas, valoraciones y componentes.

## Normas

Ramas `feature/nucleo-<tarea>` para migraciones y piezas compartidas, `feature/admin-<tarea>`, `feature/reportes-<tarea>` e `feature/inicio-<tarea>` para tus bloques, siempre desde `develop`. Ver [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/nucleo-puestos-trabajo
```
