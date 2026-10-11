# Plan de Fase 2

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Equipos — Fase 2
Basado en el [Análisis de Requerimientos v3](./analisis-requerimientos.md) (sección 13,
«Alcance por fases») y en el estado real de cierre de la Fase 1 ([plan-2-semanas.md](./plan-2-semanas.md), sección 0)

Jhon (líder) · Octubre de 2026 · Versión 1.0

---

## 0. Antes de empezar: cerrar la Fase 1

Dos cosas de la Fase 1 quedaron abiertas y bloquean dar por cerrado el inventario real.
No son Fase 2, pero van primero:

| # | Pendiente | Qué hace falta | Bloquea |
|---|---|---|---|
| 1 | Fusionar PR #9 y #10 (Juan José) | Edición de equipo, QR real, filtros avanzados de hoja de vida/movimientos. Revisar el conflicto esperado con `feature/equipos-serial-provisional` (ya fusionada) en `Crear.php`/`page-crear.blade.php` — son las mismas líneas de `sinCodigoActivo`/`sinSerial`, debería ser chico. | RF-08, RF-48, RF-50 (QR, esenciales de Fase 1 pero sin fusionar). |
| 2 | Corregir y recargar el inventario 2026 real | El Excel de Almacén trae 14 códigos de activo repetidos **dentro del propio archivo**; hoy el importador bloquea la confirmación completa porque RN-03 solo permite justificar un duplicado contra un equipo *ya existente*, no dos duplicados en el mismo lote. Dos salidas: (a) Almacén corrige esos 14 códigos en el Excel, o (b) se extiende la justificación de RN-03 a este caso. Mientras no se decida, el inventario real sigue sin cargar completo. | Criterios de aceptación de la carga (sección 12 del análisis): «equipos creados = equipos del archivo menos los personales, sin pérdidas». |

---

## 1. Qué entra en Fase 2

Los 15 requerimientos «importantes» que quedaron fuera de la Fase 1 a propósito (ver
`analisis-requerimientos.md`, sección 13), agrupados por para qué sirven. Ninguno es
bloqueante: el sistema ya es usable sin ellos.

### 1.1 Trazabilidad más fina

| ID | Requerimiento | Por qué importa | Bloque natural |
|---|---|---|---|
| RF-16 | Consultar el historial de cada componente y la configuración que tenía el equipo en una fecha dada. | Hoy se ve el evento de cambio, pero no "qué RAM tenía este PC el 3 de marzo". | Equipos |
| RF-17 | Registrar el destino del componente retirado: bodega, otro equipo, descarte. | El modelo `CambioComponente` ya tiene la columna `destino_retirado`; falta exigirla y mostrarla. | Movimientos |
| RF-13 | Permitir configurar nuevos tipos de evento sin desarrollo. | Hoy los 7 tipos de evento son un enum fijo en la migración de `eventos`. Pasar a tabla editable es un cambio de modelo — lo aprueba Jhon. | Núcleo / Jhon |

### 1.2 Puestos de trabajo y fotos

| ID | Requerimiento | Por qué importa | Bloque natural |
|---|---|---|---|
| RF-05 | Agrupar equipos en un puesto de trabajo y trasladar el puesto completo o un equipo suelto. | El modelo `PuestoTrabajo` ya existe (la importación lo usa para ubicación), pero no hay pantalla para agruparlo ni trasladarlo como unidad. | Equipos / Movimientos |
| RF-06 | Adjuntar fotografías al equipo (chasis, etiqueta, serial). | Útil para la verificación en sitio del inventario 2026 (sección 12). | Equipos |

### 1.3 Responsables y bajas

| ID | Requerimiento | Por qué importa | Bloque natural |
|---|---|---|---|
| RF-23 | Generar el paz y salvo o listado de devolución cuando un funcionario se retira. | Anuar ya tiene `GestorAsignaciones::equiposACargo()`; falta la pantalla/PDF consolidado (quedó como hallazgo abierto de Anuar, ver su documento). | Movimientos |
| RF-26 | Anular una baja registrada por error mediante un evento justificado. | RN-10 ya dice que un equipo dado de baja no admite nuevos eventos salvo anulación; falta el flujo específico de baja (el de diagnóstico ya existe). | Movimientos |
| RF-27 | Generar el listado de equipos dados de baja en un período, para la dependencia que tramita la baja administrativa. | El reporte "Dados de baja" (Fase 1, ya hecho) cubre la consulta; falta el formato de entrega específico para esa dependencia, si lo pide el jefe. | Reportes |

### 1.4 Firmas y seguimiento

| ID | Requerimiento | Por qué importa | Bloque natural |
|---|---|---|---|
| RF-31 | Listar los eventos pendientes de firma. | El reporte "Pendientes de firma" (Fase 1, ya hecho) cubre esto — **revisar si ya satisface el requerimiento o falta una vista operativa aparte** (con acción directa de "subir firmado" desde ahí). | Reportes / Movimientos |
| RF-36 | Mostrar un tablero con totales por tipo, estado, sede y dependencia. | Primer vistazo al llegar al sistema; hoy no existe pantalla de inicio con KPIs. | Reportes |

### 1.5 Importación y administración

| ID | Requerimiento | Por qué importa | Bloque natural |
|---|---|---|---|
| RF-42 | Permitir cargas posteriores desde Excel para nuevos levantamientos o equipos nuevos. | Hoy el importador asume que es la carga inicial; una segunda carga necesita detectar equipos que ya existen (por serial/código) y no duplicarlos. | Importación |
| RF-47 | Permitir al Administrador restablecer la contraseña de un usuario. | `UsuariosPanel::restablecerContrasena()` ya existe como TODO explícito; falta la lógica real (probablemente generar una temporal + `debe_cambiar_contrasena = true`, reusando el middleware de Fase 1). | Administración |

### 1.6 Etiquetas QR por lotes

| ID | Requerimiento | Por qué importa | Bloque natural |
|---|---|---|---|
| RF-49 | Imprimir etiquetas QR por lotes, filtrando por sede, piso, dependencia o tipo. | Necesario para la verificación en sitio (sección 12): cada visita etiqueta todos los equipos de esa sede de una vez. | QR |
| RF-51 | Reimprimir la etiqueta de un equipo por daño o pérdida, registrando quién y cuándo. | El modelo `EtiquetaQR` ya guarda `motivo` y `lote` para esto. | QR |

### 1.7 Fase 3 (solo si sobra tiempo)

| ID | Requerimiento |
|---|---|
| RF-32 | Capturar la firma en pantalla (tableta o celular) en vez de imprimir y escanear. |

---

## 2. Prioridad sugerida

No todo pesa igual. Orden sugerido si hay que elegir:

1. **RF-49 y RF-51** (etiquetas QR por lotes/reimpresión) — depende de que primero se
   fusione el PR #10 (QR real). Sin esto, la verificación en sitio del inventario 2026
   (que ya debería estar en marcha según la sección 12 del análisis) etiqueta equipo por
   equipo en vez de por sede.
2. **RF-47** (restablecer contraseña) — ya está marcado como TODO explícito en el código;
   es chico y cierra un hueco de usabilidad real para el Administrador.
3. **RF-42** (cargas posteriores de Excel) — Juan Camilo ya conoce el importador a fondo;
   es la continuación natural de lo que ya hizo.
4. **RF-36** (tablero) — alto impacto visual para el jefe, bajo riesgo (solo lectura).
5. El resto, según lo que priorice la Dirección TIC al ver la demo de Fase 1.

## 3. Reparto sugerido

Los bloques de Alex (Reportes) y Manuel (Administración) quedaron libres al cerrar sus
tareas de Fase 1. Sugerido, a decidir por Jhon:

| Persona | Qué ya conoce | Tomaría en Fase 2 |
|---|---|---|
| Alex | Los 15 reportes, exportación Excel/PDF | RF-36 (tablero), RF-31 si falta algo además del reporte |
| Manuel | Usuarios, listas, cambio de contraseña, auditoría, y QR si se suma al PR #10 | RF-47 (restablecer contraseña), RF-49/RF-51 (etiquetas por lotes) |
| Juan Camilo | El importador completo, de punta a punta | RF-42 (cargas posteriores) |
| Juan José | Equipos, hoja de vida, componentes | RF-05 (puestos de trabajo), RF-06 (fotos), RF-16 |
| Anuar | Movimientos, formatos, firmas | RF-23 (paz y salvo), RF-26 (anular baja), RF-17 (destino de lo retirado) |

## 4. Reglas

Las mismas de la Fase 1 ([normas-de-trabajo.md](./normas-de-trabajo.md)): ramas
`feature/<bloque>-<tarea>` desde `develop`, PR obligatorio, solo Jhon crea migraciones,
`migrate:fresh --seed` siempre, pest en verde antes de abrir PR, commits en español con
prefijo Conventional Commit en inglés.

---

**Ver también:** [Análisis de requerimientos](./analisis-requerimientos.md) ·
[Plan de la Fase 1](./plan-2-semanas.md) · [Normas de trabajo](./normas-de-trabajo.md)
