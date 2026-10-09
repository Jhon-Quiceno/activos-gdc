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

## Estado al cierre de la Fase 1 (revisado por Jhon, 2026-10-08)

| Días | Tarea | Estado |
|---|---|---|
| 1–2 | Tablas de equivalencias | ⚠️ Parcial. Normaliza bien el código de activo (espacios, guiones, L1→I1, "N/A" como vacío). Pero sede/dependencia se resuelven por similitud de texto (Levenshtein) **sin umbral ni tabla de equivalencias editable** (RF-39): hoy, con un solo valor en el catálogo, cualquier texto —sin importar cuán distinto— se asocia a esa única opción, en silencio. Con las 175 variantes reales de dependencia esto puede asociar mal. Quedó documentado como hallazgo propio en el último test de tu PR. |
| 3–4 | Lectura del Excel, división en equipos | ✅ Hecho (`Index.php`, `InventarioExcel.php`), hasta 6 equipos + 3 periféricos por fila. |
| 5–6 | Excluir personales, terceros, fila de origen, alta histórica, pendiente de verificar | ✅ Hecho. |
| 7–8 | Pantalla subir/vista previa/confirmar | ✅ Hecho y fusionado (PR #7), con 18 tests. **Corregí hoy un bug real**: la confirmación solo detectaba códigos de activo repetidos *dentro del mismo archivo*; si una fila traía un código que ya existía en un equipo fuera del archivo (sin justificación), `Equipo::create()` reventaba con una excepción cruda en vez de un error claro de fila. Ya captura la excepción y muestra "Fila X: ...". |
| 9–10 | Ayudar a Alex con el reporte de calidad | ❌ No hecho — el reporte de calidad de Alex sigue siendo un placeholder (ver su documento). |

**Para Juan Camilo, si sigue en Fase 1:** decidir un umbral para el matching de sede/dependencia (RF-39) antes de cargar el inventario real, y coordinar con Alex el reporte de calidad.

## Depende de / conecta con

- **Solo del modelo de datos de Jhon** — este bloque no depende de ningún otro bloque de apoyo o núcleo para avanzar, por lo que puede trabajar en paralelo desde el día 1 con datos de prueba (el Excel real).
- Al terminar (día 8), **entrega los datos reales a todos los demás bloques**: a partir de ese momento, Juan José, Anuar, Alex y Manuel pueden probar sus flujos con el inventario real en vez de datos falsos de los seeders.
- Usa el paquete `maatwebsite/excel` para leer el archivo de inventario.

## Recordatorio de las normas de trabajo

- Ramas `feature/importacion-<tarea>` creadas siempre desde `develop`, nunca desde `main`.
- Nunca crear ni tocar migraciones: si falta un campo (por ejemplo en `Importacion` o en la tabla de equivalencias), se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Importacion`, `resources/views/livewire/importacion` y `tests/Feature/Importacion`.
- El evento de alta histórico que genera la importación pasa por `HistorialService::registrar()`, igual que cualquier otro evento. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec -u sail laravel.test ./vendor/bin/pest` y que pase en verde.

### Cómo crear tu rama (copiar y pegar)

```bash
git checkout develop
git pull origin develop
git checkout -b feature/importacion-equivalencias   # cambia por tu tarea
# ...trabajar y commitear...
git push -u origin feature/importacion-equivalencias
# abrir el Pull Request en GitHub apuntando a develop, nunca a main
```

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
