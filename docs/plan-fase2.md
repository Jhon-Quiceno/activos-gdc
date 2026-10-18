# Plan de Fase 2 — Reestructuración con fuentes oficiales

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Activos Tecnológicos — Fase 2
Basado en el [Análisis de requerimientos v4.1](./analisis-requerimientos.md) y el [modelo de datos v4](./base-de-datos.md)

Jhon (líder) · Juan José y Anuar (núcleo) · Juan Camilo, Alex y Manuel (apoyo) · 10 días hábiles
Versión 2.2 · 17 de octubre de 2026

---

## 0. Qué cambia

El análisis ya no sale del Excel del inventario 2026 sino de la documentación oficial: Manual GT-M4, formato FOR.01, Decretos 607, 608 y 609 de 2023 y plantas de personal. Del Programa Anual de Mantenimiento solo se toman sedes, pisos y ubicaciones. El Excel queda solo para cargar datos. **El mantenimiento sigue fuera del alcance.**

Frente al modelo que corre hoy ([comparación completa](./base-de-datos.md#2-comparación-modelo-actual-v3-frente-al-modelo-nuevo-v4)) se modifican 17 de las 22 tablas, se agregan 9 y ninguna se elimina. Llegan dos módulos nuevos: **valoración del activo** (GT-M4) y **levantamiento con acta de aceptación**. Lo construido en la Fase 1 no se tira: se adapta al modelo nuevo.

---

## 1. Lo que se debe cumplir antes de empezar algo nuevo

Va **en este orden**. Nadie arranca su bloque de la Fase 2 hasta que los pasos 1 y 2 estén en `develop`.

### Paso 1 — Cerrar lo pendiente de la Fase 1 (Jhon y Juan José)

| # | Pendiente | Qué hacer | Por qué antes de la base de datos |
|---|---|---|---|
| 1.1 | **PR #9** (edición de equipo) | Revisar y fusionar. | Toca `Crear.php`, `Editar.php` y la hoja de vida, que cambian con el modelo nuevo. Si se migra primero, el PR deja de funcionar y hay que rehacerlo. |
| 1.2 | **PR #10** (formato oficial, QR, filtros, traslados y bajas) | Revisar y fusionar después del #9: comparten archivos y habrá conflictos chicos. | Toca Equipos, Movimientos, los PDF y el QR: casi todo lo que el modelo nuevo modifica. Ninguno de los dos PR trae migraciones, así que no chocan con el esquema. |
| 1.3 | Pest en verde en `develop` y etiqueta `v1.0` en `main`. | Correr la suite completa. | Deja una versión estable a la que volver si algo de la Fase 2 sale mal. |

**Lo que NO se hace ahora:** cargar el inventario 2026 con el importador actual. Ese importador trabaja con las sedes, pisos y dependencias inventadas del modelo viejo, que desaparecen. La carga real se hace en la Fase 2 con la plantilla nueva (Juan Camilo, día 7). Lo que sí sigue en pie es pedirle a Almacén que corrija los 14 códigos de activo repetidos dentro del archivo.

### Paso 2 — Cambiar la base de datos (Jhon, «día 0»)

| # | Tarea | Desbloquea a |
|---|---|---|
| 2.1 | Revisar con el profesor el análisis v4.1 y el modelo v4; cerrar las preguntas abiertas 1 a 3 (instituciones educativas, tipos de activo, propietario y custodio). | Todos |
| 2.2 | Migraciones v4 según [base-de-datos.md §6](./base-de-datos.md#6-plan-de-migración-de-v3-a-v4), con sus modelos de Eloquent y las reglas de la §7. | Todos |
| 2.3 | Reescribir `CatalogosSeeder` con los valores oficiales ([base-de-datos.md §5](./base-de-datos.md#5-valores-oficiales-de-los-catálogos-para-los-seeders)) y ajustar `EquiposDemoSeeder` y las factories. | Todos |
| 2.4 | `HistorialService::registrar()` con el tipo de evento `valoracion`. | Juan José |
| 2.5 | Componentes compartidos: `x-ui.dependencia-select` (árbol, RF-60) y `x-ui.ubicacion-select` (sede → piso → dependencia, RF-19). | Todos los formularios |
| 2.6 | Ruta y menú vacíos de `/levantamientos`. | Anuar |
| 2.7 | Dejar Pest en verde sobre el modelo nuevo. Lo que no se pueda arreglar sin tocar un bloque se marca como `todo()` con el nombre del dueño. | Todos |

**Hecho cuando:** `migrate:fresh --seed` corre con los catálogos oficiales y Pest pasa.

### Paso 3 — Adaptar lo que ya existe (todos, días 1–2)

Cada uno pone su bloque de la Fase 1 a funcionar sobre el modelo nuevo **antes** de construir algo nuevo: personas y ubicación (Anuar), ficha del equipo (Juan José), filtros de reportes (Alex), catálogos (Manuel), importador (Juan Camilo). El detalle está en el documento de cada persona.

---

## 2. Reparto

| Persona | Bloque | RF a cargo | Detalle |
|---|---|---|---|
| **Jhon** | Líder: base de datos, piezas compartidas, revisión de PR | Pasos 1 y 2; RF-12 | Esta página |
| **Juan José** | Equipos, hoja de vida y valoración | RF-01, 02, 04, 07, 09, 33, 52, 56, 57, 58 | [juan-jose.md](./juan-jose.md) |
| **Anuar** | Movimientos y Levantamientos (nuevo) | RF-18, 19, 20, 22, 23, 26, 59 | [anuar.md](./anuar.md) |
| **Juan Camilo** | Carga de datos | RF-38, 39, 40, 41, 55; cargas de valoraciones y de adquisición | [juan-camilo.md](./juan-camilo.md) |
| **Alex** | Reportes | RF-34, 35, 36, 37, 53, 54 y los reportes nuevos | [alex.md](./alex.md) |
| **Manuel** | Administración y QR | RF-44, 45, 47, 49, 51, 60 | [manuel.md](./manuel.md) |

---

## 3. Calendario

| Días | Meta | Hito |
|---|---|---|
| Antes | Pasos 1 y 2 de la sección 1. | PR #9 y #10 fusionados; base nueva con catálogos oficiales y Pest en verde. |
| 1–2 | Paso 3: adaptar lo existente. | Lo de la Fase 1 funciona otra vez sobre el modelo nuevo. |
| 3–5 | Valoración, catálogos editables, cargas de personas y equipos, matriz FOR.01. | Demo: equipo → valoración → matriz FOR.01. |
| 6–8 | Levantamiento con acta, paz y salvo, anular baja, QR por lotes, tablero, carga de datos reales. | Datos reales cargados y un levantamiento aceptado. |
| 9 | No se agrega nada más; pruebas cruzadas. | Lista de errores priorizada. |
| 10 | Correcciones, manuales, demo al profesor. | `v2.0` en `main`. |

**Pruebas cruzadas del día 9:** Juan José → Levantamientos (Anuar) · Anuar → Administración (Manuel) · Juan Camilo → Equipos (Juan José) · Alex → Carga (Juan Camilo) · Manuel → Reportes (Alex).

---

## 4. Dependencias y riesgos

```
Jhon (PR de Fase 1 → base de datos → selectores)
 ├─► Juan José: ficha y valoración ──► Alex: matriz FOR.01 y riesgos
 │                                 └─► Anuar: levantamiento
 ├─► Manuel: catálogos y equivalencias ──► Juan Camilo: cargas
 └─► Juan Camilo: personas cargadas ──► Anuar: paz y salvo
```

- **Riesgo principal:** la valoración de Juan José la necesitan Alex y Anuar. Si el día 4 no se puede valorar un equipo, Alex le ayuda, y Anuar empieza el levantamiento por el acta.
- **Riesgo secundario:** sin los catálogos editables de Manuel, Juan Camilo trabaja con las equivalencias que vienen en los datos de prueba.

---

## 5. Reglas

Las de [normas-de-trabajo.md](./normas-de-trabajo.md), más dos para esta fase:

- **Nada de datos personales de más:** de la planta solo se guardan los campos de RF-18, y las plantas reales nunca se usan en pruebas ni se suben al repositorio.
- **Nombres oficiales:** si falta una dependencia, sede o cargo, se le pide a Jhon; no se inventa.

---

## 6. Después de la Fase 2

- **Fase 3:** puestos de trabajo (RF-05), fotos (RF-06), tipos de evento configurables (RF-13), configuración por fecha (RF-16), destino de lo retirado (RF-17), listado de bajas (RF-27), vista de pendientes de firma (RF-31), cargas posteriores (RF-42).
- **Fase 4:** firma en pantalla (RF-32).
- **Fuera del alcance:** mantenimiento preventivo y correctivo (RN-08).
