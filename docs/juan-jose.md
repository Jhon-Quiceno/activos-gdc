# Juan José — Núcleo 1: Equipos y QR

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Bloques: Equipos y QR · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Sigues con el bloque central: la ficha del equipo y su hoja de vida. En esta fase completas tres de los «importantes» que quedaron fuera de la Fase 2: agrupar equipos en un puesto de trabajo, adjuntar fotos y consultar la configuración en una fecha dada.

## Lo que te deja Jhon

- **F3-1** (`puestos_trabajo.activo`, único por piso) antes del día 4.
- **F3-2** (`fotos_equipo` + modelo `FotoEquipo`, con `$equipo->fotos` y `fotoPrincipal`) antes del día 6.
- Todo lo de la Fase 2: `ValoracionActivo::registrar()`, los selectores `<x-ui.dependencia-select>` y `<x-ui.ubicacion-select>`, `Dependencia::idsDeRama($id)`.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 4 | **RF-05 (parte ficha):** en el registro y la edición del equipo, asignarlo a un puesto de trabajo existente o crear uno nuevo (nombre o código, piso, dependencia). Filtro de equipos por puesto. El traslado del puesto completo lo hace Anuar (Movimientos) sobre esta misma asignación. | Un equipo pertenece a un puesto. |
| 5 | **RF-16:** en la hoja de vida, consultar la configuración de cómputo (componentes y software) que tenía el equipo en una fecha dada, a partir de `componentes.fecha_instalacion`/`fecha_retiro` y `eventos.valores`. | Configuración histórica consultable. |
| 6 | **RF-06** (usa F3-2): subir y quitar fotos del equipo desde la hoja de vida (vista frontal, placa con el serial…), marcar la principal (una sola por equipo), con su evento `actualizacion_datos`. Límite de tamaño y solo imágenes (RNF-19: son fotos del equipo, nunca de personas). | Fotos en la ficha. |
| 8 | Montar en la hoja de vida el componente compartido de Jhon para **registrar un evento de un tipo configurable** (botón «Registrar otro evento»). | Tipos de evento configurables usables desde la ficha. |
| 9–10 | Prueba cruzada del bloque de Administración (Manuel); pruebas propias y correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-05 (parte ficha), RF-06, RF-16. Detalle en el [Análisis v4.2](./analisis-requerimientos.md), sección 13, y en [base-de-datos.md, sección 10](./base-de-datos.md#10-fase-3--cambios-de-esquema-los-crea-el-líder).

## Pruebas mínimas

- Un equipo asignado a un puesto aparece al filtrar por ese puesto.
- La configuración consultada «al 1 de octubre» no muestra un componente instalado el 5 de octubre.
- Subir una segunda foto «principal» deja la anterior como no principal, no borra ninguna.

## Normas

Ramas `feature/equipos-<tarea>` y `feature/qr-<tarea>` desde `develop`; escribes en `app/Livewire/Equipos`, `app/Livewire/Qr`, sus vistas y sus carpetas de `tests/Feature`. Lo que falte en el modelo se le pide a Jhon. Todo cambio pasa por `HistorialService::registrar()`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/equipos-puestos-trabajo
```

---

**Ver también:** [Plan de Fase 3](./plan-fase3.md) · [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
