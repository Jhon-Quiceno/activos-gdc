# Anuar — Núcleo 2: Movimientos y Levantamientos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Movimientos y Levantamientos (nuevo) · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Sigues con los procesos que generan papel firmado. Primero pasas responsables y traslados a la persona y la ubicación oficiales; después construyes el bloque nuevo de **Levantamientos**: el inventario de activos por dependencia y proceso, con acta de aceptación firmada (Manual GT-M4, 4.1 y 4.1.2). RF-22 (entrega consolidada) y RF-26 (anular baja) ya quedaron hechos en la Fase 1.

## Lo que ya te dejó Jhon

- `personas` con `tipo_documento`, `numero_documento`, `nombres`, `apellidos`, `correo_institucional`, `cargo_id` (catálogo del Decreto 607), `grado`, `encargo`, `jefe_inmediato_id`, `planta`, `tipo_provision`, `numero_contrato` y `fecha_fin_vinculacion`. Constantes `Persona::TIPOS_DOCUMENTO`, `VINCULACIONES` y `PROVISIONES`; ayudas `nombreCompleto()` y `cargoTexto()`.
- `<x-ui.ubicacion-select sede="sedeId" piso="pisoId" dependencia="dependenciaId" />`: al elegir la sede solo muestra sus pisos y sus dependencias (al elegir el piso, solo las de ese piso), con el enlace «Ver todas las dependencias» para los casos que el PAMP no cubre. Al editar, si la dependencia guardada no está en la sede, abre la lista completa sola.
- `Sede::direccionVisible()`: nunca devuelve la dirección de la Casa Refugio (RN-28).
- `LevantamientoActivos` (tabla `levantamientos_activos`), la ruta `/levantamientos`, el menú y la pantalla de arranque `app/Livewire/Levantamientos/Index.php`.
- `ValoracionActivo::registrar(..., levantamiento: $levantamiento)` enlaza la valoración con el levantamiento.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1 | **Adaptar responsables** (paso 3 del plan): crear y editar persona con los campos nuevos y el cargo del catálogo; enmascarar `numero_documento` (RN-12) con la clase `Cedula` que ya existe. Dejar de usar `nombre`, `cedula` y `cargo` en Movimientos y en los PDF. | Personas sin columnas viejas. |
| 2 | **Adaptar el traslado** con `x-ui.ubicacion-select` (RF-19, RF-20) y quitar el uso de `asignacion->sede_id`: la sede sale del piso. Formatos de entrega y baja con cargo oficial y `direccionVisible()`. | Traslado sobre el modelo nuevo. |
| 3 | Paz y salvo (RF-23): equipos a cargo de una persona en un PDF, y aviso de quienes tienen `fecha_fin_vinculacion` próxima y aún tienen equipos. | Paz y salvo. |
| 4–5 | **Levantamientos, parte 1 (RF-59):** crear un levantamiento por dependencia, proceso y sede; listar sus equipos con subdependencias (`Dependencia::idsDeRama`) y marcar los que no tienen valoración o están pendientes de verificar. | Levantamiento con su lista de equipos. |
| 6–7 | **Levantamientos, parte 2:** valorar desde el levantamiento (formulario de Juan José), acta de identificación y aceptación prellenada en PDF, matriz FOR.01 (exportación de Alex), subir el acta firmada y pasar a «Aceptado». | Un levantamiento completo. |
| 8 | Listado de bajas en un período para la dependencia que tramita la baja administrativa (RF-27). | Listado en PDF y Excel. |
| 9–10 | Prueba cruzada del bloque de Administración (Manuel); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-18, RF-19, RF-20, RF-22 (adaptar), RF-23, RF-27, RF-59. Detalle en el [Análisis v4.1](./analisis-requerimientos.md), secciones 5.4, 5.5 y 5.11, y CU-18.

## Pruebas mínimas

- Un levantamiento no pasa a «Aceptado» sin acta firmada.
- La lista de un levantamiento de una secretaría incluye sus direcciones y áreas.
- La dirección de la Casa Refugio no aparece en ningún PDF.
- Un traslado guarda el piso elegido, y la sede es la de ese piso.

## Normas

Ramas `feature/movimientos-<tarea>` y `feature/levantamientos-<tarea>` desde `develop`. Escribes en `app/Livewire/Movimientos`, `app/Livewire/Levantamientos`, sus vistas y sus carpetas de `tests/Feature`. Todo pasa por `HistorialService::registrar()`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/movimientos-persona-v4
```
