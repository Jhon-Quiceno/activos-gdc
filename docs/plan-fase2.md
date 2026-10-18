# Plan de Fase 2 — Reestructuración con fuentes oficiales

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Activos Tecnológicos — Fase 2
Basado en el [Análisis de requerimientos v4](./analisis-requerimientos.md) y el [modelo de datos v4](./base-de-datos.md)

Jhon (líder) · Juan José y Anuar (núcleo) · Juan Camilo, Alex y Manuel (apoyo) · 10 días hábiles
Versión 2.0 · 17 de octubre de 2026 · *Reemplaza la versión 1.0 de este plan, que solo cubría los RF «importantes» de v3.*

---

## 0. Por qué cambia el plan

El profesor responsable cambió la fuente del análisis: el sistema ya no se diseña a partir del Excel del inventario 2026, sino de la documentación oficial (Manual GT-M4, formato FOR.01, Programa Anual de Mantenimiento y Decretos 607, 608 y 609 de 2023). El Excel queda solo para cargar datos. Eso cambia el modelo de datos en 9 de sus 22 tablas, agrega 12 tablas y trae tres módulos nuevos: **valoración del activo**, **mantenimiento** y **levantamiento**.

La Fase 1 queda cerrada tal como está en `develop`; nada de lo construido se tira. Formularios, selectores y reportes se adaptan al modelo nuevo.

---

## 1. Antes del día 1 (lo hace Jhon)

Nadie arranca hasta que esto esté en `develop`, igual que en la Fase 1.

| # | Tarea | Desbloquea a |
|---|---|---|
| 1 | Revisar con el profesor el análisis v4 y el modelo v4 (preguntas abiertas 1 a 4 del análisis). | Todos |
| 2 | Fusionar los PR #9 y #10 (edición de equipo y QR) antes de tocar el esquema, para que nadie tenga que rehacerlos sobre el modelo nuevo. | Juan José, Manuel |
| 3 | Migraciones v4 según [base-de-datos.md §6](./base-de-datos.md#6-plan-de-migración-de-v3-a-v4), con sus modelos Eloquent y relaciones (incluidas las reglas de la §7). | Todos |
| 4 | Reescribir `CatalogosSeeder` con los valores oficiales (§5 de base-de-datos.md) y ajustar `EquiposDemoSeeder` y las factories. | Todos |
| 5 | `HistorialService::registrar()` con los tipos `mantenimiento` y `valoracion`. | Juan José, Anuar |
| 6 | Componentes compartidos nuevos: `x-ui.dependencia-select` (árbol con buscador, RF-65) y `x-ui.ubicacion-select` (sede → piso → dependencia, RF-19). | Todos los formularios |
| 7 | Menú y rutas vacías de los bloques nuevos: `/mantenimiento` y `/levantamientos`. | Anuar, Manuel |
| 8 | Arreglar la suite de Pest: todas las pruebas que asumen sedes, pisos o dependencias inventadas. | Todos |

---

## 2. Reparto

| Persona | Bloque | RF a cargo | Carpetas |
|---|---|---|---|
| **Jhon** | Líder: modelo, base compartida, revisión | Sección 1; RF-12 (eventos de valoración) | migraciones, modelos, seeders, `x-ui.*`, `HistorialService` |
| **Juan José** | Equipos, hoja de vida y valoración | RF-01, 02, 04, 07, 09, 33, 52, 56, 57, 58 | `Equipos` |
| **Anuar** | Movimientos y **Mantenimiento** (bloque nuevo) | RF-18, 19, 20, 22, 23, 61, 62, 64 | `Movimientos`, `Mantenimiento` |
| **Juan Camilo** | Carga de datos | RF-38, 39, 40, 41, 55, 60 (carga del PAMP) | `Importacion` |
| **Alex** | Reportes (vuelve al equipo) | RF-34, 37, 53, 54, 63 y los reportes nuevos de la §11 del análisis | `Reportes` |
| **Manuel** | Administración, QR y **Levantamientos** (bloque nuevo) | RF-44, 45, 47, 49, 51, 59, 60 (pantalla del plan), 65 (administración del árbol) | `Admin`, `Qr`, `Levantamientos` |

Cada persona tiene su documento con el detalle día a día:
[Juan José](./juan-jose.md) · [Anuar](./anuar.md) · [Juan Camilo](./juan-camilo.md) · [Alex](./alex.md) · [Manuel](./manuel.md)

---

## 3. Calendario

| Días | Meta del equipo | Hito |
|---|---|---|
| 0 | Jhon deja listas las migraciones, los seeders oficiales y los componentes compartidos. | `migrate:fresh --seed` con catálogos oficiales y Pest en verde. |
| 1–2 | Cada bloque adapta lo que ya existe al modelo v4 (personas, ubicación, ficha, filtros, catálogos). | Fase 1 funcionando otra vez sobre el modelo nuevo. |
| 3–5 | Funcionalidad nueva principal: valoración, mantenimiento, cargas, matriz FOR.01, catálogos. | Día 5: demo equipo → valoración → mantenimiento → matriz FOR.01. |
| 6–8 | Módulos de segundo nivel: levantamiento con acta, cumplimiento del PAMP, paz y salvo, QR por lotes, carga real de personas y del inventario 2026. | Día 8: datos reales cargados en el ambiente de pruebas. |
| 9 | Congelar funciones. Cada persona prueba el bloque de otra (rotación de la §5). | Lista de errores priorizada. |
| 10 | Correcciones, actualizar manuales, demo al profesor. | v2.0 etiquetada en `main`. |

---

## 4. Dependencias entre bloques

```
Jhon (modelo + seeders + selectores)
 ├─► Juan José: ficha y valoración ──► Alex: matriz FOR.01 y riesgos (RF-53, RF-54)
 │                                 └─► Manuel: levantamiento (RF-59) usa la valoración
 ├─► Anuar: traslado con persona y ubicación nuevas ──► Anuar: mantenimiento
 ├─► Juan Camilo: carga del PAMP (RF-60) ──► Anuar: lista de trabajo (RF-62) ──► Alex: cumplimiento (RF-63)
 └─► Manuel: catálogos ──► Juan Camilo: equivalencias para cargar
```

**Riesgo principal:** la valoración de Juan José alimenta los reportes de Alex y el levantamiento de Manuel. Si el día 4 no se puede valorar un equipo, Alex pasa a ayudarle con la pantalla y la matriz FOR.01 se mueve al día 7.

**Riesgo secundario:** la carga del PAMP (Juan Camilo) bloquea la lista de trabajo de mantenimiento. Mientras tanto, Anuar trabaja con una programación creada a mano por seeder.

---

## 5. Pruebas cruzadas del día 9

| Prueba | Bloque probado |
|---|---|
| Juan José | Mantenimiento (Anuar) |
| Anuar | Levantamientos (Manuel) |
| Juan Camilo | Valoración y ficha (Juan José) |
| Alex | Carga de datos (Juan Camilo) |
| Manuel | Reportes (Alex) |

---

## 6. Qué queda para la Fase 3

Puestos de trabajo (RF-05), fotos (RF-06), tipos de evento configurables (RF-13), configuración por fecha (RF-16), destino de lo retirado (RF-17), anular baja (RF-26), listado de bajas para la dependencia (RF-27), pendientes de firma como vista operativa (RF-31), tablero (RF-36), cargas posteriores sin duplicar (RF-42) y firma en pantalla (RF-32). Si sobra tiempo el día 9, primero el tablero (RF-36).

---

## 7. Reglas

Las mismas de la Fase 1 ([normas-de-trabajo.md](./normas-de-trabajo.md)): ramas `feature/<bloque>-<tarea>` desde `develop`, PR obligatorio, solo Jhon crea migraciones, `migrate:fresh --seed` siempre, Pest en verde antes de abrir PR, commits en español con prefijo Conventional Commit en inglés. Dos reglas nuevas para esta fase:

- **Nada de datos personales de más.** De la planta solo se toman los campos de RF-18 (RNF-19). Ningún seeder, factory, test ni captura de pantalla usa nombres o documentos reales: las plantas reales no se suben al repositorio.
- **Nombres oficiales.** Dependencias, sedes y cargos salen del seeder oficial; si falta uno, se le pide a Jhon, no se inventa.

---

**Ver también:** [Análisis de requerimientos v4](./analisis-requerimientos.md) · [Base de datos v4](./base-de-datos.md) · [Plan de la Fase 1](./plan-2-semanas.md)
