# Juan José — Equipos, Movimientos, Levantamientos, Importación y QR

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Fase 3 · Ver el [Plan de Fase 3](./plan-fase3.md)

## Rol

Desde la Fase 3 llevas todo lo que toca el día a día del técnico: tu bloque de **Equipos y QR**, más los que en la Fase 2 eran de Anuar (**Movimientos y Levantamientos**) y de Juan Camilo (**Importación**). Te tocan seis requerimientos: RF-05, RF-06, RF-16, RF-17, RF-31 y RF-42. Cuatro de ellos no necesitan migración y puedes arrancarlos desde el día 1; RF-05 y RF-06 esperan una migración de Jhon.

## Lo que ya existe

| RF | Ya está | Dónde |
|---|---|---|
| RF-05 | Tabla `puestos_trabajo` (nombre o código, piso y dependencia), `asignaciones.puesto_trabajo_id`, 25 puestos de ejemplo en el seeder. Sin pantalla. | `app/Models/PuestoTrabajo.php`, `CatalogosSeeder::puestosTrabajo()` |
| RF-06 | Nada para el equipo: hoy las fotos solo existen como evidencia de un diagnóstico o una baja (`documentos.tipo = foto`, colgadas de un evento). | `Movimientos/Soporte/GestorFirmas` |
| RF-16 | `componentes.fecha_instalacion` / `fecha_retiro` y `eventos.valores` (antes/después). **Ojo:** `ComponenteForm::guardar()` pone `now()` en esas fechas aunque el evento tenga otra fecha. | `app/Livewire/Movimientos/ComponenteForm.php` |
| RF-17 | `cambios_componente.destino_retirado` (bodega, otro equipo, descarte) se elige en el formulario; `cambios_componente.equipo_destino_id` existe pero **no se llena** y el componente no llega al otro equipo. | `ComponenteForm`, `CambioComponente` |
| RF-31 | Pantalla `/movimientos/pendientes` con filtro por tipo y «más de 30 días», panel para subir los firmados, campanita y reporte «Pendientes de firma». | `Movimientos/PendientesListado`, `Reportes/Definiciones/PendientesFirma` |
| RF-42 | La carga no duplica: un serial ya registrado da **error de fila**. Pero no deja actualizar el equipo que ya existe. | `Importacion/Soporte/CargaEquipos::analizarFila()` |

## Lo que te deja Jhon

- **F3-1 (día 1):** `puestos_trabajo.activo`, puesto único por piso y la decisión sobre `equipos.puesto_trabajo_id` (la fuente de verdad es la asignación abierta).
- **F3-2 (día 2):** tabla `fotos_equipo` y modelo `FotoEquipo` (`$equipo->fotos`, `fotoPrincipal`).
- **Día 7:** el componente compartido para registrar un evento de un tipo configurable (RF-13), que montas en la hoja de vida.

Detalle del esquema en [base-de-datos.md §10](./base-de-datos.md#10-fase-3--cambios-de-esquema-los-crea-el-líder).

## Tareas por día

| Día | RF | Tareas | Toca | Entregable |
|---|---|---|---|---|
| 1 | **RF-17** | En el cambio de componente, si el destino es «otro equipo», buscar el equipo destino (serial, código o número de activo), guardar `equipo_destino_id` y crear en ese equipo el componente instalado, con su propio evento `cambio_componente` (acción «agregar», motivo con la referencia al equipo de origen). No se puede enviar al mismo equipo ni a uno dado de baja (RN-10). Mostrar el destino en la hoja de vida de los dos equipos. | `app/Livewire/Movimientos/ComponenteForm.php`, `resources/views/livewire/movimientos/componente-form.blade.php`, `tests/Feature/Movimientos` | El componente se mueve con historial en los dos equipos. |
| 2 | **RF-16** | En la hoja de vida, un selector de fecha «Ver configuración al…» que muestra los componentes instalados ese día (`fecha_instalacion` nula o anterior, y `fecha_retiro` nula o posterior) y los datos de la configuración de cómputo reconstruidos con `eventos.valores`. Corregir `ComponenteForm` para que las fechas del componente sean las del evento y no `now()`. Los componentes de la carga inicial sin fecha cuentan «desde el alta». | `app/Livewire/Equipos/HojaDeVida.php`, su vista, `ComponenteForm`, `tests/Feature/Equipos` | Configuración en cualquier fecha. |
| 3 | **RF-31** | Completar `/movimientos/pendientes`: días pendientes por evento (resaltado si pasa de 30), filtro por dependencia con su rama (`Dependencia::idsDeRama`), por responsable y por sede, qué documento falta, y exportar a Excel y PDF. El total debe seguir coincidiendo con la campanita. | `app/Livewire/Movimientos/PendientesListado.php`, su vista, `tests/Feature/Movimientos` | Pendientes de firma completos. |
| 4–5 | **RF-05** | (Necesita F3-1.) Pantalla de puestos en Movimientos: crear un puesto en un piso y dependencia (`<x-ui.ubicacion-select>`), ver sus equipos, desactivarlo. Al trasladar un equipo, elegir el puesto (queda en `asignaciones.puesto_trabajo_id`). **Trasladar el puesto completo:** elegir el destino una vez y registrar un traslado por cada equipo del puesto con `RegistroMovimientos` (cada uno con su evento y sus formatos), más el formato de entrega consolidado. | `app/Livewire/Movimientos` (puestos y traslado), vistas, `tests/Feature/Movimientos` | Trasladar un puesto de 3 equipos en un paso. |
| 6 | **RF-06** | (Necesita F3-2.) En la hoja de vida: subir fotos (solo JPG, PNG o WebP, tamaño máximo acordado con Jhon), descripción, fecha de la toma, marcar la principal, verlas en galería y retirarlas. Subir y retirar deja un evento `actualizacion_datos`. La principal sale en la ficha y en el PDF de la hoja de vida. | `app/Livewire/Equipos/HojaDeVida.php` (o un componente `Equipos/FotosEquipo`), vistas, PDF de la hoja de vida, `tests/Feature/Equipos` | Equipo con fotos. |
| 7–8 | **RF-42** | **Carga posterior** en Importación: una fila cuyo serial (o, si no trae serial, su código de activo) ya existe **no da error ni se duplica**: se compara con el equipo y se muestra «se actualiza» con los campos que cambian. Al confirmar, se actualizan solo esos campos con un evento `actualizacion_datos` con `valores` antes/después. Si cambian la ubicación o el responsable, **no se aplican**: se listan como «requiere traslado». Un equipo con serial provisional (`PENDIENTE-…`) se completa con el serial real cuando llega por código de activo. Simulación igual que hoy. | `app/Livewire/Importacion/Soporte/CargaEquipos.php`, `Importacion/Index`, su vista, `tests/Feature/Importacion` | Recargar el mismo archivo no duplica nada. |
| 8 | RF-13 | Montar en la hoja de vida el botón «Registrar otro evento» con el componente que deja Jhon el día 7. | `resources/views/livewire/equipos/hoja-de-vida.blade.php` | Eventos configurables desde la hoja de vida. |
| 9–10 | — | Prueba cruzada de RF-13 (Jhon); correcciones; manual de usuario de lo nuevo. | — | Pest en verde. |

## Pruebas mínimas

- **RF-17:** un componente enviado a otro equipo queda instalado en ese equipo, con `equipo_destino_id` guardado y un evento en cada hoja de vida; no se puede enviar al mismo equipo ni a uno dado de baja.
- **RF-16:** con un disco cambiado el 10 de marzo, la configuración al 1 de marzo muestra el disco viejo y la del 20 de marzo el nuevo; las fechas del componente son las del evento.
- **RF-31:** el total del listado coincide con la campanita; filtrar por una secretaría incluye sus direcciones; la exportación trae los mismos eventos.
- **RF-05:** trasladar un puesto de tres equipos crea tres traslados con su evento cada uno y deja los tres en el nuevo piso; un puesto no se repite en el mismo piso.
- **RF-06:** subir una foto deja evento con su autor; un archivo que no es imagen o es demasiado grande se rechaza; solo hay una foto principal; la foto no queda en `public/`.
- **RF-42:** cargar dos veces el mismo archivo no crea equipos nuevos; un cambio de marca o de datos de adquisición se aplica con su antes y después en el historial; un cambio de ubicación aparece como «requiere traslado» y no se aplica.
- Siempre con Excel sintéticos: **nunca** el inventario real ni las plantas en las pruebas.

## Normas

Ramas `feature/equipos-<tarea>`, `feature/qr-<tarea>`, `feature/movimientos-<tarea>`, `feature/levantamientos-<tarea>` y `feature/importacion-<tarea>`, siempre desde `develop`, un PR por requerimiento y nada se fusiona sin Pest en verde. Escribes en `app/Livewire/{Equipos,Qr,Movimientos,Levantamientos,Importacion}`, sus vistas y sus carpetas de `tests/Feature`. **No creas migraciones:** lo que falte en el modelo se le pide a Jhon. Todo cambio sobre un equipo pasa por `HistorialService::registrar()` y los eventos no se editan. No se inventan nombres oficiales y no se guardan datos personales de más (RNF-19). Detalle en [normas-de-trabajo.md](./normas-de-trabajo.md).

```bash
git checkout develop && git pull origin develop
git checkout -b feature/movimientos-destino-componente
```
