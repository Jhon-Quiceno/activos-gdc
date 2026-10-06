# Sistema de Hoja de Vida de Equipos

**Gobernación de Córdoba · Dirección TIC**

Aplicación web para llevar el ciclo de vida completo de cada equipo tecnológico de la entidad: registro, componentes, traslados, diagnóstico y baja, con generación de formatos PDF firmados y código QR por equipo para consultar la hoja de vida escaneando una etiqueta física.

El sistema reemplaza hojas de cálculo y procesos en papel dispersos por un inventario maestro único: cada equipo (computadores, monitores, impresoras, UPS, switches, servidores, etc.) queda identificado por su serial y código de activo, con un historial inmutable de todo lo que le ha pasado y quién lo registró.

Para el detalle funcional completo, ver:

- [Análisis de requerimientos](docs/analisis-requerimientos.md) — documento de referencia permanente: 35 requerimientos esenciales, modelo de dominio, reglas de negocio y casos de uso.
- [Plan de trabajo de dos semanas](docs/plan-2-semanas.md) — plan de la Fase 1 y reparto de tareas del equipo.

## Stack

- **Backend:** Laravel 13
- **Frontend:** Livewire (Breeze), Tailwind CSS
- **Base de datos:** MySQL 8.4
- **Entorno:** Docker / Sail (incluye Mailpit para correo de pruebas)
- **Gestor de paquetes JS:** pnpm
- **Pruebas:** Pest

Paquetes adicionales: `barryvdh/laravel-dompdf` (formatos y reportes en PDF), `maatwebsite/excel` (importación/exportación de inventario), `simplesoftwareio/simple-qrcode` (códigos QR), `spatie/laravel-activitylog` (auditoría).

## Prerrequisitos

Solo necesitas **Docker Desktop** instalado y corriendo. No se requiere PHP ni Node instalados localmente — todo corre dentro de los contenedores.

> **Nota sobre comandos en este proyecto:** no se usa `./vendor/bin/sail` porque ese script no detecta Git Bash/MinGW nativo en Windows (solo reconoce macOS, Linux o WSL2). Todos los comandos de este README usan `docker compose exec -u sail laravel.test ...`. Si usas **WSL2 real** (una distribución Linux dentro de Windows), sí puedes usar `./vendor/bin/sail` como alternativa más corta; en Git Bash sobre Windows, usa siempre `docker compose`.
>
> **Importante — siempre usa `-u sail`:** sin ese flag, `docker compose exec` corre como `root` y los archivos que toque (`storage/`, `bootstrap/cache/`) quedan con dueño `root`. El servidor web dentro del contenedor corre como el usuario `sail`, así que si esas carpetas quedan de `root` el sitio se cae con un error 500 (`tempnam(): file created in the system's temporary directory`) al intentar compilar una vista nueva. Si eso te pasa, corrígelo con: `docker compose exec -u sail laravel.test chown -R sail:sail storage bootstrap/cache`.

## Puesta en marcha desde cero

1. **Clonar el repositorio:**

   ```bash
   git clone https://github.com/Jhon-Quiceno/activos-gdc.git
   cd activos-gdc
   ```

2. **Copiar el archivo de entorno:**

   ```bash
   cp .env.example .env
   ```

3. **Levantar los contenedores:**

   ```bash
   docker compose up -d
   ```

   Esto levanta la aplicación (`laravel.test`), MySQL 8.4 y Mailpit.

   > En Windows con Git Bash, si algún comando de Docker falla por rutas raras, antepón `export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL="*"` antes del comando.

4. **Instalar dependencias de PHP** (normalmente ya vienen instaladas en la imagen; solo si hace falta):

   ```bash
   docker compose exec -u sail laravel.test composer install
   ```

5. **Generar la clave de la aplicación:**

   ```bash
   docker compose exec -u sail laravel.test php artisan key:generate
   ```

6. **Ejecutar migraciones y seeders:**

   ```bash
   docker compose exec -u sail laravel.test php artisan migrate:fresh --seed
   ```

7. **Instalar dependencias de JavaScript:**

   ```bash
   docker compose exec -u sail laravel.test pnpm install
   ```

8. **Compilar assets:**

   ```bash
   docker compose exec -u sail laravel.test pnpm run build
   ```

   Para desarrollo con recarga en caliente, usa en su lugar:

   ```bash
   docker compose exec -u sail laravel.test pnpm run dev
   ```

9. **Acceder a la aplicación:**

   - Aplicación: [http://localhost](http://localhost)
   - Mailpit (correo de pruebas): [http://localhost:8025](http://localhost:8025)

   El registro público de usuarios está desactivado: los usuarios los crea un Administrador desde `/admin/usuarios`.

   **Usuarios de prueba** (creados por el seeder, contraseña `password` para todos):

   | Correo | Perfil |
   |---|---|
   | `admin@gobernaciondecordoba.gov.co` | Administrador |
   | `soporte.tic@gobernaciondecordoba.gov.co` | Administrador |
   | `inventario@gobernaciondecordoba.gov.co` | Usuario |
   | `consulta@gobernaciondecordoba.gov.co` | Usuario |

   El seeder también carga ~100 equipos de prueba (con responsables, componentes e historial) para que cada pantalla tenga datos reales desde el primer `migrate:fresh --seed`.

## Pantallas ya construidas

Todo el diseño del prototipo de Claude Design ya está implementado y funcionando contra datos reales — lo que sigue es que cada bloque termine la lógica de negocio que le falta (ver `docs/<tu-nombre>.md` y `docs/normas-de-trabajo.md`). Mapa de rutas:

| Bloque | Pantalla | Ruta |
|---|---|---|
| — | Inicio de sesión | `/login` |
| — | Panel principal (KPIs, calidad del inventario, actividad reciente) | `/dashboard` |
| Equipos | Listado (búsqueda, clic en una fila abre su hoja de vida) | `/equipos` |
| Equipos | Hoja de vida del equipo | `/equipos/{equipo}` |
| Equipos | Registrar equipo (formulario condicional por tipo) | `/equipos/crear` |
| Movimientos | Traslados (listado) / Traslado o cambio de responsable | `/movimientos/traslados`, `/movimientos/traslados/{equipo}` |
| Movimientos | Bajas (listado) / Registrar baja | `/movimientos/bajas`, `/movimientos/bajas/{equipo}` |
| Movimientos | Diagnóstico | `/movimientos/diagnostico/{equipo}` |
| Movimientos | Cambio de componente | `/movimientos/componente/{equipo}` |
| Movimientos | Pendientes de firma | `/movimientos/pendientes` |
| Movimientos | Formato de entrega / Formato de baja (documento imprimible) | `/movimientos/{evento}/formato-entrega`, `.../formato-baja` |
| Importación | Importar inventario (wizard de 4 pasos) | `/importacion` |
| Reportes | Catálogo de 15 reportes | `/reportes` |
| Administración | Usuarios | `/admin/usuarios` |
| Administración | Listas administrables (sedes, dependencias, marcas, etc.) | `/admin/listas` |
| QR | Verificar en sitio (vista móvil) | `/qr/verificar` |

Sistema de diseño compartido (paleta, tipografía, componentes `x-ui.*`) en `tailwind.config.js` y `resources/views/components/`. Antes de inventar un estilo nuevo, revisá si ya existe un componente para eso.

## Correr las pruebas

```bash
docker compose exec -u sail laravel.test ./vendor/bin/pest
```

Las pruebas deben pasar en verde antes de abrir cualquier Pull Request (ver [normas de trabajo](docs/normas-de-trabajo.md)).

## Estructura de carpetas

```
app/
  Http/          Controladores y middleware
  Livewire/      Componentes Livewire, organizados por bloque funcional
  Models/        Modelos Eloquent
  Providers/     Service providers
  View/          Composers y lógica de vistas
resources/
  views/         Plantillas Blade, organizadas por bloque funcional
docs/            Documentación funcional del proyecto (ver abajo)
odd/             Seguimiento de tareas de desarrollo (Organic Driven Development)
database/        Migraciones, seeders y factories
tests/           Pruebas Pest, organizadas por bloque funcional
```

La carpeta `docs/` contiene:

- [`analisis-requerimientos.md`](docs/analisis-requerimientos.md) — requerimientos, modelo de dominio y reglas de negocio.
- [`plan-2-semanas.md`](docs/plan-2-semanas.md) — plan de trabajo de la Fase 1.
- [`normas-de-trabajo.md`](docs/normas-de-trabajo.md) — reglas de ramas, migraciones, carpetas y eventos para trabajar en equipo sin pisarse.
- `juan-jose.md`, `anuar.md`, `juan-camilo.md`, `alex.md`, `manuel.md` — tareas específicas de cada integrante del equipo.

## Ramas y flujo de trabajo

El repositorio tiene dos ramas protegidas:

- **`main`** — versión estable, solo recibe código ya integrado y probado.
- **`develop`** — rama de integración diaria del equipo.

Todo el mundo trabaja en ramas `feature/<bloque>-<tarea>` creadas siempre desde `develop`, nunca desde `main`, con Pull Requests pequeños y frecuentes. El detalle completo está en [docs/normas-de-trabajo.md](docs/normas-de-trabajo.md).

---

Gobernación de Córdoba, Dirección TIC, 2026.
