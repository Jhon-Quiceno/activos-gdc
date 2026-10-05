# Bootstrap — Sistema de Hoja de Vida de Equipos (Gobernación de Córdoba)

## Objetivo
Dejar lista la base del proyecto (lo que el plan de 2 semanas llama "lo que hace Jhon antes del día 1") para que Juan José, Anuar, Juan Camilo, Alex y Manuel puedan arrancar a programar el lunes sin configurar nada.

## Por qué
El usuario (Jhon, líder del proyecto) va a dejar el repo, Docker, el modelo de datos completo, datos de prueba, el diseño base y las piezas compartidas listas antes de repartir el trabajo. Fuente: `Plan_2_Semanas_Hoja_de_Vida_Equipos_v3.docx` y `Analisis_Requerimientos_Hoja_de_Vida_Equipos_v3.docx` (ambos en Descargas), y el prototipo "Hoja de Vida de Equipos — Prototipo" en Claude Design (17 artboards).

## Alcance (Fase 1 — 35 RF esenciales únicamente)
No se implementa funcionalidad completa de cada módulo todavía (eso lo hace cada persona en las 2 semanas); este bootstrap deja: estructura Laravel+Docker, modelo de datos completo, seeders/factories, servicio de eventos, layout y componentes Blade del diseño, rutas vacías por bloque, documentación, y UNA vista de referencia aprobada por el usuario antes de continuar con el resto del diseño.

## Restricciones / decisiones ya tomadas
- Laravel 12 (última estable) — todos los paquetes del plan la soportan, no hay razón para usar una versión anterior.
- Laravel Sail (MySQL + Mailpit) + Breeze stack Livewire. Registro público desactivado.
- Repo público `activos-gdc` bajo la cuenta GitHub `Jhon-Quiceno` (gh ya autenticado). Ramas `main` y `develop`.
- Paquetes: barryvdh/laravel-dompdf, maatwebsite/excel, simplesoftwareio/simple-qrcode, spatie/laravel-activitylog, pestphp/pest.
- Paleta/tipografía extraídas del prototipo (navy #163A6B / #1E4D8C, fondo #F5F7FA, texto #1C2733, etc.; Source Sans 3 + Work Sans) — ver detalle en la tarea de diseño.
- Logo institucional en `C:\Users\Jhon\Pictures\logoGob.svg`.
- Resuelto: no hace falta invitar colaboradores por usuario de GitHub — el líder comparte el repo (público) directamente con el equipo. GitHub Projects/issues quedan fuera de este bootstrap.
- Resuelto: el usuario confirmó dejar solo la base para las demás pantallas (respeta el reparto del plan de 2 semanas), pero pidió construir la primera pantalla real como ejemplo del patrón a seguir. Se construyó el listado de equipos con búsqueda (días 1–2 del bloque de Juan José) contra datos reales; `docs/juan-jose.md` actualizado para que arranque desde el día 3–4.
- Discrepancia resuelta: el usuario pidió "3 archivos más" de documentación por programador, pero el plan divide el trabajo entre 5 personas (Juan José, Anuar, Juan Camilo, Alex, Manuel) sin contar al líder. Se crean 5 archivos, uno por persona, no 3 — se lo señalo al usuario con evidencia del propio plan.

## Tareas

- [x] T1 — Instalar Laravel + Sail (MySQL, Mailpit) + Breeze (stack Livewire) en la raíz del proyecto; registro público desactivado; pnpm como package manager (cambio de última hora pedido por el usuario); Pest instalado y en verde (24 tests). NOTA: el instalador trajo **Laravel 13.34.0** (versión más nueva que mi recomendación inicial de Laravel 12 — corrijo: mi conocimiento estaba desactualizado, Laravel 13 ya es la última estable a oct-2026 y es la que quedó instalada). Docker Desktop no estaba corriendo, hubo que iniciarlo; `vendor/bin/sail` no soporta Git Bash/MinGW nativo en Windows (solo WSL2/macOS/Linux) así que se documentará usar `docker compose` directo en el README.
- [x] T2 — Git init, primer commit, repo público `activos-gdc` creado en GitHub (Jhon-Quiceno) vía `gh`, push a `main`, rama `develop` creada y pusheada, ambas ramas protegidas (PR obligatorio, sin force-push, sin delete). Evidencia: https://github.com/Jhon-Quiceno/activos-gdc
- [x] T3 — 22 migraciones + 21 modelos Eloquent del dominio completo (sección 8 del análisis), con relaciones bidireccionales. `Equipo` genera `qr_uuid` automáticamente. `Evento`/`Auditoria` son inmutables a nivel de modelo (RN-06/RF-12). `User` extendido con perfil/activo/debe_cambiar_contrasena.
- [x] T4 — `CatalogosSeeder` (sedes, pisos, dependencias, tipos de equipo, marcas, SO, motivos de baja, tipos de componente, puestos de trabajo), `UsersSeeder` (2 admin + 2 usuarios), `EquiposDemoSeeder` (~100 equipos con responsables, asignaciones y evento de alta vía HistorialService). Factories: Equipo, Persona, Componente, Evento.
- [x] T5 — `app/Services/HistorialService.php` con `registrar()` documentado; único punto permitido para crear `Evento`.
- [x] T6 — Tailwind config con paleta institucional (navy/primary/success/warning/danger/info/neutral) y tipografía (Source Sans 3 + Work Sans); `app-shell` layout con sidebar; UI kit completo (card, button, badge, table, input, modal, timeline, definition-list, kpi-card, segmented-control); logo en `public/images/logoGob.svg`.
- [x] T7 — Rutas `/equipos`, `/movimientos`, `/importacion`, `/reportes`, `/admin`, `/qr` + `/dashboard`, cada una con su componente Livewire de arranque.
- [x] T8 — dompdf, excel, simple-qrcode, activitylog, Pest instalados y configurados (migraciones/config publicados).
- [x] T9 — `docs/plan-2-semanas.md`, `docs/analisis-requerimientos.md`, 5 archivos por persona (juan-jose, anuar, juan-camilo, alex, manuel — no 3, ver nota arriba), `docs/normas-de-trabajo.md`.
- [x] T10 — `README.md` reescrito completo con stack, puesta en marcha con Docker, estructura, pruebas, ramas. Incluye nota crítica sobre `-u sail` (ver T12).
- [x] T11 — `migrate:fresh --seed` corrido y verificado limpio con todo el modelo combinado (29/29 tests, ~100 equipos, relaciones cargando sin error).
- [x] T12 — GATE: login + dashboard construidos, mostrados al usuario en su propio navegador (no solo capturas) y APROBADOS. Bugs encontrados y corregidos en vivo: (1) Breeze en inglés con estilos genéricos → componentes restilados + español completo (laravel-lang, RNF-02); (2) logo invisible sobre sidebar navy → tarjeta blanca + wordmark; (3) `docker compose exec` sin `-u sail` deja `storage/`/`bootstrap/cache` de root, el servidor (user `sail`) no puede compilar vistas → 500 `tempnam()`; corregido y documentado. Feedback del usuario tras aprobar: "mejorar los diseños para que no se vean tan estáticos" → ver T12b.
- [x] T12b — Pulido visual: sombras/transiciones/feedback táctil en todo el UI kit, barra de acento animada en el ítem activo del sidebar, dashboard con KPIs reales (100 equipos, 100 movimientos del mes, 46 pendientes de verificar con barra de progreso) + timeline de actividad reciente real, y las 6 pantallas stub con estado vacío intencional (ícono, descripción, skeleton) en vez de texto plano. Verificado visualmente en el navegador.
- [x] T13 — Verificación final: `pnpm run build` limpio, `./vendor/bin/pest` 29/29 en verde, revisado en el navegador (login, dashboard, stub de Equipos). Todo commiteado y pusheado a `develop` (9 commits en total). Bootstrap del proyecto completo.

- [x] T14 — Pantalla real "Equipos" (listado + búsqueda RF-07) construida contra datos reales de la base, como ejemplo del patrón (Livewire + `x-ui.table`/`x-ui.badge`) para el resto de las pantallas. Verificado en el navegador: búsqueda por código sin guiones encuentra el equipo correcto. `docs/juan-jose.md` actualizado: arranca en el día 3.
- [x] T15 — Corrección de fidelidad con el diseño real de Claude Design, a pedido del usuario tras revisar el prototipo con más cuidado (3 rondas de ajuste): sidebar siempre fijo (no baja con el scroll), barra superior con búsqueda global + campanita de pendientes de firma + avatar/usuario (antes el menú de usuario estaba mal ubicado al fondo del sidebar), íconos reales del prototipo en cada ítem del menú + separador "Administración", tamaños de letra exactos (13/14/15/26/30px) aplicados a todos los componentes compartidos (no solo a Inicio) para que las pantallas nuevas los hereden. Ajuste final: Equipos conserva su buscador propio de tabla (reactivo) y la barra global se oculta solo en esa pantalla (`routeIs('equipos.*')`); el resto de pantallas sí la mantiene.

- [x] T16 — Construidas las 14 pantallas restantes del prototipo (HojaVida, RegistrarEquipo, Traslado, Baja, Diagnostico, Componente, Pendientes, FormatoEntrega, FormatoBaja, Importar, Reportes, Usuarios, Listas, VerificarMovil), a pedido explícito del usuario tras revertir la decisión anterior de dejar solo la base. Menú lateral corregido para coincidir exactamente con el prototipo (Inicio/Equipos/Traslados/Bajas/Importar inventario/Reportes + separador Administración con Usuarios/Listas — se quitaron "Movimientos" y "Etiquetas QR", que no están en el diseño). Dashboard enriquecido con las 4 secciones que faltaban (Calidad del inventario, Pendientes de firma, Equipos por tipo, Equipos por sede — confirmado con el prototipo que no hay gráficas reales en ningún lado, son barras CSS simples).
- [x] T17 — 3 rondas de agentes de revisión de diseño (leyendo el .dc.html real de cada pantalla, no un resumen) encontraron y corrigieron decenas de diferencias: asteriscos inventados, textos literales distintos, tamaños de letra sin explicitar, colores de alertbox incorrectos, y un bug real de datos (formato de baja le faltaba el campo "Propiedad"). 4 commits locales, **sin pushear** (pedido explícito del usuario: revisar antes de subir).
- Nota de entorno recurrente: varios agentes (6 de ~14 lanzados en total) se trabaron ("stalled: no progress for 600s") o chocaron entre sí corriendo `pnpm build`/`pest` contra el mismo contenedor Docker en paralelo. Lección para la próxima: lanzar como máximo 1 agente pesado (que corra build/test) a la vez contra este entorno; si se traba, revisar `git status` primero (casi siempre ya dejó el trabajo escrito) y terminar la verificación manualmente en vez de relanzar desde cero.

## Siguiente paso
El bootstrap que le correspondía al líder (Jhon) antes del día 1 está terminado. Lo que sigue es trabajo de cada uno de los 5 programadores según `docs/juan-jose.md`, `docs/anuar.md`, `docs/juan-camilo.md`, `docs/alex.md` y `docs/manuel.md` — construir sus bloques reales encima de esta base, siempre en ramas `feature/<bloque>-<tarea>` desde `develop`. Pendiente no bloqueante: usuarios de GitHub del equipo para invitarlos al repo.

## Checks aplicables
- `sail artisan migrate:fresh --seed` corre sin errores.
- `sail artisan test` / `sail pest` corre (aunque sea sin tests propios todavía, el framework debe ejecutar).
- El proyecto levanta desde cero con `./vendor/bin/sail up -d` siguiendo el README.
- La vista de login (T12) se ve en el navegador antes de continuar con el resto del diseño.

## Progreso y evidencia
(se actualiza tarea por tarea con commit hash y resultado observado)
