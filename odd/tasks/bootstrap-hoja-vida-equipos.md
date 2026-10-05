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
- Gap conocido, no bloqueante: faltan los usuarios de GitHub de los 5 integrantes para invitarlos y crear GitHub Projects/issues — queda pendiente para cuando el usuario los tenga.
- Discrepancia resuelta: el usuario pidió "3 archivos más" de documentación por programador, pero el plan divide el trabajo entre 5 personas (Juan José, Anuar, Juan Camilo, Alex, Manuel) sin contar al líder. Se crean 5 archivos, uno por persona, no 3 — se lo señalo al usuario con evidencia del propio plan.

## Tareas

- [ ] T1 — Instalar Laravel 12 + Sail (MySQL, Mailpit) + Breeze (stack Livewire) en la raíz del proyecto; registro público desactivado; `.env` de ejemplo. Ruta: inline/delegada (bash de instalación, sin research).
- [ ] T2 — Git init, primer commit, crear repo público `activos-gdc` en GitHub (Jhon-Quiceno) vía `gh`, push a `main`, crear y pushear `develop`.
- [ ] T3 — Migraciones y modelos de las ~19 entidades del análisis (sección 8): Equipo, TipoEquipo, Marca, ConfiguracionComputo, Componente, TipoComponente, PuestoTrabajo, Persona, Sede, Piso, Dependencia, Asignacion, Evento, CambioComponente, Diagnostico, Documento, EtiquetaQR, Usuario (extiende el de Breeze), Auditoria, Importacion. Incluir UUID interno del QR en Equipo. Ruta: delegada (escritor, multi-archivo).
- [ ] T4 — Seeders (11 sedes, pisos 1–8, 12 tipos de equipo con sus campos, marcas, sistemas operativos, motivos de baja) y factories (~100 equipos falsos con responsables y eventos). Ruta: delegada.
- [ ] T5 — `HistorialService::registrar` con firma definida y comentada. Ruta: inline (un archivo, ya entendido).
- [ ] T6 — Layout general y componentes Blade del diseño (menú/sidebar, tabla con filtros, formulario, card, botones, badges de estado, modal) + Tailwind config con la paleta/tipografía extraídas; logo institucional integrado. Ruta: delegada.
- [ ] T7 — Rutas vacías por bloque (equipos, movimientos, importacion, reportes, admin, qr) con controlador o componente Livewire de arranque. Ruta: delegada (junto con T6 o separada).
- [ ] T8 — Instalar paquetes del plan (dompdf, excel, simple-qrcode, activitylog, pest) y dejar configurados. Ruta: inline (comandos composer).
- [ ] T9 — Carpeta `docs/`: convertir `Plan_2_Semanas...docx` a `docs/plan-2-semanas.md`; agregar `docs/analisis-requerimientos.md` (del segundo docx, como referencia permanente); crear 5 archivos por persona (`docs/juan-jose.md`, `docs/anuar.md`, `docs/juan-camilo.md`, `docs/alex.md`, `docs/manuel.md`) con su bloque de tareas desglosado e independiente; `docs/normas-de-trabajo.md` con las reglas de ramas (siempre desde `develop`, nunca `main`), migraciones solo por el líder, carpetas por bloque, eventos solo vía HistorialService, `migrate:fresh --seed`, PR pequeños y pruebas antes de abrir PR. Ruta: delegada.
- [ ] T10 — README.md principal: qué es el proyecto, stack, paso a paso para levantarlo con Docker/Sail desde cero, estructura de carpetas, cómo correr pruebas. Ruta: delegada (junto con T9).
- [ ] T11 — Conectar base de datos y correr `migrate:fresh --seed` para dejar datos iniciales cargados. Ruta: inline (comando).
- [ ] T12 — GATE: construir UNA vista de referencia del diseño (login, por ser la más simple y la puerta de entrada) fiel al prototipo Claude Design, mostrarla al usuario en el navegador y ESPERAR su aprobación antes de construir el resto de las 16 pantallas. Ruta: delegada para el Blade, inline para levantar `sail up` y mostrar con Claude in Chrome.
- [ ] T13 — Commit final de este bootstrap, push a `develop`.

## Checks aplicables
- `sail artisan migrate:fresh --seed` corre sin errores.
- `sail artisan test` / `sail pest` corre (aunque sea sin tests propios todavía, el framework debe ejecutar).
- El proyecto levanta desde cero con `./vendor/bin/sail up -d` siguiendo el README.
- La vista de login (T12) se ve en el navegador antes de continuar con el resto del diseño.

## Progreso y evidencia
(se actualiza tarea por tarea con commit hash y resultado observado)
