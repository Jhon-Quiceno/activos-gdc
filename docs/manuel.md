# Manuel — Administración y QR

**Sistema de Hoja de Vida de Equipos · Gobernación de Córdoba · Dirección TIC**
Plan de dos semanas · Bloque: Apoyo

## Rol

Manuel lleva un **bloque con piezas pequeñas e independientes**: gestión de usuarios, listas administrables del sistema, generación y escaneo de códigos QR, etiquetas imprimibles y la auditoría del sistema.

## Tareas por día

| Días | Tareas | Entregable |
|---|---|---|
| 1–2 | Usuarios: crear, editar, desactivar (nunca borrar), contraseña inicial con cambio obligatorio en el primer ingreso, perfiles Administrador y Usuario. | Gestión de usuarios. |
| 3–4 | Listas administrables: sedes, pisos, dependencias, tipos de equipo, tipos de componente, marcas, sistemas operativos, motivos de baja (una pantalla genérica para todas). | Listas editables. |
| 5–6 | QR: generar al registrar o importar, ruta corta `/e/{uuid}`, pedir inicio de sesión y luego abrir la hoja de vida de ese equipo. Insertar el QR en la vista de Juan José. | Escanear con el celular abre el equipo. |
| 7–8 | Etiqueta QR individual imprimible (QR, serial, código, tipo, «Gobernación de Córdoba · Dirección TIC»). Si alcanza: impresión por lotes. | Etiqueta imprimible. |
| 9–10 | Auditoría (inicios de sesión, cambios, consultas y exportaciones de cédula) y cédula enmascarada en listados. Pruebas. | Bitácora funcionando. |

## Requerimientos funcionales a cargo

RF-08, RF-43, RF-44, RF-45, RF-46, RF-48, RF-50.

Consulta el detalle completo en el [Análisis de requerimientos](./analisis-requerimientos.md), sección 5.9 (usuarios y administración) y sección 5.10 (códigos QR y etiquetas). Presta especial atención a las reglas RN-17, RN-18 y RN-19 sobre el comportamiento del QR.

## Depende de / conecta con

- **Breeze de Jhon** — la autenticación base (Laravel Breeze con stack Livewire) ya está instalada; Manuel construye la gestión de usuarios y perfiles sobre ella.
- **La vista de hoja de vida de Juan José** — el QR individual se inserta ahí, y la ruta corta de escaneo (`/e/{uuid}`) redirige a esa misma vista tras validar la sesión.
- Usa el paquete `simplesoftwareio/simple-qrcode` para generar los códigos QR y `spatie/laravel-activitylog` para la bitácora de auditoría.
- El identificador del QR es un UUID interno que Jhon ya incluye en el modelo `Equipo` desde antes del día 1 (tarea 3 de la preparación del líder); Manuel no necesita crear ese campo, solo usarlo.

## Recordatorio de las normas de trabajo

- Ramas `feature/admin-<tarea>` o `feature/qr-<tarea>` creadas siempre desde `develop`, nunca desde `main`.
- Nunca crear ni tocar migraciones: si falta un campo (por ejemplo en `Usuario`, `EtiquetaQR` o `Auditoria`), se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Admin` (y/o `app/Livewire/Qr`), `resources/views/admin` (y/o `resources/views/qr`) y `tests/Admin` (y/o `tests/Qr`).
- Si alguna acción de este bloque genera un evento sobre un equipo, debe pasar por `HistorialService::registrar()`. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec laravel.test ./vendor/bin/pest` y que pase en verde.

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
