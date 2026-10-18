# Manuel — Administración, QR y Levantamientos

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Administración, QR y Levantamientos (nuevo) · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Vuelves con dos cosas: la **administración de todos los catálogos oficiales nuevos** (sin ellos nadie puede corregir un nombre o agregar una sede) y el bloque nuevo de **Levantamientos**: el inventario por proceso con acta de aceptación firmada que exige el Manual GT-M4. También cierras las etiquetas QR por lotes, ahora por sede y piso reales.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Administración de catálogos (RF-45): **árbol de dependencias** (crear, mover, desactivar, nunca borrar; RN-20) con su tabla de **equivalencias**; procesos; municipios; **sedes con sus pisos** (incluida la marca de dirección reservada); ubicación de dependencias por piso; cargos; modelos por marca y tipo; proveedores. | Todos los catálogos editables. |
| 3 | Usuarios enlazados a su persona (RF-44). Restablecer contraseña (RF-47, ya es un TODO en `UsuariosPanel::restablecerContrasena()`). | Usuarios v4. |
| 4 | Pantalla del plan de mantenimiento (RF-60): ver y editar el plan de la vigencia y sus programaciones (lo carga Juan Camilo), aprobarlo. | Plan editable y aprobable. |
| 5–7 | **Levantamientos (RF-59):** crear uno por dependencia, proceso y sede; listar sus equipos (con subdependencias) marcando los que no tienen valoración o están pendientes de verificar; valorar desde ahí (con el formulario de Juan José); generar la matriz (con la exportación de Alex) y el **acta de identificación y aceptación** prellenada en PDF; subir el acta firmada y dejarlo «Aceptado». | Levantamiento completo de una dependencia. |
| 8 | Etiquetas QR por lotes (RF-49) filtrando por sede, piso, dependencia o levantamiento; reimpresión con registro (RF-51). | Hoja de etiquetas de un piso. |
| 9–10 | Prueba cruzada del bloque de Reportes (Alex); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-44, RF-45, RF-47, RF-49, RF-51, RF-59, RF-60 (pantalla), RF-65 (administración del árbol). Detalle en el [Análisis v4](./analisis-requerimientos.md), secciones 5.9, 5.10, 5.11 y CU-18, y los valores iniciales de cada catálogo en [base-de-datos.md §5](./base-de-datos.md#5-valores-oficiales-de-los-catálogos-para-los-seeders).

## Pruebas mínimas

- No se puede borrar una dependencia con equipos o personas; solo desactivarla.
- No se puede crear un ciclo en el árbol (una dependencia como hija de su propia hija).
- Un piso no se puede eliminar si tiene asignaciones.
- Un levantamiento no pasa a «Aceptado» sin acta firmada.

## Depende de / conecta con

- **Jhon:** migraciones, ruta `/levantamientos`.
- **Juan José:** formulario de valoración (día 4–5). **Alex:** exportación FOR.01 (días 3–4).
- **Juan Camilo** llena las equivalencias que tú administras.

## Normas

Ramas `feature/admin-<tarea>`, `feature/qr-<tarea>` y `feature/levantamientos-<tarea>` desde `develop`; escribes en `app/Livewire/Admin`, `app/Livewire/Qr`, `app/Livewire/Levantamientos`, sus vistas y sus carpetas de `tests/Feature`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/admin-catalogos-v4
```
