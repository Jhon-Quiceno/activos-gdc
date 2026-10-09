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

## Estado al cierre de la Fase 1 (revisado por Jhon, 2026-10-08)

| Días | Tarea | Estado |
|---|---|---|
| 1–2 | Usuarios | ✅ Hecho (`UsuariosPanel.php`): crear, editar, desactivar, perfiles. ⚠️ **RF-44 parcial**: se guarda `debe_cambiar_contrasena`, pero no hay ningún middleware que lo use para forzar el cambio al iniciar sesión — hoy el flag no tiene ningún efecto. |
| 3–4 | Listas administrables | ✅ Hecho (`ListasPanel.php`), 8 catálogos editables. |
| 5–6 | QR: generación, ruta corta, apertura | ❌ No hecho. El UUID (`qr_uuid`) se genera solo al crear el equipo, pero el paquete `simplesoftwareio/simple-qrcode` (ya instalado) no se usa en ningún lado: no hay imagen de QR, no existe la ruta `/e/{uuid}` en `routes/web.php`, no hay flujo de "pedir sesión → abrir hoja de vida". El espacio para esto ya está reservado en la hoja de vida de Juan José (`hoja-de-vida.blade.php`, el bloque "Etiqueta QR"), con un comentario explicando el contrato esperado. |
| 7–8 | Etiqueta imprimible | ❌ No hecho (depende de lo anterior). |
| 9–10 | Auditoría, cédula enmascarada | ❌ No hecho. El modelo `Auditoria` existe, está bien hecho e inmutable, y `User` ya tiene la relación (`hasMany`) — pero **no hay un solo `Auditoria::create()` en todo el código**: no se registra ningún login, cambio ni consulta de cédula. Cédula enmascarada: solo está confirmada en los PDF de Equipos y Movimientos (RN-12); no verifiqué listados/pantallas de Admin ni QR, así que no asumas que está cubierta ahí. |

**Para Manuel, si sigue en Fase 1:** junto con Alex, es el bloque más atrasado. Prioridad sugerida: (1) middleware de cambio de contraseña obligatorio (rápido, cierra un hueco de seguridad); (2) conectar `Auditoria::create()` en los puntos clave (login, cambios, consulta/exportación de cédula) — es requisito de cumplimiento (Ley 1581); (3) QR real + ruta `/e/{uuid}`; (4) etiqueta imprimible.

## Depende de / conecta con

- **Breeze de Jhon** — la autenticación base (Laravel Breeze con stack Livewire) ya está instalada; Manuel construye la gestión de usuarios y perfiles sobre ella.
- **La vista de hoja de vida de Juan José** — el QR individual se inserta ahí, y la ruta corta de escaneo (`/e/{uuid}`) redirige a esa misma vista tras validar la sesión.
- Usa el paquete `simplesoftwareio/simple-qrcode` para generar los códigos QR y `spatie/laravel-activitylog` para la bitácora de auditoría.
- El identificador del QR es un UUID interno que Jhon ya incluye en el modelo `Equipo` desde antes del día 1 (tarea 3 de la preparación del líder); Manuel no necesita crear ese campo, solo usarlo.

## Recordatorio de las normas de trabajo

- Ramas `feature/admin-<tarea>` (usuarios y listas) o `feature/qr-<tarea>` (códigos QR y etiquetas) creadas siempre desde `develop`, nunca desde `main`. Son dos bloques, así que usa el prefijo que corresponda a la tarea.
- Nunca crear ni tocar migraciones: si falta un campo (por ejemplo en `Usuario`, `EtiquetaQR` o `Auditoria`), se pide a Jhon y lo agrega el mismo día.
- Escribir solo en `app/Livewire/Admin` y `resources/views/livewire/admin` y `tests/Feature/Admin` para usuarios/listas; en `app/Livewire/Qr`, `resources/views/livewire/qr` y `tests/Feature/Qr` para códigos QR y etiquetas.
- Si alguna acción de este bloque genera un evento sobre un equipo, debe pasar por `HistorialService::registrar()`. Nunca se inserta un evento a mano.
- Trabajar siempre con `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed`.
- Antes de abrir un PR, correr `docker compose exec -u sail laravel.test ./vendor/bin/pest` y que pase en verde.

### Cómo crear tu rama (copiar y pegar)

```bash
git checkout develop
git pull origin develop

# Para usuarios y listas administrables:
git checkout -b feature/admin-usuarios   # cambia "usuarios" por tu tarea

# Para códigos QR y etiquetas (en otra tarea, otra rama):
git checkout -b feature/qr-generacion    # cambia "generacion" por tu tarea

# ...trabajar y commitear...
git push -u origin <nombre-de-tu-rama>
# abrir el Pull Request en GitHub apuntando a develop, nunca a main
```

Para el detalle completo de estas reglas, ver [normas-de-trabajo.md](./normas-de-trabajo.md).

---

**Para profundizar:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Normas de trabajo](./normas-de-trabajo.md)
