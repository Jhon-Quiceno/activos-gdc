# Plan de trabajo de dos semanas

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Equipos — Fase 1
Basado en el [Análisis de Requerimientos v3](./analisis-requerimientos.md) (35 requerimientos esenciales)

Jhon (líder) · Juan José y Anuar (núcleo) · Juan Camilo, Alex y Manuel (apoyo) · 10 días hábiles
Versión 1.2 · Octubre de 2026

---

## 1. La idea en una página

Dos semanas solo alcanzan si nadie espera a nadie. Por eso el plan tiene tres reglas:

1. **El líder deja todo listo antes del día 1.** Repositorio, Docker, modelo de datos completo, datos de prueba, diseño base y las piezas compartidas. Así los cinco compañeros arrancan el lunes a programar, no a configurar.
2. **Cada persona es dueña de un bloque separado.** Carpetas, rutas y pantallas propias. Nadie toca migraciones ni archivos de otro bloque sin avisar al líder.
3. **Se construye solo la Fase 1.** Lo esencial del análisis. Lo «importante» y lo «deseable» se hace únicamente si sobra tiempo el día 9, y toda idea nueva se anota para después.

### Reparto de personas y bloques

| Persona | Rol | Bloque que entrega |
|---|---|---|
| **Jhon** (Líder) | Líder, diseño y revisión | Base del proyecto, diseño (Claude Design → Blade), revisión de todos los PR, integración diaria, demo. |
| **Juan José** (Núcleo 1) | Equipos y hoja de vida | Registro de equipos con formulario por tipo, búsqueda, vista de hoja de vida, historial de eventos inmutable, componentes y cambio de componente. |
| **Anuar** (Núcleo 2) | Movimientos y formatos | Responsables y asignaciones, traslado, diagnóstico, baja y anulación, formatos PDF de entrega y baja, carga de firmados y «Pendiente de firma». |
| **Juan Camilo** (Apoyo) | Carga del inventario | Importación del Excel 2026 con normalización, vista previa, exclusión de personales y marca «Pendiente de verificar». |
| **Alex** (Apoyo) | Reportes | Reportes con filtros, exportación a Excel y PDF, reporte de calidad del inventario. |
| **Manuel** (Apoyo) | Administración y QR | Usuarios y cambio de contraseña, listas administrables, auditoría y cédula enmascarada, código QR, ruta de escaneo y etiquetas. |

### Por qué esta división

Los dos núcleos llevan el corazón del sistema: sin hoja de vida y sin movimientos con sus formatos, no hay producto. Los tres de apoyo trabajan en bloques que se conectan al núcleo por pocos puntos (el modelo Equipo y el servicio de eventos), así que pueden avanzar en paralelo con datos de prueba desde el día 1 y ayudar a los núcleos en la segunda semana.

---

## 2. Lo que hace Jhon antes del día 1

Esta es la lista que desbloquea a todos. Si algo de aquí no está, ese bloque empieza tarde; por eso es lo primero.

| # | Tarea | Para qué sirve |
|---|---|---|
| 1 | Organización y repositorio en GitHub; ramas `main` y `develop` protegidas; agregar a los 5. | Todos clonan el mismo código. |
| 2 | Laravel + Sail (MySQL, Mailpit) + Breeze con Livewire; registro público desactivado; README con los pasos para correrlo. | Arranque en minutos con Docker. |
| 3 | Migraciones y modelos de TODO el modelo del análisis (sección 8): equipos, tipos, marcas, componentes, personas, sedes, pisos, dependencias, asignaciones, eventos, documentos, etiquetas QR, importaciones, usuarios. Incluir el identificador interno del QR (UUID) en equipos. | Nadie crea tablas; se evitan choques de migraciones. |
| 4 | Seeders de listas (11 sedes, pisos 1–8, 12 tipos de equipo con sus campos, marcas, sistemas operativos, motivos de baja) y factories con unos 100 equipos falsos con responsables y eventos. | Todos trabajan con datos desde el día 1, sin esperar la importación. |
| 5 | Servicio de eventos (`HistorialService::registrar`) con la firma definida y comentada, aunque sea simple. | Juan José, Anuar y Manuel registran eventos de la misma forma. |
| 6 | Layout general y componentes Blade del diseño: menú, tabla con filtros, formulario, tarjeta, botones, badges de estado, modal. | Las pantallas salen iguales y rápido. |
| 7 | Rutas vacías por bloque (equipos, movimientos, importación, reportes, admin, qr) con su controlador o componente Livewire de arranque. | Cada uno sabe dónde escribir. |
| 8 | GitHub Projects con un issue por tarea de este plan, asignado a su dueño. | Seguimiento sin reuniones largas. |
| 9 | Sesión de 1 hora el día 1 en la mañana: todos corren el proyecto y se explica el modelo. | Se resuelven los problemas de Docker de una vez. |

**Paquetes a instalar de una vez:** `barryvdh/laravel-dompdf` (formatos y reportes en PDF), `maatwebsite/excel` (importación y exportación), `simplesoftwareio/simple-qrcode` (QR), `spatie/laravel-activitylog` (auditoría), `pestphp/pest` (pruebas).

---

## 3. Calendario general

| Días | Meta del equipo | Hito para Jhon |
|---|---|---|
| Día 1 | Todos corren el proyecto y abren su primer PR. | Ningún bloqueo de Docker al final del día. |
| Días 2–4 | Cada bloque construye su parte principal con datos de prueba. | Día 4: registrar un equipo y verlo en su hoja de vida. |
| Día 5 | Primera integración en `develop` y revisión de la semana. | Demo interna: equipo → traslado → PDF. |
| Días 6–8 | Completar bloques y conectar: importación real, QR en la hoja de vida, reportes con datos importados. | Día 8: inventario 2026 cargado en el ambiente de pruebas. |
| Día 9 | Congelar funciones. Pruebas cruzadas: cada uno prueba el bloque de otro. | Lista de errores priorizada. |
| Día 10 | Corrección de errores, README y manual corto, demo al jefe. | Versión v1.0 etiquetada en `main`. |

---

## 4. Tareas por persona

Cada bloque tiene su propio documento autocontenido con el detalle día a día, requerimientos y dependencias:

- [Juan José — Núcleo 1: Equipos y hoja de vida](./juan-jose.md)
- [Anuar — Núcleo 2: Movimientos y formatos](./anuar.md)
- [Juan Camilo — Carga del inventario](./juan-camilo.md)
- [Alex — Reportes](./alex.md)
- [Manuel — Administración y QR](./manuel.md)

### 4.6 Jhon — durante las dos semanas

- Revisar y fusionar los PR el mismo día (máximo 24 horas). Un PR esperando es una persona bloqueada.
- Pasar el diseño de Claude Design a componentes Blade a medida que cada bloque los necesite; prioridad a la hoja de vida y al formulario de equipo.
- Ser el único que aprueba cambios al modelo de datos.
- Integrar `develop` cada tarde y verificar que el proyecto sigue corriendo desde cero (`migrate:fresh --seed`).
- Mover gente: si un núcleo se atrasa, el apoyo que vaya adelantado pasa a ayudarle desde el día 6.
- Preparar la demo del día 10 y el despliegue en el servidor de pruebas.

---

## 5. Reglas para no estorbarse

Ver el detalle completo en [normas-de-trabajo.md](./normas-de-trabajo.md). Resumen:

| Tema | Regla |
|---|---|
| Ramas | `feature/<bloque>-<tarea>` desde `develop`. PR pequeños, uno por tarea, al menos uno cada dos días. |
| Migraciones | Solo Jhon. Si alguien necesita un campo, lo pide en el grupo y Jhon crea la migración ese mismo día. |
| Carpetas | Cada bloque escribe en `app/Livewire/<Bloque>`, `resources/views/<bloque>` y `tests/<Bloque>`. Lo compartido (componentes, HistorialService) lo cambia Jhon. |
| Eventos | Todo cambio sobre un equipo pasa por `HistorialService`. Nunca se inserta un evento a mano. |
| Datos | Siempre con `migrate:fresh --seed`. Nadie depende de datos que solo existen en su máquina. |
| Seguimiento | Mensaje diario en el grupo antes de las 9 a.m.: ayer, hoy, bloqueos. Llamada de 15 minutos solo si hay bloqueos. |
| Terminado | Funciona desde cero en Docker, sigue el diseño, registra el evento con su autor, tiene una prueba de la regla principal y Jhon aprobó el PR. |

---

## 6. Qué queda fuera de estas dos semanas

Se anota como Fase 2, salvo que sobre tiempo el día 9:

- Firma capturada en pantalla e insertada en el PDF (RF-32, deseable en el análisis). En la Fase 1 se firma impreso y se sube escaneado, que es lo que exige RF-30. Si se quiere adelantar, lo toma Anuar o Manuel el día 9.
- Puestos de trabajo y traslado del puesto completo (RF-05), fotografías (RF-06), tipos de evento configurables (RF-13).
- Historial por componente y destino de lo retirado (RF-16, RF-17), paz y salvo (RF-23).
- Tablero de inicio (RF-36), etiquetas QR por lotes y reimpresión (RF-49, RF-51), cargas posteriores de Excel (RF-42).
- Hoja de vida exportable a PDF (RF-33) y reportes de obsolescencia y antivirus.

### Riesgo principal

El bloque de Juan José es del que dependen casi todos. Si el día 4 no se puede registrar un equipo y ver su hoja de vida, Jhon debe pasar a Manuel a ayudarle de inmediato y mover las etiquetas QR al día 9.

---

**Ver también:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
