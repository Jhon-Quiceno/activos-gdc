# Plan de Fase 2 — Reestructuración con fuentes oficiales

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Activos Tecnológicos — Fase 2
Basado en el [Análisis de requerimientos v4.1](./analisis-requerimientos.md) y el [modelo de datos v4](./base-de-datos.md)

Jhon (líder) · Juan José y Anuar (núcleo) · Juan Camilo, Alex y Manuel (apoyo) · 10 días hábiles
Versión 2.3 · 17 de octubre de 2026

---

## 0. Qué cambia

El análisis ya no sale del Excel del inventario 2026 sino de la documentación oficial: Manual GT-M4, formato FOR.01, Decretos 607, 608 y 609 de 2023 y plantas de personal. Del Programa Anual de Mantenimiento solo se toman sedes, pisos y ubicaciones. El Excel queda solo para cargar datos. **El mantenimiento sigue fuera del alcance.**

Frente al modelo de la Fase 1 ([comparación completa](./base-de-datos.md#2-comparación-modelo-actual-v3-frente-al-modelo-nuevo-v4)) se modifican 17 de las 22 tablas y se agregan 9; ninguna se elimina. Llegan dos módulos nuevos: **valoración del activo** (GT-M4) y **levantamiento con acta de aceptación**. Lo construido en la Fase 1 no se tira: se adapta.

---

## 1. Lo que se debe cumplir antes de empezar algo nuevo

### Paso 1 — Cerrar la Fase 1 ✅ hecho

PR #9 y #10 fusionados en `develop`, `develop` pasado a `main` (PR #17) y ramas viejas borradas. Etiqueta `v1.0` en `main`.

### Paso 2 — Cambiar la base de datos ✅ hecho (primer tiempo)

Rama `feature/nucleo-modelo-v4`. Todo lo que el resto del equipo necesita para arrancar:

| # | Qué quedó | Dónde |
|---|---|---|
| 2.1 | 15 migraciones que **agregan** las 9 tablas y todas las columnas nuevas, sin borrar nada (migración en dos tiempos). | `database/migrations/2026_10_18_1000*` |
| 2.2 | Modelos nuevos: `Proceso`, `Municipio`, `Cargo`, `Proveedor`, `ModeloEquipo`, `DependenciaEquivalencia`, `UbicacionDependencia`, `ValoracionActivo`, `LevantamientoActivos`. Los existentes con sus campos y relaciones nuevos. | `app/Models` |
| 2.3 | Seeders con los catálogos oficiales: 17 procesos, 30 municipios, 11 sedes con 24 pisos, 78 dependencias en árbol con 26 equivalencias, 69 ubicaciones por piso, 23 cargos y 24 tipos de equipo. Datos demo coherentes, con 100 equipos (unos 50 valorados). | `database/seeders` |
| 2.4 | `ValoracionActivo::registrar()` crea la valoración GT-M4 con su evento `valoracion` y calcula total, importancia y riesgo (RN-22 a RN-25). `HistorialService` documenta el tipo nuevo. | `app/Models/ValoracionActivo.php` |
| 2.5 | Componentes compartidos `x-ui.dependencia-select` (árbol con buscador) y `x-ui.ubicacion-select` (sede → piso → dependencia). | `resources/views/components/ui` |
| 2.6 | Ruta `/levantamientos`, menú y pantalla de arranque. | `app/Livewire/Levantamientos` |
| 2.7 | Pest en verde: 240 pruebas (212 de antes + 28 del núcleo). | `tests/Feature/ModeloV4Test.php` |

**Pendiente del profesor (no bloquea):** las preguntas abiertas 1 a 3 del análisis. Lo sembrado con supuestos está marcado «por confirmar» en [base-de-datos.md §5](./base-de-datos.md#5-valores-oficiales-de-los-catálogos-para-los-seeders).

### Paso 3 — Adaptar lo que ya existe (todos, días 1 y 2)

Cada uno pasa su bloque a los campos nuevos **antes** de construir algo nuevo. Las columnas viejas siguen funcionando, pero se borran al cerrar el día 2:

| Deja de usar | Usa en su lugar | Ayudas que ya existen |
|---|---|---|
| `persona->nombre` | `nombres`, `apellidos` | `$persona->nombreCompleto()` |
| `persona->cedula` | `numero_documento`, `tipo_documento` | — |
| `persona->cargo` (texto) | `cargo_id`, `grado`, `encargo` | `$persona->cargoOficial`, `$persona->cargoTexto()` |
| `equipo->modelo` (texto) | `modelo_id` | `$equipo->modeloEquipo` |
| `equipo->propietario_tercero` | `proveedor_tercero_id` | `$equipo->proveedorTercero` |
| `asignacion->sede_id`, `asignacion->sede` | `piso_id` | `$asignacion->piso->sede` |
| Selects sueltos de sede, piso y dependencia | Selectores compartidos | `<x-ui.ubicacion-select>`, `<x-ui.dependencia-select>` |
| Filtrar por una sola dependencia | Filtrar por la rama completa | `Dependencia::idsDeRama($id)` |
| Comparar textos de dependencia «a ojo» | Tabla de equivalencias | `DependenciaEquivalencia::resolver($texto)` |
| Mostrar `sede->direccion` | Dirección visible | `$sede->direccionVisible()` (Casa Refugio, RN-28) |

Cómo comprobarlo antes del PR de cada uno: [base-de-datos.md §6.2](./base-de-datos.md#62-segundo-tiempo--al-cerrar-el-día-2-jhon).

### Paso 4 — Cerrar la migración (Jhon, al final del día 2)

Migración de cierre: borra las columnas viejas, vuelve obligatorios `pisos.sede_id`, `sedes.municipio_id` y `personas.numero_documento`, y convierte `estado_funcionamiento` en lista. Detalle en [base-de-datos.md §6.2](./base-de-datos.md#62-segundo-tiempo--al-cerrar-el-día-2-jhon). Solo se fusiona con Pest en verde.

---

## 2. Reparto

Los PR #9 y #10 ya entregaron RF-22 (entrega consolidada), RF-26 (anular baja), RF-33 (PDF de la hoja de vida) y RF-49/RF-51 (QR por lotes y reimpresión), y el bloque QR pasó a Juan José. El reparto quedó así:

| Persona | Bloque | RF a cargo | Detalle |
|---|---|---|---|
| **Jhon** | Líder: base de datos, piezas compartidas, revisión de PR | Pasos 1, 2 y 4; RF-12 | Esta página |
| **Juan José** | Equipos, hoja de vida, valoración y QR | RF-01, 02, 04, 07, 09, 33, 52, 56, 57, 58; adaptar QR | [juan-jose.md](./juan-jose.md) |
| **Anuar** | Movimientos y Levantamientos | RF-18, 19, 20, 22, 23, 27, 59 | [anuar.md](./anuar.md) |
| **Juan Camilo** | Carga de datos | RF-38, 39, 40, 41, 55; cargas de valoraciones y de adquisición | [juan-camilo.md](./juan-camilo.md) |
| **Alex** | Reportes | RF-34, 35, 37, 53, 54 y los reportes nuevos | [alex.md](./alex.md) |
| **Manuel** | Administración e Inicio | RF-36, 44, 45, 47, 60 | [manuel.md](./manuel.md) |

---

## 3. Calendario

| Días | Meta | Hito |
|---|---|---|
| Antes | Pasos 1 y 2. | ✅ Hecho. |
| 1–2 | Paso 3: cada bloque usa los campos nuevos. Día 2 en la tarde: paso 4. | Columnas viejas borradas y Pest en verde. |
| 3–5 | Valoración, catálogos editables, cargas de personas y equipos, matriz FOR.01. | Demo: equipo → valoración → matriz FOR.01. |
| 6–8 | Levantamiento con acta, paz y salvo, tablero, carga de datos reales. | Datos reales cargados y un levantamiento aceptado. |
| 9 | No se agrega nada más; pruebas cruzadas. | Lista de errores priorizada. |
| 10 | Correcciones, manuales, demo al profesor. | `v2.0` en `main`. |

**Pruebas cruzadas del día 9:** Juan José → Levantamientos (Anuar) · Anuar → Administración (Manuel) · Juan Camilo → Equipos (Juan José) · Alex → Carga (Juan Camilo) · Manuel → Reportes (Alex).

---

## 4. Dependencias y riesgos

```
Jhon (base de datos ✅ → migración de cierre, día 2)
 ├─► Juan José: ficha y valoración ──► Alex: matriz FOR.01 y riesgos
 │                                 └─► Anuar: levantamiento
 ├─► Manuel: catálogos y equivalencias ──► Juan Camilo: cargas
 └─► Juan Camilo: personas cargadas ──► Anuar: paz y salvo
```

- **Riesgo principal:** la valoración de Juan José la necesitan Alex y Anuar. El modelo ya calcula todo (`ValoracionActivo::registrar()`), así que pueden trabajar con valoraciones del seeder demo mientras Juan José hace la pantalla.
- **Riesgo del día 2:** si un bloque no termina de dejar las columnas viejas, la migración de cierre espera; los demás siguen trabajando sin problema.

---

## 5. Reglas

Las de [normas-de-trabajo.md](./normas-de-trabajo.md), más tres para esta fase:

- **Código nuevo, campos nuevos:** nada nuevo usa `nombre`, `cedula`, `cargo`, `modelo`, `propietario_tercero` ni `sede_id` de la asignación.
- **Nada de datos personales de más:** de la planta solo se guardan los campos de RF-18, y las plantas reales nunca se usan en pruebas ni se suben al repositorio.
- **Nombres oficiales:** si falta una dependencia, sede o cargo, se le pide a Jhon; no se inventa.

---

## 6. Después de la Fase 2

- **Fase 3:** puestos de trabajo (RF-05), fotos (RF-06), tipos de evento configurables (RF-13), configuración por fecha (RF-16), destino de lo retirado (RF-17), vista de pendientes de firma (RF-31), cargas posteriores (RF-42).
- **Fase 4:** firma en pantalla (RF-32).
- **Fuera del alcance:** mantenimiento preventivo y correctivo (RN-08).
