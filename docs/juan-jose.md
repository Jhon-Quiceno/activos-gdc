# Juan José — Núcleo 1: Equipos y hoja de vida

**Sistema de Hoja de Vida de Equipos · Gobernación de Córdoba · Dirección TIC**
Plan de dos semanas · Bloque: Núcleo 1

## Rol

Juan José lleva el **bloque central** del sistema: todo lo demás se ve a través de la hoja de vida. Sin este bloque funcionando, ningún otro bloque tiene dónde mostrar sus resultados (los botones de acción de Anuar, el QR de Manuel, los reportes de Alex dependen de que un equipo pueda registrarse y consultarse).

## Tareas por día

> **Días 1–2 ya hechos por el líder** como parte del bootstrap, para dejar un ejemplo real de cómo conectar una pantalla al modelo de datos: el listado de equipos con búsqueda (`app/Livewire/Equipos/Index.php` y `resources/views/livewire/equipos/index.blade.php`) ya existe y funciona contra la base real — búsqueda por serial, código de activo, responsable, cédula, dependencia y sede, tolerando espacios y guiones (`I1 24147` = `I1-24147`). Arrancá directo en los días 3–4; revisá ese archivo primero para seguir el mismo patrón (Livewire + `x-ui.table` + `x-ui.badge`) en el resto de tus pantallas.

| Días | Tareas | Entregable |
|---|---|---|
| ~~1–2~~ | ~~Listado de equipos y búsqueda por serial, código de activo, responsable, cédula, dependencia y sede, tolerando espacios y guiones (`I1 24147` = `I1-24147`).~~ | **Ya hecho** — listado con búsqueda. |
| 3–4 | Registro y edición de equipo con formulario que cambia según el tipo; serial obligatorio y único; código de activo con formato `I1-######`; aviso de duplicados; propiedad Gobernación o tercero. | Registrar un equipo. |
| 5–6 | Vista de hoja de vida: ficha, configuración, componentes actuales, responsable, ubicación e historial cronológico. Botones de acción que llaman a los flujos de Anuar. | Hoja de vida completa. |
| 7–8 | Componentes y cambio de componente (agregar, cambiar, quitar) con seriales y motivo; actualiza la configuración y registra el evento. Eventos inmutables y anulación/aclaración. | Cambio de componente funcionando. |
| 9–10 | Pruebas: serial único, evento inmutable, autor automático. Corrección de errores. | Pruebas en verde. |

## Requerimientos funcionales a cargo

RF-01, RF-02, RF-03, RF-04, RF-07, RF-09, RF-10, RF-11, RF-12, RF-14, RF-15.

Consulta el detalle completo de cada uno en el [Análisis de requerimientos](./analisis-requerimientos.md), secciones 5.1 a 5.3.

> **Agregado fuera del plan original: RF-33 · Exportar la hoja de vida a PDF.**
> RF-33 estaba en la **Fase 2** (análisis, sección 13; plan, sección 6 «Qué queda fuera de estas dos semanas»). Se **adelantó a la Fase 1** con autorización de Jhon el **7 de octubre de 2026** y lo implementó Juan José: botón «Exportar hoja de vida (PDF)» en la hoja de vida, que descarga un PDF tamaño carta sin firma con la ficha, el responsable y la ubicación (cédula enmascarada, RN-12), el software, los componentes actuales y el historial completo. Código: `App\Livewire\Equipos\HojaDeVida::exportarPdf()` y `resources/views/livewire/equipos/pdf/hoja-de-vida.blade.php`.

## Depende de / conecta con

- **El modelo de datos y `HistorialService` de Jhon** — ya deben existir antes del día 1; no se crean migraciones propias (ver norma 2 más abajo).
- **Anuar** usa los botones de acción de la hoja de vida (traslado, diagnóstico, baja) que Juan José expone en esta vista.
- **Manuel** inserta el código QR en esta misma vista de hoja de vida.
- Este es el bloque del que dependen casi todos los demás: si el día 4 no se puede registrar un equipo y ver su hoja de vida, es el riesgo principal del plan (ver sección 6 del [Plan de dos semanas](./plan-2-semanas.md)).

## Recordatorio de las normas de trabajo

- Ramas `feature/equipos-<tarea>` creadas siempre desde `develop`, nunca desde `main`.
- Nunca crear ni tocar migraciones: si falta un campo, se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Equipos`, `resources/views/livewire/equipos` y `tests/Feature/Equipos`.
- Todo cambio sobre un equipo pasa por `HistorialService::registrar()`. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec -u sail laravel.test ./vendor/bin/pest` y que pase en verde.

### Cómo crear tu rama (copiar y pegar)

```bash
git checkout develop
git pull origin develop
git checkout -b feature/equipos-busqueda   # cambia "busqueda" por tu tarea
# ...trabajar y commitear...
git push -u origin feature/equipos-busqueda
# abrir el Pull Request en GitHub apuntando a develop, nunca a main
```

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
