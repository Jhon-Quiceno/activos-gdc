# Normas de trabajo

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Equipos

> Reglas de la sección 5 del [Plan de dos semanas](./plan-2-semanas.md) ("Reglas para no estorbarse"), explicadas para que cualquiera del equipo las aplique sin ambigüedad.

Cinco personas tocando el mismo repositorio en paralelo solo funciona si cada una sabe exactamente dónde puede escribir y qué no debe tocar. Estas normas existen para eso: evitar choques de migraciones, archivos pisados y eventos registrados de forma inconsistente.

---

## 1. Ramas

- Toda tarea se trabaja en una rama `feature/<bloque>-<tarea>` (por ejemplo, `feature/nucleo1-busqueda-equipos` o `feature/qr-etiqueta-individual`).
- Esa rama se crea **siempre desde `develop`**, nunca desde `main`. `main` solo recibe código ya integrado y probado.
- Los Pull Request deben ser **pequeños**: uno por tarea, no uno gigante al final de la semana.
- Se espera **al menos un PR cada dos días** por persona. Si una tarea es más grande, se parte en PR más chicos en vez de acumular cambios.

## 2. Migraciones

- **Solo Jhon (el líder) crea migraciones.** Nadie más agrega, modifica o elimina una migración, aunque sea un campo pequeño.
- Si alguien necesita un campo nuevo en una tabla, lo pide en el canal del equipo describiendo qué necesita y para qué. Jhon crea la migración **ese mismo día** para no bloquear a nadie.
- Esta regla evita el problema más común de trabajar varias personas contra el mismo modelo de datos: migraciones que chocan o que pisan cambios de otro.

## 3. Carpetas por bloque

Cada bloque (persona) escribe exclusivamente en sus propias carpetas:

- `app/Livewire/<Bloque>`
- `resources/views/<bloque>`
- `tests/<Bloque>`

Lo que es compartido entre bloques —componentes Blade reutilizables (tabla, formulario, tarjeta, modal, badges) y `HistorialService`— **lo cambia únicamente Jhon**. Si un bloque necesita un ajuste en una pieza compartida, se solicita igual que una migración: se avisa y el líder lo resuelve el mismo día.

Esto mantiene cada bloque aislado: nadie necesita mirar el código de otro para avanzar, y los conflictos de Git se minimizan porque las carpetas no se cruzan.

## 4. Eventos solo vía `HistorialService`

**Todo cambio sobre un equipo pasa por `HistorialService::registrar()`.** Nunca se inserta un evento directamente en la base de datos ni se crea a mano un registro de `Evento`.

Esto garantiza que:

- El autor del evento siempre es el usuario con sesión iniciada (RF-11), nunca se puede falsear.
- Los eventos quedan con la estructura correcta sin que cada bloque reinvente la lógica.
- Se respeta la inmutabilidad de eventos (RF-12, RN-06): una corrección es un evento de anulación o aclaración, jamás una edición directa.

Si tu bloque registra traslados, cambios de componente, diagnósticos, bajas o cualquier otro hecho sobre un equipo, pasa siempre por ese servicio.

## 5. Datos: siempre `migrate:fresh --seed`

Nadie debe depender de datos que solo existen en su máquina. El flujo de trabajo diario es:

```bash
docker compose exec -u sail laravel.test php artisan migrate:fresh --seed
```

Esto reconstruye la base de datos desde cero con los seeders oficiales (sedes, pisos, tipos de equipo, marcas, sistemas operativos, motivos de baja, y los equipos falsos de las factories). Si tu funcionalidad solo funciona con datos que tú insertaste manualmente, no está realmente lista.

## 6. Seguimiento diario

- Mensaje diario en el canal del equipo **antes de las 9:00 a.m.** con tres puntos: qué hiciste ayer, qué vas a hacer hoy, y si tienes algún bloqueo.
- Si hay un bloqueo real, se hace una llamada corta de 15 minutos para resolverlo; no es necesario para el reporte diario en sí.

Esto reemplaza reuniones largas: el líder puede ver el estado de todo el equipo leyendo cinco mensajes cortos.

## 7. Qué significa "terminado"

Una tarea no está terminada solo porque el código "funciona en mi máquina". Se considera **terminado** cuando cumple las cinco condiciones:

1. **Funciona desde cero en Docker** — es decir, después de un `migrate:fresh --seed` limpio, sin pasos manuales adicionales.
2. **Sigue el diseño** — usa los componentes Blade compartidos y el layout general, no una pantalla improvisada.
3. **Registra el evento con su autor** — si la tarea genera un evento, pasa por `HistorialService` y el autor queda correctamente asignado al usuario que lo hizo.
4. **Tiene una prueba de la regla principal** — al menos un test (Pest) que verifique el comportamiento central de la tarea (por ejemplo, que el serial sea único, o que un evento sea inmutable).
5. **El líder aprobó el PR** — Jhon revisa y aprueba antes de hacer merge a `develop`.

## 8. Antes de abrir un Pull Request

Siempre, sin excepción, correr las pruebas y verificar que pasan en verde antes de abrir el PR:

```bash
docker compose exec -u sail laravel.test ./vendor/bin/pest
```

Un PR con pruebas rotas no se revisa; corrígelo antes de pedir revisión.

---

**Ver también:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Plan de dos semanas](./plan-2-semanas.md)
