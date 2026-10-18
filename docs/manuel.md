# Manuel — Administración e Inicio

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Administración e Inicio (tablero) · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Vuelves con la **administración de los catálogos oficiales nuevos**: sin ellos nadie puede corregir un nombre, agregar una sede o resolver una equivalencia al cargar un Excel. También haces el **tablero de inicio** (RF-36). Las etiquetas QR por lotes y la reimpresión ya quedaron hechas en la Fase 1 (bloque de Juan José).

## Lo que ya te dejó Jhon

- Tablas y modelos de todos los catálogos: `Proceso`, `Municipio`, `Sede` (con `pisos`), `Piso`, `Dependencia` (árbol), `DependenciaEquivalencia`, `UbicacionDependencia`, `Cargo`, `ModeloEquipo` y `Proveedor`, con sus valores oficiales sembrados.
- Constantes para los formularios: `Sede::TIPOS` y `TENENCIAS`, `Dependencia::TIPOS`, `Cargo::NIVELES` y `Proceso::TIPOS`.
- `Dependencia::arbol()` (orden de árbol con profundidad) y la validación de ciclos (RN-20) en el modelo: si alguien mueve una dependencia dentro de una de sus hijas, `save()` lanza una `ValidationException` con el mensaje listo.
- `users.persona_id` y `$user->persona`.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1 | **Adaptar Listas** (paso 3 del plan): la pantalla actual de listas (`ListasPanel`) maneja sedes, pisos y dependencias planos; pasarla a los catálogos nuevos sin columnas viejas. | Listas sobre el modelo nuevo. |
| 2–3 | **Árbol de dependencias** (RF-45, RF-60): ver en árbol con buscador, crear, renombrar, mover (el modelo bloquea los ciclos), desactivar y nunca borrar (RN-20). **Equivalencias:** variantes por dependencia, agregar, reasignar y ver las que quedaron sin resolver en la última carga. | Árbol y equivalencias editables. |
| 4 | **Sedes con sus pisos** (marca de dirección reservada), ubicación de dependencias por piso, municipios y procesos. | Catálogos de ubicación. |
| 5 | **Cargos**, **modelos** por marca y tipo, **proveedores**, campos nuevos de tipos de equipo (tipo de activo, configuración de cómputo, vida útil) y fin de soporte de sistemas operativos. | Catálogos del activo. |
| 6 | Usuarios enlazados a su persona (RF-44). Restablecer contraseña (RF-47; hoy es un TODO en `UsuariosPanel::restablecerContrasena()`). | Usuarios v4. |
| 7–8 | **Tablero de inicio (RF-36)** en `dashboard`: totales por tipo, estado, sede y dependencia (por secretaría), importancia de los activos, pendientes de verificar y sin valoración, comparados con los conteos estimados del PAMP por sede. Acompañar a Juan Camilo con las equivalencias que queden pendientes al cargar el inventario 2026. | Inicio con indicadores. |
| 9–10 | Prueba cruzada del bloque de Reportes (Alex); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-36, RF-44, RF-45, RF-47, RF-60. Detalle en el [Análisis v4.1](./analisis-requerimientos.md), secciones 5.7, 5.9 y 5.12, y CU-19; valores iniciales en [base-de-datos.md §5](./base-de-datos.md#5-valores-oficiales-de-los-catálogos-para-los-seeders).

## Pruebas mínimas

- No se puede borrar una dependencia con equipos o personas; solo desactivarla.
- Mover una dependencia dentro de una de sus hijas muestra el error del modelo.
- Un piso no se puede eliminar si tiene asignaciones.
- El tablero no muestra la dirección de la Casa Refugio.

## Normas

Ramas `feature/admin-<tarea>` y `feature/inicio-<tarea>` desde `develop`; escribes en `app/Livewire/Admin`, sus vistas, `resources/views/dashboard.blade.php` (o un componente `app/Livewire/Inicio` si lo necesitas) y sus carpetas de `tests/Feature`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/admin-listas-v4
```
