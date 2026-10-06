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
- **Identificadores del código** (nombres de clases, métodos, variables, rutas, vistas) siguen **en inglés**, como ya está en todo el proyecto (`Equipo`, `HistorialService`, `movimientos.pendientes`); esto no cambia, solo aplica al texto alrededor del código.

Lo que el usuario final ve (textos de pantalla, mensajes de validación, correos) ya está en español por el idioma de la app (`laravel-lang`, RNF-02) y no se ve afectado por esta norma — esto es sobre lo que escribimos nosotros como equipo, no sobre la UI.

---

**Ver también:** [Análisis de requerimientos](./analisis-requerimientos.md) · [Plan de dos semanas](./plan-2-semanas.md)
