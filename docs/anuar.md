# Anuar — Núcleo 2: Movimientos y Levantamientos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Movimientos y Levantamientos (nuevo) · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Sigues con los procesos que generan papel firmado. Primero adaptas los traslados a la persona y la ubicación oficiales; después construyes el bloque nuevo de **Levantamientos**: el inventario de activos por dependencia y proceso, con acta de identificación y aceptación firmada, que exige el Manual GT-M4 (4.1 y 4.1.2). Es el mismo tipo de flujo que ya dominas: documento prellenado, firma y carga del firmado.

## Heredado de la Fase 1

- RF-22 (formato de entrega consolidado por persona): confirmar si `GestorAsignaciones::equiposACargo()` ya tiene pantalla; si no, sale junto con el paz y salvo (día 3).
- Los 4 hallazgos menores del PR #6 (`TrasladoForm` no limpia `dependenciaId` en bodega, `descargarPrellenado()` sin validar el tipo, `ComponenteForm` fuera de `RegistroMovimientos`, `estaDadoDeBaja()` sin uso): corregirlos el día 1, que de todos modos tocas esos archivos.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1 | Persona v4 (RF-18): tipo y número de documento, nombres y apellidos, cargo del catálogo con grado y encargo, dependencia real, jefe inmediato, vinculación y provisión. Enmascaramiento RN-12 con el campo nuevo. Hallazgos del PR #6. | Crear persona con el modelo nuevo. |
| 2 | Ubicación v4 en el traslado (RF-19, RF-20) con `x-ui.ubicacion-select`: sede → piso de esa sede → dependencia, proponiendo las dependencias de ese piso (`ubicaciones_dependencia`). Formatos de entrega y baja con cargo oficial y ubicación nueva; la dirección reservada nunca sale (RN-28). | Traslado completo con PDF sobre el modelo nuevo. |
| 3 | Paz y salvo (RF-23) y entrega consolidada (RF-22), con aviso de personas con `fecha_fin_vinculacion` próxima que aún tienen equipos. | Dos PDF. |
| 4–5 | **Levantamientos, parte 1 (RF-59):** crear un levantamiento por dependencia, proceso y sede; listar sus equipos (con subdependencias) y marcar los que no tienen valoración o están pendientes de verificar; desde cada equipo, abrir el formulario de valoración de Juan José. | Levantamiento con su lista de equipos. |
| 6–7 | **Levantamientos, parte 2:** acta de identificación y aceptación prellenada en PDF (propietario, custodios, lista de activos con su importancia); matriz FOR.01 del levantamiento con la exportación de Alex; subir el acta firmada y pasar a «Aceptado». | Levantamiento completo de una dependencia. |
| 8 | Anular una baja registrada por error (RF-26), con evento justificado que devuelve el equipo a su estado anterior. | Anulación de baja. |
| 9–10 | Prueba cruzada del bloque de Administración (Manuel); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-18, RF-19, RF-20, RF-22, RF-23, RF-26, RF-59. Detalle en el [Análisis v4.1](./analisis-requerimientos.md), secciones 5.4, 5.5 y 5.11, CU-18, y la tabla `levantamientos_activos` en [base-de-datos.md](./base-de-datos.md).

## Pruebas mínimas

- Un levantamiento no pasa a «Aceptado» sin acta firmada.
- La lista del levantamiento de una secretaría incluye los equipos de sus direcciones y áreas.
- La dirección de la Casa Refugio no aparece en ningún PDF.
- Un traslado a un piso de otra sede es rechazado (RN-21).
- Anular una baja devuelve el equipo al estado que tenía, y la anulación queda como evento.

## Depende de / conecta con

- **Jhon:** tablas nuevas, `x-ui.ubicacion-select`, ruta `/levantamientos`.
- **Juan José:** formulario de valoración (días 4–5), que se abre desde el levantamiento.
- **Alex:** exportación de la matriz FOR.01 (días 3–4), que reutilizas para el levantamiento.
- **Juan Camilo:** personas cargadas con su fecha de fin de vinculación, para el paz y salvo.

## Normas

Ramas `feature/movimientos-<tarea>` y `feature/levantamientos-<tarea>` desde `develop`. Escribes en `app/Livewire/Movimientos`, `app/Livewire/Levantamientos`, sus vistas y sus carpetas de `tests/Feature`. Todo pasa por `HistorialService::registrar()`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/movimientos-persona-v4
```
