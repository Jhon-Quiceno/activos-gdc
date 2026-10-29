# Alex — Reportes

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Bloque: Reportes · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

En la Fase 3 no hay reportes nuevos; tu única tarea sale de RF-13 (tipos de evento configurables, de Jhon y Manuel): que un tipo nuevo se vea con su nombre real en los reportes de eventos, no con su clave interna.

## Lo que te deja Jhon

- **F3-3** (`tipos_evento` + modelo `TipoEvento`) antes del día 6.
- Todo lo de la Fase 2: los 15 reportes con sus filtros, la matriz FOR.01, los reportes de riesgo y obsolescencia.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 6 | **RF-13 (apoyo):** en los reportes que muestran el tipo de evento (Historial por equipo, Traslados, Pendientes de firma, Cambios de componentes), leer la etiqueta de `TipoEvento::nombre` en vez del arreglo fijo que hay hoy. Un tipo configurable nuevo aparece con su nombre sin tocar código. | Reportes de eventos con tipos configurables. |
| 9–10 | Prueba cruzada del bloque de Importación (Juan Camilo); correcciones. | Pest en verde. |

**Opcional, si sobra tiempo:** columna «Puesto» en los reportes por sede y piso (usa RF-05 de Juan José y Anuar).

## Requerimientos a cargo

Apoyo de RF-13. Detalle en el [Análisis v4.2](./analisis-requerimientos.md), sección 13.

## Pruebas mínimas

- Un tipo de evento configurable nuevo aparece con su nombre (no su clave) en el reporte de Historial por equipo.
- Los reportes existentes de traslados y bajas no cambian su salida.

## Normas

Rama `feature/reportes-<tarea>` desde `develop`; escribes en `app/Livewire/Reportes`, sus vistas y `tests/Feature/Reportes`. Solo lectura: los reportes no modifican datos. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/reportes-tipos-evento
```

---

**Ver también:** [Plan de Fase 3](./plan-fase3.md) · [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
