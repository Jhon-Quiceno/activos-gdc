# Juan Camilo — Carga de datos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloque: Apoyo · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

El Excel del inventario 2026 **dejó de ser la base del sistema**: ahora es solo una fuente de datos más. Tu bloque pasa de «importar ese Excel» a **cargar datos en el modelo oficial**: equipos con una plantilla propia, personas desde la planta de Talento Humano y el plan de mantenimiento desde el Excel del PAMP.

## Heredado de la Fase 1

- El emparejamiento por similitud (Levenshtein) sin umbral para sede y dependencia **se reemplaza**: ahora todo se resuelve con la tabla `dependencia_equivalencias` (RF-39), y si no hay equivalencia, la fila queda con error en vez de asociarse en silencio.
- Los 14 códigos de activo repetidos dentro del Excel 2026 siguen pendientes; se resuelven al mapear ese archivo a la plantilla nueva (día 7).

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1 | Diseñar la **plantilla oficial de carga de equipos** (`.xlsx` descargable desde la pantalla): columnas del modelo v4 (tipo, marca, modelo, serial, código de activo, sede, piso, dependencia, responsable por documento, propiedad, adquisición) y, opcional, las de valoración del FOR.01. Lista desplegable en cada catálogo. | Plantilla publicada. |
| 2–3 | Reescribir el importador sobre la plantilla (RF-38): vista previa, modo simulación, errores por fila, fila de origen, «Pendiente de verificar» (RF-41). Resolver sede → piso → dependencia con el modelo nuevo y con `dependencia_equivalencias` (RF-39); sin equivalencia = error de fila. | Importar la plantilla en la base de pruebas. |
| 4–5 | **Carga de personas desde la planta (RF-55):** leer los Excel de planta Central y Educación, tomar **solo** documento, nombres, cargo (código + denominación + grado), dependencia real, jefe inmediato, planta, vinculación, provisión y correo institucional; descartar en memoria todas las demás columnas (RNF-19). Resolver cargos contra el catálogo del Decreto 607 y dependencias contra las equivalencias. | Personas cargadas sin datos sensibles. |
| 6 | **Carga del PAMP (RF-60):** leer el Excel del plan (hoja «Programación Mantenimientos») y crear el plan de la vigencia y una programación por dependencia, sede y mes, con su impacto. | Plan 2026 cargado. |
| 7 | **Correspondencia del inventario 2026 con la plantilla:** script o pantalla que convierte el Excel de Forms a la plantilla (divide filas en equipos, normaliza códigos como ya hacías) para cargarlo por el flujo normal. Llenar `dependencia_equivalencias` con sus 175 variantes, junto a Manuel. | Inventario 2026 cargado por la plantilla. |
| 8 | Carga de valoraciones desde una matriz FOR.01 diligenciada (opcional si alcanza). Criterios de aceptación de la §12 del análisis. | Reporte de la carga. |
| 9–10 | Prueba cruzada del bloque de Equipos y valoración (Juan José); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-38, RF-39, RF-40, RF-41, RF-55, RF-60 (la carga; la pantalla de edición del plan es de Manuel). Detalle en el [Análisis v4](./analisis-requerimientos.md), secciones 5.8 y 12.

## Pruebas mínimas

- Una carga de planta con columnas de salario, RH o dirección no guarda ninguna de ellas (comprobado en la base).
- Una dependencia escrita con su nombre anterior al Decreto 609 se resuelve por equivalencia; una desconocida da error de fila.
- Un piso que no existe en la sede indicada da error de fila (RN-21).
- **Nunca** se usan las plantas reales en tests ni se suben al repositorio: los tests usan Excel sintéticos con las mismas cabeceras.

## Depende de / conecta con

- **Jhon:** modelo v4, catálogos oficiales sembrados.
- **Manuel:** pantalla de equivalencias (tú la llenas; él la administra).
- **Anuar** usa las programaciones que cargas; **Alex** compara tus conteos con los del PAMP.

## Normas

Rama `feature/importacion-<tarea>` desde `develop`; escribes en `app/Livewire/Importacion`, sus vistas y `tests/Feature/Importacion`. El alta histórica pasa por `HistorialService::registrar()` (`es_historico = true`). Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/importacion-plantilla-v4
```
