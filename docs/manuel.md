# Manuel — Administración e Inicio

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Bloques: Administración e Inicio · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Llevas la parte de pantalla de RF-13 (tipos de evento configurables, cuyo núcleo construye Jhon): la administración de los tipos y que el tablero de inicio los muestre con su nombre real.

> **Nota (30 de octubre):** Manuel ya no desarrolla. Jhon construyó las tareas del
> día 5 y el día 7 de esta ficha directamente sobre `develop` (rama
> `feature/admin-tipos-evento`), siguiendo este documento tal cual estaba. El
> detalle de qué se hizo y qué sigue pendiente (prueba cruzada de Reportes,
> día 9-10) está en el estado general del [Plan de Fase 3](./plan-fase3.md#1-punto-de-partida).

## Lo que te deja Jhon

- **F3-3** (`tipos_evento` + modelo `TipoEvento`, `HistorialService` validando contra la tabla) antes del día 5.
- Todo lo de la Fase 2: catálogos oficiales, árbol de dependencias, usuarios enlazados a su persona, el tablero de inicio con sus indicadores.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 5 | ✅ **RF-13 (pantalla):** en Administración, pestaña o sección nueva para crear, renombrar, describir, marcar «requiere firma» y desactivar tipos de evento configurables. Los 8 tipos del sistema se ven pero no se editan ni se desactivan (`es_sistema`); nunca se borra un tipo con eventos. | `/admin/tipos-evento` (`TiposEventoPanel`), fusionado. |
| 7 | ✅ **RF-13 (apoyo):** en el tablero de inicio, leer las etiquetas de tipo de evento (`$tipoEventoLabels` en `dashboard.blade.php`) desde `TipoEvento` en vez del arreglo fijo. | Tablero con tipos configurables. |
| 9–10 | ⬜ Prueba cruzada del bloque de Reportes (Alex); correcciones. | Pendiente: depende de que Alex aplique su parte de RF-13 sobre `develop`. |

## Requerimientos a cargo

RF-13 (pantalla y tablero). Detalle en el [Análisis v4.2](./analisis-requerimientos.md), sección 13, y en [base-de-datos.md, sección 10.4](./base-de-datos.md#104-tipos_evento-rf-13).

## Pruebas mínimas

- ✅ Un tipo de evento nuevo creado desde Administración se puede registrar sobre un equipo sin desarrollo (`tests/Feature/Admin/TiposEventoPanelTest.php`).
- ✅ Un tipo del sistema no se puede renombrar ni desactivar desde la pantalla, ni forzando la llamada (`tests/Feature/Admin/TiposEventoPanelTest.php`).
- ✅ Un tipo configurable con eventos no se puede borrar (lo bloquea la llave foránea de `eventos.tipo`, F3-3).
- ✅ El tablero muestra el nombre del tipo nuevo, no su clave (`tests/Feature/Inicio/TableroTest.php`).

## Normas

Ramas `feature/admin-<tarea>` e `feature/inicio-<tarea>` desde `develop`; escribes en `app/Livewire/Admin`, sus vistas, `resources/views/dashboard.blade.php` y sus carpetas de `tests/Feature`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/admin-tipos-evento
```

---

**Ver también:** [Plan de Fase 3](./plan-fase3.md) · [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
