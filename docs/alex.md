# Alex — Reportes

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloque: Apoyo · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Vuelves con el bloque de Reportes. Tus 15 reportes de la Fase 1 se adaptan al modelo v4 (árbol de dependencias, pisos por sede, proceso, importancia), y entregas las **salidas oficiales nuevas**: la matriz de inventario de activos en el formato FOR.01 y el reporte de activos que van a gestión de riesgos.

## Lo que ya te dejó Jhon

- `Dependencia::idsDeRama($id)` para filtrar «una secretaría con todo lo suyo», y `<x-ui.dependencia-select>` con buscador.
- `valoraciones_activo` con `importancia` y `requiere_gestion_riesgo` ya calculados, y `$equipo->valoracionVigente`. El seeder demo trae unos 50 equipos valorados para trabajar desde el día 1.
- `SistemaOperativo->fin_soporte` y `sinSoporte()` para la obsolescencia; `Equipo::garantiaPorVencer()` y `vidaUtilSuperada()`.
- `Sede::direccionVisible()` (RN-28) y `Persona::nombreCompleto()`.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | **Adaptar los 15 reportes** (paso 3 del plan): la sede sale del piso (no de `asignacion->sede_id`), el responsable de `nombres`/`apellidos` y `numero_documento` enmascarado (RN-12), y el modelo de `modeloEquipo`. Filtro por dependencia con o sin subdependencias, por sede → piso, por proceso y por importancia. Calidad del inventario (RF-37) suma «sin valoración» y «sin datos de adquisición». | Reportes sin columnas viejas. |
| 3–4 | **Matriz FOR.01 (RF-53):** Excel con el encabezado oficial («Proceso Gestión de Seguridad de la Información · FOR.01 · Versión 00»), los grupos de columnas (Identificación, Atributo, Propiedad, Ubicación, Retención, Valoración) y las mismas columnas del formato, por proceso o dependencia. Déjala como una clase que reciba una lista de equipos: Anuar la reutiliza en el levantamiento. | Matriz igual al formato oficial. |
| 5 | **Activos que requieren gestión de riesgos (RF-54)** y **activos por importancia**. | Dos reportes. |
| 6–7 | Obsolescencia por fin de soporte del sistema operativo (fecha real, no solo «Windows 7»), garantías por vencer y vida útil superada. | Tres reportes. |
| 8 | Personas por retirarse con equipos a cargo y equipos por estructura (secretaría con sus direcciones). | Dos reportes. |
| 9–10 | Prueba cruzada del bloque de Carga (Juan Camilo); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-34, RF-35, RF-37, RF-53, RF-54 y los reportes nuevos de la sección 11 del [Análisis v4.1](./analisis-requerimientos.md). El tablero (RF-36) pasó a Manuel.

## Pruebas mínimas

- La matriz FOR.01 trae las mismas columnas, en el mismo orden, que el formato oficial.
- Un equipo con C = 4 aparece en el reporte de riesgos aunque su importancia sea media.
- Filtrar por «Secretaría de Hacienda» incluye los equipos de sus seis direcciones.
- Documentos enmascarados en las exportaciones masivas (RN-12) y ninguna dirección reservada.

## Normas

Rama `feature/reportes-<tarea>` desde `develop`; escribes en `app/Livewire/Reportes`, sus vistas y `tests/Feature/Reportes`. Solo lectura: los reportes no modifican datos. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/reportes-modelo-v4
```
