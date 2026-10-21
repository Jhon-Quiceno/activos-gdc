# Plan de Fase 3 — Consolidación

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Activos Tecnológicos — Fase 3
Basado en el [Análisis de requerimientos v4.2](./analisis-requerimientos.md) (§13) y el [modelo de datos v4](./base-de-datos.md) (§10)

Jhon (líder) · Juan José · 10 días hábiles
Versión 3.0 · 21 de octubre de 2026

---

## 0. Qué es la Fase 3

La Fase 2 dejó el sistema sobre las fuentes oficiales (modelo v4, valoración GT-M4, levantamiento con acta, matriz FOR.01, cargas). La Fase 3 **consolida**: completa los siete requerimientos «importantes» que quedaron por fuera y que hacen el trabajo diario más cómodo y más trazable.

| RF | Requerimiento (análisis §5) | Prioridad |
|---|---|---|
| RF-05 | Agrupar equipos en un puesto de trabajo y trasladar el puesto completo. | I |
| RF-06 | Adjuntar fotografías al equipo. | I |
| RF-13 | Configurar nuevos tipos de evento sin desarrollo. | I |
| RF-16 | Consultar la configuración en una fecha dada. | I |
| RF-17 | Registrar el destino del componente retirado (bodega, otro equipo con su referencia, descarte). | I |
| RF-31 | Listar pendientes de firma. | I |
| RF-42 | Cargas posteriores sin duplicar equipos existentes. | I |

**Fuera de esta fase:** la firma en pantalla (RF-32) es la Fase 4. **El mantenimiento preventivo y correctivo sigue fuera del alcance** (RN-08).

---

## 1. Punto de partida

### Fase 2 cerrada ✅

Auditoría RF por RF del 21 de octubre de 2026 contra `develop`: todos los esenciales de la Fase 2 (RF-01, 02, 04, 07, 09, 10, 12, 18, 19, 34, 37, 38, 39, 44, 45, 53 a 58 y 60) y sus importantes (RF-23, 27, 36, 47, 52, 59) están hechos, con Pest en verde. Lo último en cerrarse fue RF-18 (jefe inmediato y planta en el formulario de persona), RF-37 («sin valoración» y «sin datos de adquisición» en la calidad del inventario) y RF-45 (ubicación de dependencias por piso y campos v4 de tipos de equipo, sistemas operativos y tipos de componente).

Decisiones registradas que **no** se reabren en esta fase ([base-de-datos.md §6.2](./base-de-datos.md#62-segundo-tiempo--al-cerrar-el-día-2-jhon)):

- `personas.numero_documento` sigue siendo opcional (la carga crea responsables sin cédula a propósito).
- El tablero no se compara con los conteos del PAMP (falta esa fuente en el sistema).
- RF-39: la tabla editable de equivalencias es solo para dependencias; sedes, marcas, modelos y sistemas operativos se resuelven en código.

### Antes del día 1 (Jhon)

1. Pasar `develop` a `main` y poner la etiqueta `v2.0`.
2. Replicar la versión 4.2 del análisis en `analisis-requerimientos-v4.docx` (ver §1.3 del análisis).

---

## 2. Reparto

Solo dos personas. Juan José asume su bloque y los que en la Fase 2 eran de Anuar y de Juan Camilo; Jhon asume las piezas compartidas y los bloques que eran de Alex y de Manuel.

| Persona | Bloques | RF a cargo | Detalle |
|---|---|---|---|
| **Jhon** | Líder: base de datos y migraciones, piezas compartidas (`HistorialService`, `x-ui.*`, layout), Reportes, Administración e Inicio, revisión de PR | RF-13; **todas** las migraciones de la fase (F3-1, F3-2, F3-3); ajustes de Reportes e Inicio que pide RF-13 | [jhon.md](./jhon.md) |
| **Juan José** | Equipos y QR, Movimientos y Levantamientos (antes Anuar), Importación (antes Juan Camilo) | RF-05, RF-06, RF-16, RF-17, RF-31, RF-42 | [juan-jose.md](./juan-jose.md) |

Carpetas de cada uno (la regla de no escribir fuera de las propias sigue igual, [normas-de-trabajo.md §3](./normas-de-trabajo.md#3-carpetas-por-bloque)):

| Persona | Escribe en |
|---|---|
| Juan José | `app/Livewire/{Equipos,Qr,Movimientos,Levantamientos,Importacion}`, sus vistas en `resources/views/livewire/…` y sus pruebas en `tests/Feature/…` |
| Jhon | `database/migrations`, `database/seeders`, `app/Models`, `app/Services`, `resources/views/components`, `app/Livewire/{Reportes,Admin}`, `resources/views/dashboard.blade.php` y lo compartido nuevo (por ejemplo, un componente para registrar eventos configurables) |

---

## 3. Calendario

| Día | Jhon | Juan José | Hito |
|---|---|---|---|
| Antes | `v2.0` en `main`; versión 4.2 en el `.docx`. | — | Fase 2 publicada. |
| 1 | **F3-1** puestos de trabajo (`activo`, único por piso) y decisión sobre `equipos.puesto_trabajo_id`. | **RF-17** destino del componente retirado (sin migración). | F3-1 en `develop`. |
| 2 | **F3-2** `fotos_equipo` + modelo `FotoEquipo`. | **RF-16** configuración en una fecha (sin migración). | F3-2 en `develop`. |
| 3 | **F3-3** `tipos_evento` (diseño, migración y seeder de los 8 tipos del sistema). | **RF-31** pendientes de firma completos (sin migración). | — |
| 4 | F3-3: `HistorialService` valida contra `tipos_evento`; Pest completo en verde. | **RF-05** pantalla de puestos y asignar equipos a un puesto (usa F3-1). | F3-3 en `develop`. |
| 5 | Etiquetas de tipo de evento leídas de `tipos_evento` en Reportes, Inicio y el PDF de la hoja de vida. | **RF-05** traslado del puesto completo. | Demo: trasladar un puesto. |
| 6 | **RF-13** pantalla de tipos de evento en Administración. | **RF-06** fotos del equipo (usa F3-2). | — |
| 7 | **RF-13** componente compartido para registrar un evento de un tipo configurable. | **RF-42** cargas posteriores, parte 1 (reconocer equipos existentes). | Demo: un tipo de evento nuevo creado sin código. |
| 8 | Revisión de PR y correcciones. | **RF-42** parte 2 (actualizar con historial) y botón «Registrar otro evento» en la hoja de vida (usa el componente del día 7). | Datos recargados sin duplicados. |
| 9 | No se agrega nada más; pruebas cruzadas. | Pruebas cruzadas. | Lista de errores priorizada. |
| 10 | Correcciones, manual, demo. | Correcciones, manual de usuario de lo nuevo. | `v3.0` en `main`. |

**Pruebas cruzadas del día 9:** Juan José prueba RF-13 (crear un tipo, registrarlo desde una hoja de vida, verlo en reportes y en el PDF); Jhon prueba RF-05, RF-06, RF-16, RF-17, RF-31 y RF-42 sobre `migrate:fresh --seed`.

---

## 4. Dependencias y riesgos

```
Jhon
 ├─ F3-1 puestos (día 1) ───────────────► Juan José: RF-05 puestos (días 4–5)
 ├─ F3-2 fotos_equipo (día 2) ──────────► Juan José: RF-06 fotos (día 6)
 └─ F3-3 tipos_evento + HistorialService (días 3–4)
        ├─► Jhon: etiquetas en Reportes/Inicio/PDF (día 5)
        ├─► Jhon: RF-13 pantalla de Administración (día 6)
        └─► Jhon: componente «registrar evento» (día 7) ──► Juan José: botón en la hoja de vida (día 8)

Juan José, sin depender de nadie: RF-17 (día 1) · RF-16 (día 2) · RF-31 (día 3) · RF-42 (días 7–8)
```

- **Riesgo principal — F3-3 toca la columna `eventos.tipo`,** que usan todos los bloques. Mitigación: la migración conserva **exactamente** las mismas claves de texto (el código que escribe `'traslado_responsable'` sigue funcionando), siembra los 8 tipos del sistema antes de poner la llave foránea, y no se fusiona sin la suite completa en verde. Juan José no abre PR que toquen eventos el día 4.
- **Riesgo de datos — RF-42 sobre equipos existentes:** una carga posterior podría cambiar la ubicación o el responsable sin el formato firmado. Regla de la fase: la carga posterior **solo actualiza datos de la ficha**; las diferencias de ubicación o de responsable se listan como «requiere traslado» y se hacen con el traslado normal (RF-20).
- **Riesgo de privacidad — RF-06:** las fotos son del equipo, no de las personas. Se guardan en el disco privado (como los documentos firmados), con límite de tamaño y solo imágenes.
- **Riesgo de calendario:** si F3-1 o F3-2 se atrasan, Juan José adelanta RF-42 (no depende de nada) y deja RF-05 o RF-06 para después.

---

## 5. Reglas

Las de [normas-de-trabajo.md](./normas-de-trabajo.md), más estas para la fase:

- **Ramas `feature/<bloque>-<tarea>` siempre desde `develop`**, PR obligatorio y nada se fusiona sin `./vendor/bin/pest` en verde.
- **Español** en commits, comentarios y PR; solo el prefijo de Conventional Commits va en inglés (`feat:`, `fix:`, `docs:`…).
- **Solo el líder crea migraciones.** Juan José no agrega ni modifica ninguna: si falta un campo, lo pide y Jhon lo crea el mismo día. La migración en dos tiempos de la Fase 2 está **cerrada**: no se reabre.
- **Todo cambio sobre un equipo pasa por `HistorialService::registrar()`** (o `ValoracionActivo::registrar()` para la valoración), y los eventos son **inmutables**: subir o retirar una foto, asignar un puesto, actualizar datos desde una carga o mover un componente a otro equipo dejan su evento; una corrección es una anulación o aclaración.
- **Nombres oficiales:** no se inventan dependencias, sedes, pisos ni cargos; si falta uno, se le pide a Jhon.
- **Nada de datos personales de más (RNF-19):** ni en fotos, ni en cargas, ni en pruebas. Las plantas reales y el inventario real nunca se usan en pruebas ni se suben al repositorio.
- **Trabajar sobre `migrate:fresh --seed`:** lo nuevo tiene que funcionar desde cero, sin datos que solo existan en una máquina.

---

## 6. Después de la Fase 3

- **Fase 4:** firma en pantalla (RF-32).
- **Fuera del alcance:** mantenimiento preventivo y correctivo (RN-08).
