# Juan José — Núcleo 1: Equipos, hoja de vida y valoración

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloque: Núcleo 1 · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Sigues llevando el bloque central: la ficha del equipo y su hoja de vida. En esta fase la ficha crece con los datos oficiales del formato FOR.01 y de adquisición, y se suma la **valoración del activo del Manual GT-M4**, que después usan Alex (matriz FOR.01, riesgos) y Anuar (levantamiento).

## Heredado de la Fase 1

Edición de equipo, QR real y filtros (PR #9 y #10): Jhon los fusiona **antes** del cambio de esquema, así que no tienes que rehacerlos. Tu bloque de la Fase 1 queda cerrado.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Adaptar el registro y la edición al modelo v4: modelo del catálogo (marca → modelo), número de activo automático (RN-27), descripción, proceso (por defecto el de la dependencia), propietario tercero desde `proveedores`, estado de funcionamiento como lista. Búsqueda por número de activo y por rama del árbol de dependencias (RF-07, con `x-ui.dependencia-select`). | Registrar y editar un equipo con la ficha nueva. |
| 3 | Datos de adquisición (RF-52): forma de ingreso, fecha, valor COP, proveedor, contrato, factura, garantía, vida útil. Avisos en la hoja de vida: garantía por vencer y vida útil superada. Configuración de cómputo con ofimática, licencias, dominio, IP y MAC. | Ficha completa. |
| 4–5 | **Valoración GT-M4 (RF-56, RF-57):** formulario en la hoja de vida con las escalas de las tablas 3, 4 y 5 (texto de cada nivel), cálculo de total, importancia y «requiere gestión de riesgos», validaciones RN-22, RN-24 y RN-25. Registro vía `HistorialService` (tipo `valoracion`). | Valorar un equipo. |
| 6 | Reclasificación con historial (RF-58): la anterior queda no vigente; se ven todas las valoraciones en la hoja de vida. | Historial de valoraciones. |
| 7 | Hoja de vida (RF-09): bloques nuevos de valoración vigente (con su historial) y datos de adquisición; responsable con cargo oficial. PDF de la hoja de vida (RF-33) con esas secciones. | Hoja de vida v4 y su PDF. |
| 8 | Ayudar a Anuar a enganchar la valoración dentro del levantamiento (RF-59). | Valoración usable desde el levantamiento. |
| 9–10 | Prueba cruzada del bloque de Levantamientos (Anuar); pruebas propias y correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-01, RF-02, RF-04, RF-07, RF-09, RF-33, RF-52, RF-56, RF-57, RF-58. Detalle en el [Análisis v4.1](./analisis-requerimientos.md), secciones 5.1, 5.2 y 5.11, y la tabla `valoraciones_activo` en [base-de-datos.md §4.5](./base-de-datos.md#45-valoraciones_activo-gt-m4).

## Pruebas mínimas

- Total = C + I + D; importancia según la tabla 6; marca de riesgo si algún criterio vale 4 o la importancia es alta o muy alta.
- No se puede valorar un activo de soporte (RN-25), ni guardar una clasificación distinta de «no aplica» sin datos personales (RN-24).
- Una sola valoración vigente por equipo, incluso con dos guardados simultáneos (lock, como en RN-03).
- El número de activo no cambia al editar.

## Depende de / conecta con

- **Jhon:** migraciones v4, `x-ui.dependencia-select`, tipo de evento `valoracion`.
- **Alex** lee `valoraciones_activo` para la matriz FOR.01 y el reporte de riesgos.
- **Anuar** abre tu formulario de valoración desde el levantamiento.

## Normas

Rama `feature/equipos-<tarea>` desde `develop`; solo escribes en `app/Livewire/Equipos`, `resources/views/livewire/equipos` y `tests/Feature/Equipos`; los campos que falten se le piden a Jhon; todo cambio pasa por `HistorialService::registrar()`; Pest en verde antes del PR. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/equipos-valoracion
```
