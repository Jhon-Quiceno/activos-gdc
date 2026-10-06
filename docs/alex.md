# Alex — Reportes

**Sistema de Hoja de Vida de Equipos · Gobernación de Córdoba · Dirección TIC**
Plan de dos semanas · Bloque: Apoyo

## Rol

Alex construye el **módulo de reportes y consultas** sobre todo el parque tecnológico. Trabaja con los datos falsos de los seeders hasta que llegue la importación real de Juan Camilo (día 8).

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Componente de reporte reutilizable: filtros combinables (sede, piso, dependencia, tipo, marca, estado, propiedad, responsable, vinculación, fechas) y exportación a Excel y PDF. | Base de reportes. |
| 3–5 | Reportes de inventario: general, por dependencia, por sede y piso, por funcionario, por tipo/marca/modelo, por estado, de terceros. | 7 reportes. |
| 6–7 | Reporte de calidad del inventario: sin serial, sin código, sin responsable, código repetido, pendientes de verificar, sin etiqueta QR. | Reporte de calidad. |
| 8 | Reportes de movimientos: dados de baja, traslados y cambios de componentes en un período, pendientes de firma. | 4 reportes. |
| 9–10 | Probar con el inventario real; cédula enmascarada en exportaciones; ayudar en pruebas cruzadas. | Reportes con datos reales. |

## Requerimientos funcionales a cargo

RF-34, RF-35, RF-37 (los demás reportes del catálogo de la sección 11 del análisis se hacen solo si sobra tiempo).

Consulta el detalle completo en el [Análisis de requerimientos](./analisis-requerimientos.md), sección 5.7 (requerimientos) y sección 11 (catálogo completo de reportes y salidas).

## Depende de / conecta con

- **El modelo de datos de Jhon** — base de todos los reportes.
- **Los eventos de Juan José y Anuar** — los reportes de movimientos (traslados, cambios de componentes, bajas, pendientes de firma) leen los eventos que esos dos bloques generan vía `HistorialService`.
- **Los datos de Juan Camilo** — a partir del día 8, los reportes deben probarse con el inventario real importado, no solo con los datos falsos de los seeders.
- Usa el paquete `maatwebsite/excel` (exportación) y `barryvdh/laravel-dompdf` (exportación a PDF).
- La cédula debe mostrarse enmascarada en cualquier exportación masiva (RN-12), por lo que este bloque no debe exponerla completa en listados ni archivos exportados.

## Recordatorio de las normas de trabajo

- Ramas `feature/reportes-<tarea>` creadas siempre desde `develop`, nunca desde `main`.
- Nunca crear ni tocar migraciones: si falta un campo o un índice para optimizar un filtro, se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Reportes`, `resources/views/livewire/reportes` y `tests/Feature/Reportes`.
- Los reportes son de solo lectura, pero si alguna acción del módulo llegara a generar un evento sobre un equipo, debe pasar por `HistorialService::registrar()`. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec -u sail laravel.test ./vendor/bin/pest` y que pase en verde.

### Cómo crear tu rama (copiar y pegar)

```bash
git checkout develop
git pull origin develop
git checkout -b feature/reportes-base   # cambia "base" por tu tarea
# ...trabajar y commitear...
git push -u origin feature/reportes-base
# abrir el Pull Request en GitHub apuntando a develop, nunca a main
```

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
