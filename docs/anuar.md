# Anuar — Núcleo 2: Movimientos y Mantenimiento

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Movimientos y Mantenimiento (nuevo) · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Sigues con los procesos que generan papel firmado y sumas un bloque nuevo: el **mantenimiento preventivo y correctivo** del Programa Anual de Mantenimiento (PAMP). Primero adaptas los traslados a la persona y la ubicación oficiales; después construyes la ejecución del mantenimiento.

## Heredado de la Fase 1

- RF-22 (formato de entrega consolidado por persona): confirmar si `GestorAsignaciones::equiposACargo()` ya tiene pantalla; si no, sale junto con el paz y salvo (día 6).
- Los 4 hallazgos menores del PR #6 (`TrasladoForm` no limpia `dependenciaId` en bodega, `descargarPrellenado()` sin validar el tipo, `ComponenteForm` fuera de `RegistroMovimientos`, `estaDadoDeBaja()` sin uso): corregirlos el día 1, que de todos modos tocas esos archivos.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1 | Persona v4 (RF-18): tipo y número de documento, nombres y apellidos, cargo del catálogo con grado y encargo, dependencia real, jefe inmediato, vinculación y provisión. Enmascaramiento RN-12 con el campo nuevo. Hallazgos del PR #6. | Crear persona con el modelo nuevo. |
| 2 | Ubicación v4 en el traslado (RF-19, RF-20) con `x-ui.ubicacion-select`: sede → piso de esa sede → dependencia, proponiendo las dependencias de ese piso (`ubicaciones_dependencia`). Formatos de entrega y baja con cargo oficial y ubicación nueva; la dirección reservada nunca sale (RN-29). | Traslado completo con PDF sobre el modelo nuevo. |
| 3–4 | **Mantenimiento (RF-61):** registrar un mantenimiento preventivo o correctivo desde la hoja de vida, con checklist (inspección, limpieza, actualización, revisión), hallazgos, insumos, duración, estado resultante y ejecutor; actualiza el estado de funcionamiento. Si cambia una pieza, ofrecer el cambio de componente en el mismo flujo (RN-08). | Mantenimiento correctivo funcionando. |
| 5 | **Lista de trabajo (RF-62):** elegir una programación del mes y ver los equipos asignados hoy a esa dependencia y sede; registrar el mantenimiento de cada uno desde ahí; RN-28 si el equipo no corresponde. | Mantenimiento preventivo por programación. |
| 6 | Formato de mantenimiento en PDF por equipo y por programación (RF-64). Paz y salvo (RF-23) y entrega consolidada (RF-22), con aviso de personas con `fecha_fin_vinculacion` próxima. | Dos PDF nuevos. |
| 7–8 | Pruebas con el PAMP real cargado por Juan Camilo; ajustes con Alex para que el cumplimiento (RF-63) lea bien tus eventos. | Mantenimientos de un mes completo en pruebas. |
| 9–10 | Prueba cruzada del bloque de Levantamientos (Manuel); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-18, RF-19, RF-20, RF-22, RF-23, RF-61, RF-62, RF-64. Detalle en el [Análisis v4](./analisis-requerimientos.md), secciones 5.4 y 5.12, CU-20, y las tablas de mantenimiento en [base-de-datos.md §4.6](./base-de-datos.md#46-mantenimiento).

## Pruebas mínimas

- Un mantenimiento queda como evento `mantenimiento` a nombre del usuario y no se puede editar.
- Un equipo dado de baja no admite mantenimientos (RN-10).
- Si el mantenimiento cambia una pieza, quedan los dos eventos (mantenimiento y cambio de componente).
- La dirección de la Casa Refugio no aparece en ningún PDF.
- Un traslado a un piso de otra sede es rechazado (RN-21).

## Depende de / conecta con

- **Jhon:** tablas de mantenimiento, tipo de evento, `x-ui.ubicacion-select`, ruta `/mantenimiento`.
- **Juan Camilo** carga el plan y las programaciones desde el Excel del PAMP (RF-60). Mientras tanto, usa la programación de ejemplo del seeder.
- **Manuel** tiene la pantalla de edición del plan; **Alex** reporta el cumplimiento a partir de tus eventos.

## Normas

Ramas `feature/movimientos-<tarea>` y `feature/mantenimiento-<tarea>` desde `develop`. Escribes en `app/Livewire/Movimientos`, `app/Livewire/Mantenimiento`, sus vistas y sus carpetas de `tests/Feature`. Todo pasa por `HistorialService::registrar()`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/mantenimiento-registro
```
