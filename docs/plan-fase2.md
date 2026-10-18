# Plan de Fase 2 — Reestructuración con fuentes oficiales

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Activos Tecnológicos — Fase 2
Basado en el [Análisis de requerimientos v4.1](./analisis-requerimientos.md) y el [modelo de datos v4](./base-de-datos.md)

Jhon (líder) · Juan José y Anuar (núcleo) · Juan Camilo, Alex y Manuel (apoyo) · 10 días hábiles
Versión 2.1 · 17 de octubre de 2026 · *Reemplaza la versión 1.0, que solo cubría los RF «importantes» de v3. La 2.1 quita el mantenimiento, que sigue fuera del alcance.*

---

## 0. Por qué cambia el plan

El profesor responsable cambió la fuente del análisis: el sistema ya no se diseña a partir del Excel del inventario 2026, sino de la documentación oficial (Manual GT-M4, formato FOR.01, Decretos 607, 608 y 609 de 2023 y las plantas de personal; del Programa Anual de Mantenimiento solo se toman sedes, pisos y ubicaciones). El Excel queda solo para cargar datos.

Frente al modelo que corre hoy (ver la comparación en [base-de-datos.md §2](./base-de-datos.md#2-comparación-modelo-actual-v3-frente-al-modelo-nuevo-v4)), se modifican 17 de las 22 tablas, se agregan 9 y llegan dos módulos nuevos: **valoración del activo** (GT-M4) y **levantamiento con acta de aceptación**. **El mantenimiento sigue fuera del alcance**, igual que en la Fase 1.

La Fase 1 queda cerrada tal como está en `develop`; nada de lo construido se tira. Formularios, selectores y reportes se adaptan al modelo nuevo.

---

## 1. Antes del día 1 (lo hace Jhon)

Nadie arranca hasta que esto esté en `develop`, igual que en la Fase 1.

| # | Tarea | Desbloquea a |
|---|---|---|
| 1 | Revisar con el profesor el análisis v4.1 y el modelo v4 (preguntas abiertas 1 a 3 del análisis). | Todos |
| 2 | Fusionar los PR #9 y #10 (edición de equipo y QR) antes de tocar el esquema, para que nadie tenga que rehacerlos sobre el modelo nuevo. | Juan José, Manuel |
| 3 | Migraciones v4 según [base-de-datos.md §6](./base-de-datos.md#6-plan-de-migración-de-v3-a-v4), con sus modelos Eloquent y relaciones (incluidas las reglas de la §7). | Todos |
| 4 | Reescribir `CatalogosSeeder` con los valores oficiales (§5 de base-de-datos.md) y ajustar `EquiposDemoSeeder` y las factories. | Todos |
| 5 | `HistorialService::registrar()` con el tipo de evento `valoracion`. | Juan José |
| 6 | Componentes compartidos nuevos: `x-ui.dependencia-select` (árbol con buscador, RF-60) y `x-ui.ubicacion-select` (sede → piso → dependencia, RF-19). | Todos los formularios |
| 7 | Menú y ruta vacía del bloque nuevo `/levantamientos`. | Anuar |
| 8 | Arreglar la suite de Pest: todas las pruebas que asumen sedes, pisos o dependencias inventadas. | Todos |

---

## 2. Reparto

| Persona | Bloque | RF a cargo | Carpetas |
|---|---|---|---|
| **Jhon** | Líder: modelo, base compartida, revisión | Sección 1; RF-12 (eventos de valoración) | migraciones, modelos, seeders, `x-ui.*`, `HistorialService` |
| **Juan José** | Equipos, hoja de vida y valoración | RF-01, 02, 04, 07, 09, 33, 52, 56, 57, 58 | `Equipos` |
| **Anuar** | Movimientos y **Levantamientos** (bloque nuevo) | RF-18, 19, 20, 22, 23, 26, 59 | `Movimientos`, `Levantamientos` |
| **Juan Camilo** | Carga de datos | RF-38, 39, 40, 41, 55 y las cargas de valoraciones y de datos de adquisición | `Importacion` |
| **Alex** | Reportes (vuelve al equipo) | RF-34, 35, 36, 37, 53, 54 y los reportes nuevos de la §11 del análisis | `Reportes` |
| **Manuel** | Administración y QR | RF-44, 45, 47, 49, 51, 60 | `Admin`, `Qr` |

Cada persona tiene su documento con el detalle día a día:
[Juan José](./juan-jose.md) · [Anuar](./anuar.md) · [Juan Camilo](./juan-camilo.md) · [Alex](./alex.md) · [Manuel](./manuel.md)

---

## 3. Calendario

| Días | Meta del equipo | Hito |
|---|---|---|
| 0 | Jhon deja listas las migraciones, los seeders oficiales y los componentes compartidos. | `migrate:fresh --seed` con catálogos oficiales y Pest en verde. |
| 1–2 | Cada bloque adapta lo que ya existe al modelo v4 (personas, ubicación, ficha, filtros, catálogos). | Fase 1 funcionando otra vez sobre el modelo nuevo. |
| 3–5 | Funcionalidad nueva principal: valoración, catálogos administrables, cargas de personas y equipos, matriz FOR.01. | Día 5: demo equipo → valoración → matriz FOR.01. |
| 6–8 | Segundo nivel: levantamiento con acta, paz y salvo, anular baja, QR por lotes, tablero, carga real de personas y del inventario 2026. | Día 8: datos reales cargados en el ambiente de pruebas y un levantamiento aceptado. |
| 9 | Congelar funciones. Cada persona prueba el bloque de otra (rotación de la §5). | Lista de errores priorizada. |
| 10 | Correcciones, actualizar manuales, demo al profesor. | v2.0 etiquetada en `main`. |

---

## 4. Dependencias entre bloques

```
Jhon (modelo + seeders + selectores)
 ├─► Juan José: ficha y valoración ──► Alex: matriz FOR.01 y riesgos (RF-53, RF-54)
 │                                 └─► Anuar: levantamiento (RF-59) usa la valoración y la matriz
 ├─► Anuar: traslado con persona y ubicación nuevas
 ├─► Manuel: catálogos y equivalencias ──► Juan Camilo: cargas que resuelven por equivalencia
 └─► Juan Camilo: personas cargadas ──► Anuar: paz y salvo con fecha de fin de vinculación
```

**Riesgo principal:** la valoración de Juan José alimenta los reportes de Alex y el levantamiento de Anuar. Si el día 4 no se puede valorar un equipo, Alex pasa a ayudarle con la pantalla y el levantamiento empieza por la parte del acta (que no depende de la valoración).

**Riesgo secundario:** sin catálogos administrables (Manuel, días 1–3), las cargas de Juan Camilo no pueden corregir equivalencias. Mientras tanto, Juan Camilo trabaja con las equivalencias del seeder.

---

## 5. Pruebas cruzadas del día 9

| Prueba | Bloque probado |
|---|---|
| Juan José | Levantamientos (Anuar) |
| Anuar | Administración y catálogos (Manuel) |
| Juan Camilo | Equipos y valoración (Juan José) |
| Alex | Carga de datos (Juan Camilo) |
| Manuel | Reportes (Alex) |

---

## 6. Qué queda para la Fase 3

Puestos de trabajo (RF-05), fotos (RF-06), tipos de evento configurables (RF-13), configuración por fecha (RF-16), destino de lo retirado (RF-17), listado de bajas para la dependencia (RF-27), pendientes de firma como vista operativa (RF-31), cargas posteriores sin duplicar (RF-42) y firma en pantalla (RF-32).

**Fuera del alcance (no es Fase 3):** mantenimiento preventivo y correctivo (RN-08).

---

## 7. Reglas

Las mismas de la Fase 1 ([normas-de-trabajo.md](./normas-de-trabajo.md)): ramas `feature/<bloque>-<tarea>` desde `develop`, PR obligatorio, solo Jhon crea migraciones, `migrate:fresh --seed` siempre, Pest en verde antes de abrir PR, commits en español con prefijo Conventional Commit en inglés. Dos reglas nuevas para esta fase:

- **Nada de datos personales de más.** De la planta solo se toman los campos de RF-18 (RNF-19). Ningún seeder, factory, test ni captura de pantalla usa nombres o documentos reales, y las plantas reales no se suben al repositorio.
- **Nombres oficiales.** Dependencias, sedes y cargos salen del seeder oficial; si falta uno, se le pide a Jhon, no se inventa.

---

**Ver también:** [Análisis de requerimientos v4.1](./analisis-requerimientos.md) · [Base de datos v4](./base-de-datos.md) · [Plan de la Fase 1](./plan-2-semanas.md)
