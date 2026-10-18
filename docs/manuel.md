# Manuel — Administración y QR

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Administración y QR · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Vuelves con la **administración de todos los catálogos oficiales nuevos**. Sin ellos nadie puede corregir un nombre, agregar una sede o resolver una equivalencia al cargar un Excel. Además cierras las etiquetas QR por lotes, ahora por sede y piso reales.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | **Árbol de dependencias** (RF-45, RF-60): crear, renombrar, mover y desactivar, nunca borrar (RN-20); impedir ciclos; vista en árbol con buscador. **Equivalencias**: listar las variantes de texto de cada dependencia, agregarlas, reasignarlas y ver cuáles quedaron sin resolver en la última carga. | Árbol y equivalencias editables. |
| 3 | **Sedes con sus pisos** (con la marca de dirección reservada), **ubicación de dependencias por piso**, municipios y procesos. | Catálogos de ubicación editables. |
| 4 | **Cargos** (código, denominación, nivel), **modelos** por marca y tipo, **proveedores**, y los campos nuevos de tipos de equipo (tipo de activo, configuración de cómputo, vida útil) y de sistemas operativos (fin de soporte). | Catálogos del activo editables. |
| 5 | Usuarios enlazados a su persona (RF-44). Restablecer contraseña (RF-47, ya es un TODO en `UsuariosPanel::restablecerContrasena()`). | Usuarios v4. |
| 6–7 | Etiquetas QR por lotes (RF-49): filtrar por sede, piso, dependencia (con subdependencias) o levantamiento, y armar la hoja de etiquetas; reimpresión con registro (RF-51). | Hoja de etiquetas de un piso. |
| 8 | Acompañar a Juan Camilo con la carga real del inventario 2026: resolver en pantalla las equivalencias que queden pendientes. | Cero dependencias sin resolver. |
| 9–10 | Prueba cruzada del bloque de Reportes (Alex); correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-44, RF-45, RF-47, RF-49, RF-51, RF-60. Detalle en el [Análisis v4.1](./analisis-requerimientos.md), secciones 5.9, 5.10 y 5.12, CU-19, y los valores iniciales de cada catálogo en [base-de-datos.md §5](./base-de-datos.md#5-valores-oficiales-de-los-catálogos-para-los-seeders).

## Pruebas mínimas

- No se puede borrar una dependencia con equipos o personas; solo desactivarla.
- No se puede crear un ciclo en el árbol (una dependencia como hija de su propia hija).
- Un piso no se puede eliminar si tiene asignaciones.
- La dirección de una sede reservada no aparece en la etiqueta QR ni en el listado de sedes.

## Depende de / conecta con

- **Jhon:** migraciones de los catálogos y seeders oficiales.
- **Juan Camilo** llena las equivalencias que tú administras.
- **Anuar** usa el filtro por levantamiento de tus etiquetas QR.

## Normas

Ramas `feature/admin-<tarea>` y `feature/qr-<tarea>` desde `develop`; escribes en `app/Livewire/Admin`, `app/Livewire/Qr`, sus vistas y sus carpetas de `tests/Feature`. Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/admin-catalogos-v4
```
