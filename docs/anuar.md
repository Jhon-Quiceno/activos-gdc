# Anuar — Núcleo 2: Movimientos y Levantamientos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Bloques: Movimientos y Levantamientos · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Cierras dos de los «importantes» que quedaron fuera de la Fase 2: el destino del componente retirado y el listado de pendientes de firma, y sumas el traslado del puesto completo que depende del trabajo de Juan José en la ficha del equipo.

## Lo que te deja Jhon

- **F3-1** (`puestos_trabajo.activo`, único por piso) antes del día 4.
- Todo lo de la Fase 2: `personas` con los campos oficiales, `<x-ui.ubicacion-select>`, `Sede::direccionVisible()`, `LevantamientoActivos`.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1 | **RF-17:** en el formulario de cambio de componente (`ComponenteForm`), registrar el destino del retirado (bodega, otro equipo con su serial/código de activo, o descarte) usando `cambios_componente.destino_retirado` y `equipo_destino_id` (ya existen desde la Fase 2, sin uso en pantalla). Si el destino es «otro equipo», ese componente aparece en la configuración actual del equipo destino. | Destino del componente registrado. |
| 3 | **RF-31:** pantalla de pendientes de firma completa: todos los eventos con `estado_firma = pendiente`, filtrable por tipo y por antigüedad, con enlace directo a subir el documento firmado. | Pendientes de firma listados. |
| 4 | **RF-05 (traslado del puesto completo):** en el traslado (`TrasladoForm`), opción de trasladar todos los equipos de un puesto de una vez (usa la asignación a puesto que hizo Juan José), generando un único juego de formatos para el puesto. | Traslado de puesto. |
| 9–10 | Prueba cruzada del bloque de Administración (Manuel); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-05 (parte traslado), RF-17, RF-31. Detalle en el [Análisis v4.2](./analisis-requerimientos.md), sección 13, y en [base-de-datos.md, sección 10](./base-de-datos.md#10-fase-3--cambios-de-esquema-los-crea-el-líder).

## Pruebas mínimas

- Retirar un componente con destino «otro equipo» lo deja en la configuración del equipo destino, con su propio evento.
- Los pendientes de firma solo listan eventos sin los dos documentos completos.
- Trasladar un puesto genera un solo juego de formatos para todos sus equipos, no uno por equipo.

## Normas

Ramas `feature/movimientos-<tarea>` y `feature/levantamientos-<tarea>` desde `develop`. Escribes en `app/Livewire/Movimientos`, `app/Livewire/Levantamientos`, sus vistas y sus carpetas de `tests/Feature`. Todo pasa por `HistorialService::registrar()`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/movimientos-destino-componente
```

---

**Ver también:** [Plan de Fase 3](./plan-fase3.md) · [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
