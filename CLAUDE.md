# Sistema de Hoja de Vida de Equipos

Gobernación de Córdoba · Dirección TIC. Lleva el ciclo de vida completo de cada equipo
tecnológico (registro, componentes, traslados, diagnóstico, baja) con formatos PDF
firmados y código QR por equipo. Ver `docs/analisis-requerimientos.md` para el detalle
funcional completo (35 RF esenciales, modelo de dominio, reglas de negocio, casos de uso)
y `docs/plan-2-semanas.md` para el plan y reparto de trabajo del equipo.

## Stack

Laravel 13 · Livewire 3 (Breeze, Volt) · MySQL 8.4 · Docker · Tailwind CSS · pnpm · Pest.
Paquetes: `barryvdh/laravel-dompdf`, `maatwebsite/excel`, `simplesoftwareio/simple-qrcode`,
`spatie/laravel-activitylog`, `laravel-lang/common` (locale `es`).

## Entorno — Docker, no Sail nativo

**No uses `./vendor/bin/sail`**: ese script no detecta Git Bash/MinGW en Windows (solo
macOS/Linux/WSL2). Usa siempre `docker compose` directo contra el servicio `laravel.test`.

**El flag `-u sail` es obligatorio** en todo comando que no sea de solo lectura. Sin él,
`docker compose exec` corre como `root` y dejar archivos en `storage/`, `bootstrap/cache/`,
`vendor/` o `public/build/` con ese dueño rompe al servidor web (que corre como `sail`)
la próxima vez que necesite escribir ahí — error 500 `tempnam()`. Si pasa, arreglarlo con:

```bash
docker compose exec laravel.test chown -R sail:sail storage bootstrap/cache public/build vendor
```

En Windows con Git Bash, si un comando de Docker falla por rutas raras, antepón:
`export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL="*"`

## Comandos frecuentes

```bash
docker compose up -d                                              # levantar todo
docker compose exec -u sail laravel.test php artisan migrate:fresh --seed
docker compose exec -u sail laravel.test ./vendor/bin/pest         # pruebas
docker compose exec -u sail laravel.test pnpm run build            # assets (o run dev)
docker compose exec -u sail laravel.test php artisan <comando>     # artisan en general
```

App: `http://localhost` · Mailpit: `http://localhost:8025`.
Admin de prueba: `admin@gobernaciondecordoba.gov.co` / `password`.

## Reglas de trabajo en equipo

Detalle completo en `docs/normas-de-trabajo.md`. Resumen:

- Ramas `feature/<bloque>-<tarea>` **siempre desde `develop`**, nunca desde `main`. Ambas
  están protegidas (PR obligatorio).
- **Solo el líder crea migraciones.** Si falta un campo, se pide y se agrega el mismo día.
- Cada bloque escribe solo en sus carpetas: `app/Livewire/<Bloque>`,
  `resources/views/livewire/<bloque>`, `tests/Feature/<Bloque>`. Lo compartido (componentes
  `x-ui.*`, el layout, `HistorialService`) lo cambia solo el líder.
- **Todo cambio sobre un equipo pasa por `HistorialService::registrar()`.** Nunca se
  inserta un `Evento` a mano — el modelo lo bloquea a propósito (`update()`/`delete()`
  lanzan excepción, ver `app/Models/Evento.php`).
- Trabajar siempre sobre `migrate:fresh --seed`; nadie depende de datos locales.
- Antes de abrir un PR: `./vendor/bin/pest` en verde.
- **Idioma de commits, comentarios y PR: español**, excepto el prefijo del commit
  (Conventional Commits), que va en inglés: `feat: agregar busqueda por serial`,
  `fix: corregir boton que se partia en dos lineas`. Detalle completo y lista de
  prefijos válidos en [docs/normas-de-trabajo.md](docs/normas-de-trabajo.md#9-idioma-commits-comentarios-y-pull-requests).
  Los identificadores del código (clases, métodos, variables, rutas) siguen en inglés.

Bloques y a quién pertenecen (ver `docs/<nombre>.md` de cada persona):

| Bloque | Carpeta Livewire | Ruta |
|---|---|---|
| Equipos (Juan José) | `app/Livewire/Equipos` | `/equipos` |
| Movimientos (Anuar) | `app/Livewire/Movimientos` | `/movimientos` |
| Importación (Juan Camilo) | `app/Livewire/Importacion` | `/importacion` |
| Reportes (Alex) | `app/Livewire/Reportes` | `/reportes` |
| Administración (Manuel) | `app/Livewire/Admin` | `/admin` |
| Etiquetas QR (Manuel) | `app/Livewire/Qr` | `/qr` |

## Sistema de diseño

Sigue el prototipo "Hoja de Vida de Equipos — Prototipo" en Claude Design. Paleta y
tipografía ya están en `tailwind.config.js` (colores `navy`, `primary`, `success`,
`warning`, `danger`, `info`, `neutral`; fuentes Source Sans 3 + Work Sans). Tamaños de
letra exactos del prototipo (no usar la escala por defecto de Tailwind sin más —
usar valores explícitos `text-[13px]`/`text-[14px]`/`text-[15px]` donde el prototipo
los define así):

- Título de página: 26px → usa `<x-ui.page-header title="..." subtitle="...">` (clase
  `.page-title`), como primer elemento del contenido — **no** en la barra superior.
- Título de sección dentro de una card: 16px → clase `.section-title`.
- Texto de tabla/cuerpo: 14px. Labels de formulario, badges, texto muted: 13px.
- Números grandes de KPI: 30px (`<x-ui.kpi-card>`).

Layout compartido en `resources/views/components/layouts/app-shell.blade.php`: sidebar
**siempre fijo** (nunca `position:static` en desktop, o vuelve a bajar con el scroll),
barra superior con búsqueda global + campanita de pendientes de firma + usuario
(`livewire.layout.topbar-user-menu`), íconos de menú en `nav-icon.blade.php`.

Componentes reutilizables en `resources/views/components/ui/`: `card`, `button`, `badge`,
`table`, `input`, `modal`, `timeline`, `definition-list`, `kpi-card`, `segmented-control`,
`page-header`. Extiende estos antes de inventar estilos nuevos.

## Al tocar el diseño de una pantalla nueva

Si el prototipo de Claude Design tiene un artboard para esa pantalla, **leer el archivo
`.dc.html` real** (vía el Artifact) antes de maquetar — un resumen de otra sesión puede
omitir detalles (pasó con la barra superior completa la primera vez). No inventar
colores, tamaños ni íconos que no estén ya en `tailwind.config.js` o en los componentes
`x-ui.*`/`x-layouts.*` existentes.

## Documentación completa

- `docs/analisis-requerimientos.md` — requerimientos, modelo de dominio, reglas de negocio.
- `docs/plan-2-semanas.md` — plan de trabajo de la Fase 1.
- `docs/normas-de-trabajo.md` — reglas de ramas/carpetas/eventos en detalle.
- `docs/juan-jose.md`, `docs/anuar.md`, `docs/juan-camilo.md`, `docs/alex.md`,
  `docs/manuel.md` — tareas específicas de cada integrante.
- `odd/tasks/bootstrap-hoja-vida-equipos.md` — historial del bootstrap inicial del
  proyecto (qué se construyó, decisiones tomadas, problemas de entorno encontrados).
