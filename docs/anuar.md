# Anuar — Núcleo 2: Movimientos y formatos

**Sistema de Hoja de Vida de Equipos · Gobernación de Córdoba · Dirección TIC**
Plan de dos semanas · Bloque: Núcleo 2

## Rol

Anuar lleva los **procesos que generan papel firmado**: traslados, diagnósticos, bajas y los dos formatos oficiales (entrega y baja) que la Dirección TIC necesita para cada movimiento de un equipo.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Plantillas PDF de los formatos de entrega y de baja (carta, legibles en blanco y negro, espacios de firma), a partir del formato actual mejorado. | Dos PDF de muestra. |
| 3–4 | Responsables: crear persona, asignar a un equipo (una sola asignación abierta), dejar sin asignar. Traslado o cambio de responsable: cierra la asignación anterior, abre la nueva, crea el evento y genera los dos PDF. | Traslado completo con PDF. |
| 5–6 | Subida de documentos firmados (PDF, JPG, PNG, máx. 10 MB); estado «Pendiente de firma» hasta que estén los dos; opción de documento único. | Evento que se completa al subir firmas. |
| 7–8 | Diagnóstico y baja (motivo, recomendaciones, evidencia, formato de baja firmado → «Dado de baja»); bloquear eventos nuevos en equipos dados de baja salvo anulación. Formato de entrega consolidado por funcionario. | Baja funcionando. |
| 9–10 | Pruebas de los flujos y corrección de errores. | Pruebas en verde. |

## Requerimientos funcionales a cargo

RF-18, RF-19, RF-20, RF-21, RF-22, RF-24, RF-25, RF-28, RF-29, RF-30.

Consulta el detalle completo de cada uno en el [Análisis de requerimientos](./analisis-requerimientos.md), secciones 5.4 a 5.6.

## Depende de / conecta con

- **`HistorialService` y la hoja de vida de Juan José** — los botones de traslado, diagnóstico y baja viven en la vista de hoja de vida que construye Juan José; Anuar implementa la lógica detrás de esos botones.
- **Alex** lee los eventos que genera este bloque (traslados, bajas, cambios) para construir sus reportes de movimientos.
- Usa el paquete `barryvdh/laravel-dompdf` para generar los formatos PDF de entrega y baja (RNF-13: tamaño carta, legibles en blanco y negro, con espacio de firma).

## Recordatorio de las normas de trabajo

- Ramas `feature/movimientos-<tarea>` creadas siempre desde `develop`, nunca desde `main`.
- Nunca crear ni tocar migraciones: si falta un campo (por ejemplo en `Asignacion` o `Documento`), se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Movimientos`, `resources/views/livewire/movimientos` y `tests/Feature/Movimientos`.
- Todo cambio sobre un equipo (traslado, diagnóstico, baja) pasa por `HistorialService::registrar()`. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec -u sail laravel.test ./vendor/bin/pest` y que pase en verde.

### Cómo crear tu rama (copiar y pegar)

```bash
git checkout develop
git pull origin develop
git checkout -b feature/movimientos-traslado   # cambia "traslado" por tu tarea
# ...trabajar y commitear...
git push -u origin feature/movimientos-traslado
# abrir el Pull Request en GitHub apuntando a develop, nunca a main
```

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
