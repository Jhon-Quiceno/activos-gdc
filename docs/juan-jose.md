# Juan José — Núcleo 1: Equipos, hoja de vida, valoración y QR

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 2 · Bloques: Equipos y QR · Ver el [Plan de Fase 2](./plan-fase2.md)

## Rol

Llevas el bloque central: la ficha del equipo y su hoja de vida. En esta fase la ficha crece con los datos oficiales del FOR.01 y de adquisición, y se suma la **valoración del activo del Manual GT-M4**, que después usan Alex (matriz FOR.01, riesgos) y Anuar (levantamiento). Desde el 9 de octubre también eres dueño del bloque QR; RF-49 y RF-51 ya están hechos (PR #10).

## Lo que ya te dejó Jhon

- `equipos` con `numero_activo` (se asigna solo y no cambia, RN-27), `modelo_id`, `descripcion`, `proceso_id`, `proveedor_tercero_id`, `forma_ingreso` y los datos de adquisición y garantía.
- `ModeloEquipo` (catálogo de modelos por marca y tipo) y `Proveedor`.
- `Equipo::garantiaPorVencer()`, `Equipo::vidaUtilSuperada()`, `$equipo->valoracionVigente`, `$equipo->valoraciones`.
- **`ValoracionActivo::registrar($equipo, $usuario, $datos)`**: crea la valoración y su evento `valoracion`, calcula total, importancia y riesgo, deja no vigente la anterior y aplica RN-22 a RN-25. Tu pantalla solo arma `$datos` y muestra los errores de validación.
- Textos de las escalas para el formulario: `ValoracionActivo::ESCALA_CONFIDENCIALIDAD`, `ESCALA_INTEGRIDAD`, `ESCALA_DISPONIBILIDAD`, `CLASIFICACIONES_DATOS`, `CONTROLES_MONITOREO`.
- Selectores `<x-ui.dependencia-select>` y `<x-ui.ubicacion-select sede="sedeId" piso="pisoId" dependencia="dependenciaId" />`: este último, al elegir la sede, solo muestra sus pisos y sus dependencias (con «Ver todas las dependencias» como salida). Úsalo en el registro y la edición del equipo en lugar de los tres selects actuales. También `Dependencia::idsDeRama($id)`.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | **Adaptar al modelo nuevo** (paso 3 del plan): registro y edición con modelo del catálogo (marca → modelo) en vez de texto, propietario tercero desde `proveedores`, número de activo visible, descripción y proceso (por defecto el de la dependencia). Filtros de equipos y de QR (`FiltrosDeEquipos`) por rama de dependencia con `x-ui.dependencia-select` y por sede → piso. Dejar de usar `modelo`, `propietario_tercero` y `asignacion->sede`. | Tu bloque sin columnas viejas. |
| 3 | Datos de adquisición (RF-52): forma de ingreso, fecha, valor COP, proveedor, contrato, factura, garantía y vida útil, con avisos de garantía por vencer y vida útil superada. Configuración de cómputo con ofimática, dominio, IP y MAC. | Ficha completa. |
| 4–5 | **Pantalla de valoración (RF-56, RF-57):** formulario en la hoja de vida con las escalas (el texto de cada nivel, no solo el número), total, importancia y marca de riesgo en vivo. Guarda con `ValoracionActivo::registrar()`. | Valorar un equipo. |
| 6 | Reclasificación con historial (RF-58): todas las valoraciones en la hoja de vida, la vigente destacada. | Historial de valoraciones. |
| 7 | Hoja de vida (RF-09) y su PDF (RF-33) con valoración vigente, adquisición y cargo oficial del responsable. | Hoja de vida v4. |
| 8 | Dejar el formulario de valoración reusable (componente Livewire o parcial) para el levantamiento de Anuar (RF-59). | Valoración usable desde el levantamiento. |
| 9–10 | Prueba cruzada del bloque de Levantamientos (Anuar); pruebas propias y correcciones. | Pest en verde. |

## Requerimientos a cargo

RF-01, RF-02, RF-04, RF-07, RF-09, RF-33, RF-52, RF-56, RF-57, RF-58 y la adaptación del QR. Detalle en el [Análisis v4.1](./analisis-requerimientos.md), secciones 5.1, 5.2 y 5.11, y en [base-de-datos.md §4.4 y §4.5](./base-de-datos.md#44-equipos-columnas-nuevas).

## Pruebas mínimas

- La pantalla muestra el error de RN-24 (datos personales) y de RN-25 (soporte) que lanza el modelo.
- El número de activo aparece en la ficha y no se puede editar.
- Filtrar por «Secretaría de Hacienda» trae los equipos de sus direcciones.

## Normas

Ramas `feature/equipos-<tarea>` y `feature/qr-<tarea>` desde `develop`; escribes en `app/Livewire/Equipos`, `app/Livewire/Qr`, sus vistas y sus carpetas de `tests/Feature`. Lo que falte en el modelo se le pide a Jhon. Todo cambio pasa por `HistorialService::registrar()` (la valoración, por `ValoracionActivo::registrar()`). Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/equipos-modelo-v4
```
