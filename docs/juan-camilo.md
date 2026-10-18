# Juan Camilo — Carga de datos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloque: Apoyo · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

El Excel del inventario 2026 **dejó de ser la base del sistema**: ahora es solo una fuente de datos más. Tu bloque pasa de «importar ese Excel» a **cargar datos en el modelo oficial**: equipos con una plantilla propia, personas desde la planta de Talento Humano, valoraciones desde la matriz FOR.01 y datos de adquisición desde Almacén.

## Lo que ya te dejó Jhon

- **`DependenciaEquivalencia::resolver($texto)`**: devuelve la dependencia oficial por nombre, nombre corto o equivalencia, sin importar mayúsculas, tildes ni espacios, y `null` si no hay coincidencia exacta. Reemplaza el emparejamiento por similitud (Levenshtein): sin coincidencia, la fila queda con error.
- 26 equivalencias sembradas, entre ellas los nombres anteriores al Decreto 609 («DIRECION DE SISTEMAS», «Nómina Central»…).
- `importaciones.tipo` (`equipos`, `personas`, `valoraciones`) y `eventos.es_historico` para las altas de carga inicial.
- `Cargo` (código + denominación) para resolver los cargos de la planta, y `Persona` con los campos permitidos (RNF-19).
- `ValoracionActivo::registrar()` para crear valoraciones con su evento y sus reglas.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | **Adaptar el importador** (paso 3 del plan): resolver dependencias con `DependenciaEquivalencia::resolver()` (sin coincidencia = error de fila), sede → piso de esa sede (un piso que no existe en la sede = error), y escribir personas y equipos con los campos nuevos (`nombres`, `numero_documento`, `modelo_id`…), no con los viejos. Altas con `es_historico = true`. | Importador sin columnas viejas. |
| 3 | **Plantilla oficial de carga de equipos** (`.xlsx` descargable): columnas del modelo v4 con listas desplegables de los catálogos. | Plantilla publicada. |
| 4–5 | **Carga de personas desde la planta (RF-55):** de las plantas Central y Educación solo se toman documento, nombres, cargo (código y grado), dependencia real, jefe inmediato, planta, vinculación, provisión y correo institucional. El resto de columnas se descarta en memoria y nunca se guarda (RNF-19). | Personas cargadas sin datos sensibles. |
| 6 | **Carga de valoraciones** desde una matriz FOR.01: cruzar cada fila con su equipo, pasar las respuestas de C, I y D a 1–4 y crear con `ValoracionActivo::registrar()`. | Valoraciones de una dependencia. |
| 7 | **Inventario 2026 por la plantilla:** convertir el Excel de Forms a la plantilla (dividir filas en equipos y normalizar códigos, como ya hacías) y cargarlo por el flujo normal. Llenar las equivalencias de sus 175 variantes de dependencia junto a Manuel. | Inventario 2026 cargado. |
| 8 | **Datos de adquisición** desde Almacén (fecha, valor, proveedor, contrato, factura), cruzando por código de activo; listar los que no crucen. Revisar los criterios de la §12 del análisis. | Reporte de la carga. |
| 9–10 | Prueba cruzada del bloque de Equipos y valoración (Juan José); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-38, RF-39, RF-40, RF-41, RF-55 y las cargas de valoraciones y de adquisición (§12 del [Análisis v4.1](./analisis-requerimientos.md)).

## Pruebas mínimas

- Una carga de planta con columnas de salario, RH o dirección no guarda ninguna de ellas (comprobado en la base).
- Una dependencia escrita con su nombre anterior al Decreto 609 se resuelve; una desconocida da error de fila.
- Un piso que no existe en la sede indicada da error de fila (RN-21).
- **Nunca** se usan las plantas reales en pruebas ni se suben al repositorio: las pruebas usan Excel sintéticos con las mismas cabeceras.

## Normas

Rama `feature/importacion-<tarea>` desde `develop`; escribes en `app/Livewire/Importacion`, sus vistas y `tests/Feature/Importacion`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/importacion-modelo-v4
```
