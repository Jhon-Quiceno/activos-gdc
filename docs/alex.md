# Alex — Reportes

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloque: Apoyo · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Vuelves al equipo con el bloque de Reportes. Tus 15 reportes de la Fase 1 siguen sirviendo, pero hay que adaptarlos al modelo v4 (árbol de dependencias, pisos por sede, proceso, importancia). Además entregas las **salidas oficiales nuevas**: la matriz de inventario de activos en el formato FOR.01, el reporte de activos que van a gestión de riesgos y el tablero de inicio.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Adaptar los 15 reportes al modelo v4 (RF-34): filtro por dependencia con o sin subdependencias (`x-ui.dependencia-select`), sede → piso, proceso, importancia; las sedes reservadas no muestran dirección (RN-28). Calidad del inventario (RF-37) + «sin valoración» y «sin datos de adquisición». | Reportes existentes en verde con el modelo nuevo. |
| 3–4 | **Matriz FOR.01 (RF-53):** exportación a Excel con el encabezado («Proceso Gestión de Seguridad de la Información · FOR.01 · Versión 00»), los grupos de columnas (Identificación, Atributo, Propiedad, Ubicación, Retención, Valoración) y las mismas columnas del formato, por proceso o dependencia. Usar `maatwebsite/excel` con estilos. | Matriz exportada igual al formato oficial. |
| 5 | **Activos que requieren gestión de riesgos (RF-54)** y **activos por importancia**. | Dos reportes. |
| 6–7 | **Tablero de inicio (RF-36):** totales por tipo, estado, sede y dependencia, importancia de los activos, pendientes de verificar y de valoración, comparados con los conteos del PAMP por sede. | Pantalla de inicio con indicadores. |
| 8 | Garantías y vida útil; personas por retirarse con equipos a cargo; equipos por estructura (secretaría con sus direcciones). | Tres reportes. |
| 9–10 | Prueba cruzada del bloque de Carga (Juan Camilo); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-34, RF-35, RF-36, RF-37, RF-53, RF-54 y los reportes nuevos de la sección 11 del [Análisis v4.1](./analisis-requerimientos.md).

## Pruebas mínimas

- La matriz FOR.01 trae las mismas columnas, en el mismo orden, que el formato oficial.
- Un equipo con C = 4 aparece en el reporte de riesgos aunque su importancia sea media.
- Filtrar por «Secretaría de Hacienda» incluye los equipos de sus seis direcciones.
- Cédulas enmascaradas en todas las exportaciones masivas (RN-12) y ninguna dirección reservada.

## Depende de / conecta con

- **Juan José:** `valoraciones_activo` (días 4–5). Mientras tanto, trabaja con valoraciones de factory.
- **Anuar** reutiliza tu exportación FOR.01 en el levantamiento: dejala como una clase que reciba una lista de equipos.
- **Juan Camilo:** datos reales cargados para probar los conteos.

## Normas

Rama `feature/reportes-<tarea>` desde `develop`; escribes en `app/Livewire/Reportes`, sus vistas y `tests/Feature/Reportes`. Solo lectura: los reportes no modifican datos. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/reportes-matriz-for01
```
