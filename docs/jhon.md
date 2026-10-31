# Jhon — Líder: base de datos y piezas compartidas

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Sigues como líder: eres el único que crea migraciones, cuidas las piezas compartidas (`HistorialService`, `Evento`, componentes `x-ui.*`, layout) y revisas y fusionas los PR. En esta fase tu entrega propia es la parte núcleo de **RF-13** (tipos de evento configurables): la migración, el seeder y la validación en `HistorialService`. La pantalla de administración y las etiquetas en pantalla las hacen Manuel y Alex (ver [plan-fase3.md, sección 2](./plan-fase3.md#2-reparto)).

## Antes del día 1

- Pasar `develop` a `main` y etiquetar `v2.0`.
- Replicar la versión 4.2 del análisis en `docs/analisis-requerimientos-v4.docx` (nota en la sección 1.3 del análisis).

## Migraciones de la fase, en orden

Detalle de columnas en [base-de-datos.md, sección 10](./base-de-datos.md#10-fase-3--cambios-de-esquema-los-crea-el-líder). Cada una con su `down()`, sus modelos y factories al día, y Pest completo en verde antes de fusionar.

| Orden | Migración | Qué hace | Habilita | Día |
|---|---|---|---|---|
| 1 | ✅ **F3-1** `ajustar_puestos_trabajo` | `puestos_trabajo.activo` y `unique(piso_id, nombre_o_codigo)`. `equipos.puesto_trabajo_id` se borró (resto de v3 sin uso; la fuente de verdad es `asignaciones.puesto_trabajo_id`). `PuestoTrabajo` con `activo` en `$fillable` y cast. | RF-05 (Juan José y Anuar) — ya pueden empezar. | 1 |
| 2 | ✅ **F3-2** `create_fotos_equipo_table` | Tabla `fotos_equipo` + modelo `FotoEquipo` (relación `$equipo->fotos`, `fotoPrincipal`), regla de una sola principal por equipo en el modelo (`saving`) y factory con estado `principal()`. | RF-06 (Juan José) — ya puede empezar. | 2 |
| 3 | ✅ **F3-3** `create_tipos_evento_table` | Tabla `tipos_evento` sembrada con los 8 tipos del sistema; `eventos.tipo` de `enum` a `varchar(40)` con llave foránea a `tipos_evento.clave`, conservando las claves actuales. Modelo `TipoEvento`. | RF-13 (núcleo) | 3–4 |

RF-16, RF-17, RF-31 y RF-42 **no** necesitan migración. Si alguien pide un campo en el camino, se crea el mismo día.

## RF-13 — Configurar nuevos tipos de evento sin desarrollo (parte núcleo)

| Días | Tareas | Entregable |
|---|---|---|
| 3–4 | **F3-3** y adaptar `HistorialService::registrar()`: valida que el tipo exista en `tipos_evento` y esté activo; si `requiere_firma`, el evento nace «pendiente de firma» (RF-30). Los 8 tipos del sistema conservan su comportamiento actual (traslado, baja, valoración y componente siguen con su lógica propia). | Eventos sobre `tipos_evento`, Pest en verde. |
| 7 | Componente compartido para **registrar un evento de un tipo configurable** (fecha, descripción y adjuntos opcionales) sobre un equipo, que Juan José monta en la hoja de vida el día 8. Pasa por `HistorialService::registrar()`. | Componente listo para usar. |
| — | Revisión de PR y correcciones (días 7–10). | — |

Manuel construye sobre esto la pantalla de Administración (crear, renombrar, describir, marcar «requiere firma» y desactivar tipos configurables; los del sistema no se editan ni se desactivan) y Alex y Manuel actualizan las etiquetas que hoy están escritas a mano (tablero, reportes de eventos, PDF de la hoja de vida) para leerlas de `TipoEvento`.

## Pruebas mínimas

- Un tipo configurable nuevo se registra sobre un equipo y aparece con su nombre en el historial, en el reporte de Historial por equipo y en el PDF.
- Un tipo con «requiere firma» nace «pendiente de firma» y aparece en la campanita.
- No se puede desactivar ni renombrar la clave de un tipo del sistema, ni borrar un tipo con eventos.
- `HistorialService::registrar()` rechaza un tipo inexistente o desactivado.
- Después de F3-3 la suite completa sigue en verde sin cambiar el código que registra traslados, bajas, valoraciones y componentes.

## Normas

Rama `feature/nucleo-<tarea>` para migraciones y piezas compartidas, siempre desde `develop`. Ver [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/nucleo-puestos-trabajo
```

---

**Ver también:** [Plan de Fase 3](./plan-fase3.md) · [Base de datos v4](./base-de-datos.md) · [Normas de trabajo](./normas-de-trabajo.md)
