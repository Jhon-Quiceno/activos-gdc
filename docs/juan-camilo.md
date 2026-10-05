# Juan Camilo — Carga del inventario

**Sistema de Hoja de Vida de Equipos · Gobernación de Córdoba · Dirección TIC**
Plan de dos semanas · Bloque: Apoyo

## Rol

Juan Camilo lleva un **bloque aislado pero crítico**: sin él, el sistema arranca vacío. Se encarga de importar el inventario real de marzo de 2026 (461 filas, 930 equipos) al sistema, normalizando los datos y marcando lo que necesita verificación en sitio.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Tablas de equivalencias: 175 variantes de dependencia, sedes, pisos, marcas y sistemas operativos (empezar con un script que liste los valores únicos del Excel). | Equivalencias en seeders o tabla editable. |
| 3–4 | Lectura del Excel: dividir cada fila en hasta 6 equipos y crear teclado, mouse y sonido como componentes; normalizar códigos (espacios, guiones, L1→I1, «N/A» como vacío, mover seriales). | Comando que procesa en modo simulación. |
| 5–6 | Excluir personales, marcar terceros, guardar la fila de origen, crear evento de alta histórico y marcar todo «Pendiente de verificar». | Importación en base de pruebas. |
| 7–8 | Pantalla: subir archivo, vista previa con errores y advertencias por fila, confirmar. Verificar los criterios de aceptación (conteos por tipo y sede). | Inventario 2026 cargado. |
| 9–10 | Ayudar a Alex con el reporte de calidad y corregir errores. | Reporte de la importación. |

## Requerimientos funcionales a cargo

RF-38, RF-39, RF-40, RF-41.

Consulta el detalle completo de cada uno en el [Análisis de requerimientos](./analisis-requerimientos.md), sección 5.8. También es importante revisar la sección 3 (diagnóstico del inventario 2026, con los 11 problemas de calidad D-01 a D-11) y la sección 12 (pasos de carga inicial y depuración), porque ahí está el detalle de qué normalizar y por qué.

## Depende de / conecta con

- **Solo del modelo de datos de Jhon** — este bloque no depende de ningún otro bloque de apoyo o núcleo para avanzar, por lo que puede trabajar en paralelo desde el día 1 con datos de prueba (el Excel real).
- Al terminar (día 8), **entrega los datos reales a todos los demás bloques**: a partir de ese momento, Juan José, Anuar, Alex y Manuel pueden probar sus flujos con el inventario real en vez de datos falsos de los seeders.
- Usa el paquete `maatwebsite/excel` para leer el archivo de inventario.

## Recordatorio de las normas de trabajo

- Ramas `feature/importacion-<tarea>` creadas siempre desde `develop`, nunca desde `main`.
- Nunca crear ni tocar migraciones: si falta un campo (por ejemplo en `Importacion` o en la tabla de equivalencias), se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Importacion`, `resources/views/importacion` y `tests/Importacion`.
- El evento de alta histórico que genera la importación pasa por `HistorialService::registrar()`, igual que cualquier otro evento. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec laravel.test ./vendor/bin/pest` y que pase en verde.

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
