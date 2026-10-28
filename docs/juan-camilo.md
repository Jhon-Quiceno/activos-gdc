# Juan Camilo — Carga de datos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Bloque: Importación · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Cierras el único «importante» que quedó pendiente de tu bloque: las cargas posteriores al inventario inicial, sin duplicar lo que ya existe.

## Lo que te deja Jhon

Todo lo de la Fase 2: `DependenciaEquivalencia::resolver()`, la plantilla oficial de carga de equipos, las cargas de personas, valoraciones y datos de adquisición ya funcionando sobre el modelo v4.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 7 | **RF-42, parte 1:** al cargar una plantilla de equipos, reconocer filas que ya existen (por serial o código de activo) en vez de crear un duplicado; separar en la vista previa los que son altas nuevas de los que son actualizaciones. | Reconocimiento de equipos existentes. |
| 8 | **RF-42, parte 2:** actualizar solo los datos de la ficha de los equipos reconocidos (nunca ubicación ni responsable, que exigen traslado firmado, RF-20); las diferencias de ubicación o responsable se listan como «requiere traslado» en el reporte de la carga, dejando un evento `actualizacion_datos` con lo que cambió. | Carga posterior sin duplicar ni pisar movimientos. |
| 9–10 | Prueba cruzada del bloque de Equipos (Juan José); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-42. Detalle en el [Análisis v4.2](./analisis-requerimientos.md), sección 13.

## Pruebas mínimas

- Cargar dos veces el mismo archivo no duplica ningún equipo.
- Una fila con ubicación distinta a la actual queda marcada «requiere traslado», no actualiza la asignación.
- La carga posterior deja un evento por cada equipo que cambió, con el campo, el valor anterior y el nuevo.

## Normas

Rama `feature/importacion-<tarea>` desde `develop`; escribes en `app/Livewire/Importacion`, sus vistas y `tests/Feature/Importacion`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/importacion-cargas-posteriores
```

---

**Ver también:** [Plan de Fase 3](./plan-fase3.md) · [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
