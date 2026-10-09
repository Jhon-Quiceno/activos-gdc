# Normas de trabajo

**Gobernación de Córdoba · Dirección TIC**
Sistema de Hoja de Vida de Equipos

> Reglas de la sección 5 del [Plan de dos semanas](./plan-2-semanas.md) ("Reglas para no estorbarse"), explicadas para que cualquiera del equipo las aplique sin ambigüedad.

Cinco personas tocando el mismo repositorio en paralelo solo funciona si cada una sabe exactamente dónde puede escribir y qué no debe tocar. Estas normas existen para eso: evitar choques de migraciones, archivos pisados y eventos registrados de forma inconsistente.

---

## 1. Ramas

- Toda tarea se trabaja en una rama `feature/<bloque>-<tarea>` (por ejemplo, `feature/equipos-busqueda` o `feature/qr-etiqueta-individual`).
- Esa rama se crea **siempre desde `develop`**, nunca desde `main`. `main` solo recibe código ya integrado y probado.
- Los Pull Request deben ser **pequeños**: uno por tarea, no uno gigante al final de la semana.
- Se espera **al menos un PR cada dos días** por persona. Si una tarea es más grande, se parte en PR más chicos en vez de acumular cambios.

### Prefijo de rama por bloque

| Persona | Bloque | Prefijo de rama |
|---|---|---|
| Juan José | Equipos y hoja de vida | `feature/equipos-<tarea>` |
| Anuar | Movimientos y formatos | `feature/movimientos-<tarea>` |
| Juan Camilo | Carga del inventario | `feature/importacion-<tarea>` |
| Alex | Reportes | `feature/reportes-<tarea>` |
| Manuel | Administración | `feature/admin-<tarea>` |
| Manuel | Etiquetas QR | `feature/qr-<tarea>` |

### Flujo paso a paso (copiar y pegar, cambiando el nombre de la rama)

```bash
# 1. Pararse siempre sobre develop actualizado, nunca sobre main
git checkout develop
git pull origin develop

# 2. Crear la rama de la tarea DESDE develop
git checkout -b feature/equipos-busqueda

# 3. Trabajar, commitear seguido con mensajes claros
git add .
git commit -m "feat: busqueda de equipos por serial y codigo de activo"

# 4. Subir la rama (la primera vez con -u, después alcanza con "git push")
git push -u origin feature/equipos-busqueda

# 5. Abrir el Pull Request en GitHub apuntando a develop (NUNCA a main)
#    y esperar la revisión de Jhon antes de hacer merge.
```

Si la tarea tarda más de dos días, no se acumula todo en una sola rama: se abre un PR más chico con lo que ya está listo y se continúa en una rama nueva para lo que falta.

### Mantenerte al día con `develop`

Jhon integra `develop` seguido (migraciones nuevas, componentes compartidos, fixes). Si no actualizás tu rama, trabajás sobre una base vieja y el día que abras el PR vas a tener conflictos grandes. Usamos **rebase** para esto (no `git merge`), por dos razones: mantiene el historial de commits lineal y fácil de leer, y es la misma estrategia que ya usa este repo para integrar PRs a `main` (GitHub tiene deshabilitados los merge commits).

**Antes de empezar a trabajar cada día**, actualizá tu rama contra `develop`:

```bash
# 1. Traé lo nuevo de GitHub (no modifica tu rama todavía)
git fetch origin

# 2. Pará sobre tu rama de trabajo
git checkout feature/equipos-busqueda

# 3. Reacomodá tus commits encima de lo último de develop
git rebase origin/develop
```

Si no hay conflictos, listo: seguís trabajando. Si tu rama ya estaba pusheada a GitHub, el rebase reescribe tus commits (cambian de hash), así que el push normal va a fallar — usá **force-with-lease**, nunca `--force` a secas:

```bash
git push --force-with-lease origin feature/equipos-busqueda
```

`--force-with-lease` se niega a sobrescribir si alguien más subió algo a esa rama que vos no tenés — `--force` no se fija en eso y puede borrar trabajo ajeno. Como cada rama `feature/*` es de una sola persona, en la práctica siempre va a andar, pero el hábito evita un desastre el día que no sea así.

**Si el rebase encuentra conflictos**, Git para y te dice qué archivos chocan:

```bash
# Git te avisa algo como:
# CONFLICT (content): Merge conflict in app/Livewire/Equipos/Index.php

# 1. Abrí el archivo, vas a ver marcas como estas:
#    <<<<<<< HEAD
#    (tu código)
#    =======
#    (el código de develop)
#    >>>>>>> origin/develop
# Dejá el código correcto (puede ser el tuyo, el de develop, o una mezcla de los dos) y borrá las tres marcas.

# 2. Marcá el archivo como resuelto
git add app/Livewire/Equipos/Index.php

# 3. Seguí con el rebase (repetí 1-3 si hay más de un commit con conflictos)
git rebase --continue
```

**Si te vas por las ramas y querés cancelar todo y volver a como estaba antes de empezar el rebase:**

```bash
git rebase --abort
```

Esto es 100% seguro, te deja exactamente donde estabas antes del paso 3. Úsalo sin miedo si te perdés a mitad del rebase.

### Problemas comunes

| Mensaje / síntoma | Qué hacer |
|---|---|
| `Your branch and 'origin/develop' have diverged` | Tu rama está desactualizada: hacé el rebase de arriba. |
| `CONFLICT (content): Merge conflict in <archivo>` | Resolvé el archivo a mano (ver arriba), `git add`, `git rebase --continue`. |
| `! [rejected] ... (non-fast-forward)` al hacer `git push` | Acabás de rebasar: usá `git push --force-with-lease`, no un push normal. |
| Te perdiste a mitad de un rebase | `git rebase --abort` y arrancá de nuevo con calma. |
| Borraste algo que no querías o quedó todo raro | **No sigas tocando nada.** `git status` y `git log --oneline -10`, pegalo en el grupo y pedí ayuda antes de forzar cualquier cosa. |

**Reglas de oro:**
- Rebase y `--force-with-lease` **solo en tu propia rama `feature/*`**. Nunca en `develop` ni en `main`.
- Nunca una rama que esté usando otra persona al mismo tiempo (acá no debería pasar: cada rama es de un solo dueño).
- Ante la duda, preguntá antes de forzar un push. Un push mal hecho se arregla; code perdido sin backup, no siempre.

## 2. Migraciones

- **Solo Jhon (el líder) crea migraciones.** Nadie más agrega, modifica o elimina una migración, aunque sea un campo pequeño.
- Si alguien necesita un campo nuevo en una tabla, lo pide en el canal del equipo describiendo qué necesita y para qué. Jhon crea la migración **ese mismo día** para no bloquear a nadie.
- Esta regla evita el problema más común de trabajar varias personas contra el mismo modelo de datos: migraciones que chocan o que pisan cambios de otro.

## 3. Carpetas por bloque

Cada bloque (persona) escribe exclusivamente en sus propias carpetas. Estos son los nombres reales ya creados en el proyecto (no son un ejemplo, son las carpetas que existen hoy):

| Bloque | Carpeta Livewire | Carpeta de vistas | Carpeta de pruebas | Ruta |
|---|---|---|---|---|
| Equipos (Juan José) | `app/Livewire/Equipos` | `resources/views/livewire/equipos` | `tests/Feature/Equipos` | `/equipos` |
| Movimientos (Anuar) | `app/Livewire/Movimientos` | `resources/views/livewire/movimientos` | `tests/Feature/Movimientos` | `/movimientos` |
| Importación (Juan Camilo) | `app/Livewire/Importacion` | `resources/views/livewire/importacion` | `tests/Feature/Importacion` | `/importacion` |
| Reportes (Alex) | `app/Livewire/Reportes` | `resources/views/livewire/reportes` | `tests/Feature/Reportes` | `/reportes` |
| Administración (Manuel) | `app/Livewire/Admin` | `resources/views/livewire/admin` | `tests/Feature/Admin` | `/admin` |
| Etiquetas QR (Manuel) | `app/Livewire/Qr` | `resources/views/livewire/qr` | `tests/Feature/Qr` | `/qr` |

Cada carpeta de pruebas va dentro de `tests/Feature/` (no suelta en `tests/`) porque así está configurado `tests/Pest.php` para que los tests tomen automáticamente la clase base correcta.

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

## 9. Idioma: commits, comentarios y Pull Requests

Todo el texto que escribimos alrededor del código va **en español**: mensajes de commit, descripciones y revisiones de Pull Request, comentarios en el código, y la documentación de `docs/`. La única excepción es el prefijo del [Conventional Commit](https://www.conventionalcommits.org/es/), que va siempre en inglés porque es el que entienden las herramientas (changelog, linters de commits, etc.).

- **Commits:** `<prefijo en inglés>: <descripción en español>`.
  - `feat: agregar busqueda de equipos por serial y codigo de activo`
  - `fix: corregir el boton Dar de baja que se partia en dos lineas`
  - `docs: actualizar el README con los usuarios de prueba`
  - `refactor: extraer el calculo de dias pendientes a un metodo`
  - `test: cubrir la inmutabilidad del evento de baja`
  - Prefijos válidos: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `style`, `perf`.
- **Pull Requests:** título y descripción en español (incluido el plan de pruebas).
- **Comentarios en el código:** en español, y solo cuando explican un *por qué* no obvio (una regla de negocio, una decisión rara, un bug que se evitó) — no para describir qué hace el código línea por línea.
- **Identificadores del código no cambian** — esta norma es solo sobre el texto alrededor del código (commits, comentarios, PR), no sobre nombres de clases, métodos, variables, rutas o vistas. Seguí el patrón que ya existe en el proyecto: los nombres de dominio del negocio van en español, igual que en los documentos de requerimientos (`Equipo`, `HistorialService`, la ruta `movimientos.pendientes`), mientras que los términos genéricos de programación van en inglés (`render`, `boot`, `HasMany`, convenciones propias de Laravel). Si tenés dudas sobre un nombre puntual, fijate cómo está nombrado algo similar ya existente.

Lo que el usuario final ve (textos de pantalla, mensajes de validación, correos) ya está en español por el idioma de la app (`laravel-lang`, RNF-02) y no se ve afectado por esta norma — esto es sobre lo que escribimos nosotros como equipo, no sobre la UI.

---

**Ver también:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Plan de dos semanas](./plan-2-semanas.md)
