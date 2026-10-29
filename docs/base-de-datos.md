# Base de datos — Modelo v4

**Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba · Dirección TIC**
Versión 4.2 · 17 de octubre de 2026 · sin mantenimiento · migraciones aplicadas (primer tiempo) · Jhon (líder) · Rama `docs/reestructuracion-fuentes-oficiales`

> Este documento es la referencia del modelo de datos. Acompaña al [Análisis de requerimientos v4](./analisis-requerimientos.md).
> Los dos archivos fuente están en [database/dbml/](../database/dbml/):
>
> - [hoja-vida-activos.dbml](../database/dbml/hoja-vida-activos.dbml) — para pegar en dbdiagram.io.
> - [hoja-vida-activos.sql](../database/dbml/hoja-vida-activos.sql) — el mismo modelo en SQL MySQL 8.
>
> El DBML completo también está al final de este documento (sección 9), listo para copiar.

---

## 1. Cómo abrirlo en dbdiagram.io

1. Entrar a <https://dbdiagram.io/d> (crea un diagrama nuevo; con la cuenta del equipo se puede guardar y compartir).
2. Borrar el ejemplo del panel izquierdo.
3. Pegar el contenido de la **sección 9** (o del archivo `.dbml`). El diagrama se dibuja solo.
4. Alternativa: menú **Import → From MySQL** y pegar el archivo `.sql`.
5. Para exportar: **Export → PDF / PNG / SQL**.

Para revisarlo en el diagrama, conviene agrupar las tablas por los siete bloques de la sección 3.

---

## 2. Comparación: modelo actual (v3) frente al modelo nuevo (v4)

El **modelo actual (v3)** es el que corre hoy en `develop`: 22 tablas propias, más las de Laravel y la de `spatie/laravel-activitylog`. Salió de las 53 columnas del inventario 2026 (Microsoft Forms). Por decisión del profesor responsable, ese Excel **ya no define la estructura**: solo se usará después para cargar datos. El **modelo nuevo (v4)** se construye con las fuentes oficiales de la Gobernación.

> **El mantenimiento preventivo y correctivo sigue fuera del alcance**, igual que en v3 (RN-08). El Programa Anual de Mantenimiento (PAMP) se usa solo como fuente oficial de sedes, pisos y de qué dependencia funciona en cada piso.

### 2.1 Resumen en cifras

| | Modelo actual (v3) | Modelo nuevo (v4) |
|---|---|---|
| Fuente del diseño | Inventario 2026 (Excel de Forms) | GT-M4, FOR.01, Decretos 607/608/609, plantas de personal, PAMP (solo ubicaciones) |
| Tablas propias | 22 | 31 (9 nuevas, 17 modificadas, 5 iguales, 0 eliminadas) |
| Relaciones (llaves foráneas) | 31 | 57 |
| Dependencias | 15, lista plana, nombres inventados | 78 en árbol, nombres del Decreto 609, con tabla de equivalencias |
| Sedes | 11 inventadas («Sede Alcaldía Mayor», «Edificio San Jerónimo»…) | 11 reales con dirección y municipio (10 confirmadas en el PAMP + Suricentro por confirmar) |
| Pisos | 8 globales (1 a 8 para todas las sedes) | Propios de cada sede |
| Cargo del responsable | Texto libre | Catálogo oficial del Decreto 607 (21 empleos con código y nivel) + grado |
| Tipos de equipo | 12 | 24, clasificados en hardware o soporte (GT-M4) |
| Valoración del activo (GT-M4) | No existe | Datos personales, criticidad, propietario, custodio, CID 1 a 4 e importancia, con historial |
| Datos de adquisición y garantía | No existen | Forma de ingreso, fecha, valor, proveedor, contrato, factura, garantía, vida útil |
| Levantamiento con acta firmada | No existe | Por dependencia y proceso (GT-M4 4.1) |
| Tipos de evento | 7 | 8 (+ `valoracion`) |
| Mantenimiento | Fuera del alcance | **Fuera del alcance** (sin cambio) |

### 2.2 Tabla por tabla

| Tabla | Estado en v4 | Qué cambia frente a v3 |
|---|---|---|
| `sedes` | Modificada | + `direccion`, `barrio`, `municipio_id`, `tipo`, `tenencia`, `direccion_reservada`, `activa`. |
| `pisos` | Modificada | + `sede_id` y `nombre`. El único pasa de `numero` a `(sede_id, numero)`: cada sede tiene sus pisos. |
| `dependencias` | Modificada | + `padre_id` (árbol), `tipo`, `nombre_corto`, `sigla`, `proceso_id`, `planta`, `acto_administrativo`, `activa`. El único pasa de `nombre` a `(padre_id, nombre)`. |
| `tipos_equipo` | Modificada | `familia` pasa de 7 a 10 valores (+ `seguridad_electronica`, `climatizacion`, `telefonia`). + `tipo_activo`, `usa_configuracion_computo`, `vida_util_meses`, `activo`. |
| `marcas` | Igual | — |
| `sistemas_operativos` | Modificada | + `fin_soporte` (reporte de obsolescencia). |
| `motivos_baja` | Igual | Solo cambian los valores del seeder. |
| `tipos_componente` | Modificada | + `obligatorio_en_computo`. |
| `puestos_trabajo` | Modificada | Se quita `sede_id`: la da el piso (en el segundo tiempo). |
| `personas` | Modificada | `nombre` → `nombres` + `apellidos`; `cedula` (opcional) → `tipo_documento` + `numero_documento` (obligatorio y único); `cargo` (texto) → `cargo_id` + `grado` + `encargo`; `tipo_vinculacion` obligatorio y + `pasante`; + `correo_institucional`, `jefe_inmediato_id`, `planta`, `tipo_provision`, `numero_contrato`, `fecha_fin_vinculacion`; `tipo_documento`, `nombres`, `apellidos` y `numero_documento` nuevos. |
| `users` | Modificada | + `persona_id`. |
| `importaciones` | Modificada | + `tipo` (equipos, personas, valoraciones). |
| `equipos` | Modificada | + `numero_activo`, `descripcion`, `proceso_id`, `forma_ingreso`, `fecha_adquisicion`, `valor_adquisicion`, `proveedor_id`, `numero_contrato`, `numero_factura`, `fecha_fin_garantia`, `vida_util_meses`. `modelo` (texto) → `modelo_id`; `propietario_tercero` (texto) → `proveedor_tercero_id`; `figura_tercero` + `arrendamiento`; `estado_funcionamiento` de texto a lista (`bueno`, `regular`, `malo`, `no_funcional`). |
| `configuraciones_computo` | Modificada | `licencia` (del sistema operativo) se conserva; + `ofimatica`, `licencia_ofimatica`, `dominio`, `direccion_ip`, `direccion_mac`. |
| `componentes` | Modificada | + `modelo`; `estado` de texto a la misma lista del equipo. |
| `asignaciones` | Modificada | Se quita `sede_id` (la da el piso); + `puesto_trabajo_id`. |
| `eventos` | Modificada | `tipo` + `valoracion`; + `es_historico` (altas de carga inicial). |
| `cambios_componente` | Modificada | + `equipo_destino_id` cuando lo retirado va a otro equipo. |
| `diagnosticos` | Igual | — |
| `documentos` | Modificada | `tipo` + `acta_aceptacion_activos`. |
| `etiquetas_qr` | Igual | — |
| `auditorias` | Igual | — |
| `procesos` | **Nueva** | 17 procesos del SIG (FOR.01). |
| `dependencia_equivalencias` | **Nueva** | Variantes de texto → dependencia oficial (RF-39), incluidos los nombres anteriores al Decreto 609. |
| `municipios` | **Nueva** | 30 municipios de Córdoba (DIVIPOLA). |
| `ubicaciones_dependencia` | **Nueva** | Qué dependencia funciona en qué piso (PAMP). |
| `cargos` | **Nueva** | Nomenclatura de empleos del Decreto 607. |
| `modelos` | **Nueva** | Modelo por marca y tipo, en vez de texto libre. |
| `proveedores` | **Nueva** | Quien vende, dona o presta equipos (y dueño de los equipos de terceros). |
| `valoraciones_activo` | **Nueva** | Valoración GT-M4, histórica, una vigente por equipo. |
| `levantamientos_activos` | **Nueva** | Levantamiento por dependencia y proceso con acta de aceptación. |
| `activity_log` | Igual (paquete) | No se toca. |

### 2.3 Columnas que desaparecen (y a dónde pasan)

| Columna v3 | Pasa a |
|---|---|
| `equipos.modelo` | `equipos.modelo_id` → `modelos` |
| `equipos.propietario_tercero` | `equipos.proveedor_tercero_id` → `proveedores` |
| `personas.nombre` | `personas.nombres` + `personas.apellidos` |
| `personas.cedula` | `personas.tipo_documento` + `personas.numero_documento` |
| `personas.cargo` | `personas.cargo_id` → `cargos` + `personas.grado` |
| `asignaciones.sede_id`, `puestos_trabajo.sede_id` | Se deducen de `piso_id` → `pisos.sede_id` |

### 2.4 Lo que no cambia

El equipo sigue identificado por serial (con serial provisional, RN-02) y código de activo (índice no único con justificación, RN-03). El QR sigue usando un UUID permanente. Los eventos siguen siendo inmutables y solo se escriben con `HistorialService::registrar()`. La asignación abierta sigue siendo la ubicación y el responsable actuales. Diagnósticos, documentos, etiquetas QR, usuarios (perfiles y cambio de contraseña) y auditoría se conservan.

---

## 3. Bloques del modelo

31 tablas en 6 bloques, 29 enums y 57 llaves foráneas.

| Bloque | Tablas | Para qué |
|---|---|---|
| 1. Estructura organizacional | `procesos`, `dependencias`, `dependencia_equivalencias`, `municipios`, `sedes`, `pisos`, `ubicaciones_dependencia`, `cargos`, `personas` | Dónde está el equipo y quién responde por él, con los nombres oficiales. |
| 2. Catálogos del activo | `tipos_equipo`, `marcas`, `modelos`, `sistemas_operativos`, `tipos_componente`, `motivos_baja`, `proveedores` | Listas cerradas: nada de texto libre (RN-11). |
| 3. Equipo y hoja de vida | `equipos`, `valoraciones_activo`, `configuraciones_computo`, `componentes`, `puestos_trabajo`, `asignaciones` | La ficha y su estado actual. |
| 4. Eventos | `eventos`, `cambios_componente`, `diagnosticos`, `documentos`, `etiquetas_qr` | El historial inmutable y sus soportes. |
| 5. Levantamiento | `levantamientos_activos` | Matriz por proceso y acta de aceptación (GT-M4). |
| 6. Sistema | `users`, `auditorias`, `importaciones` | Acceso, trazabilidad y cargas desde Excel. |

```
procesos ─┬─< dependencias (árbol: padre_id) ─< ubicaciones_dependencia >─ pisos >─ sedes >─ municipios
          │        │
          │        └─< personas >─ cargos            (personas.jefe_inmediato_id → personas)
          │
          └─< equipos ─┬─< asignaciones >─ personas / pisos / dependencias / puestos_trabajo
                       ├─< valoraciones_activo (una vigente) >─ levantamientos_activos
                       ├── configuraciones_computo (1 a 1)
                       ├─< componentes
                       ├─< etiquetas_qr
                       └─< eventos ─┬── cambios_componente | diagnosticos (1 a 1)
                                    └─< documentos
```

---

## 4. Diccionario de datos

Solo se describen las columnas nuevas o con reglas. El resto está documentado en las notas del DBML.

### 4.1 `dependencias`

| Columna | Tipo | Regla |
|---|---|---|
| `padre_id` | FK → dependencias | Null solo para el Despacho del Gobernador. No se permiten ciclos. |
| `tipo` | enum | `despacho`, `secretaria`, `departamento_administrativo`, `oficina`, `direccion`, `area`. |
| `nombre` | varchar(160) | Nombre oficial del Decreto 609. Único dentro del mismo padre. |
| `proceso_id` | FK → procesos | Proceso del SIG al que pertenece. |
| `planta` | enum | `central` o `educacion`. Las áreas de la Secretaría de Educación son `educacion`. |
| `activa` | boolean | Una dependencia suprimida se desactiva, nunca se borra, porque el historial la referencia. |

`dependencia_equivalencias` guarda cada variante de texto que llega en un Excel («DIRECION DE SISTEMAS», «Sec Educación», «Dirección técnica de sistemas») y la dependencia oficial a la que corresponde. Es la tabla editable que exige RF-39.

### 4.2 `sedes`, `pisos` y `ubicaciones_dependencia`

- La sede pertenece a un municipio, y el piso a una sede (`unique(sede_id, numero)`). La asignación de un equipo guarda solo `piso_id`, porque el piso ya determina la sede, así que no hay forma de guardar un piso que no exista en esa sede.
- `direccion_reservada = true` en la Casa Refugio: su dirección no se muestra en listados, reportes ni etiquetas. Es un refugio para mujeres víctimas de violencia.
- `ubicaciones_dependencia` dice qué dependencias funcionan en cada piso. `x-ui.ubicacion-select` la usa como filtro: al elegir la sede solo muestra sus pisos y sus dependencias (y al elegir el piso, solo las de ese piso). Como el PAMP es una foto de 2026 y no ubica 11 dependencias, el enlace «Ver todas las dependencias» abre la lista completa; la validación del servidor no lo prohíbe.

### 4.3 `cargos` y `personas`

- `cargos.codigo` tiene 3 dígitos; el primero indica el nivel (0 directivo, 1 asesor, 2 profesional, 3 técnico, 4 asistencial; Decreto 607, art. 16). Los contratistas usan un cargo sin código («Contratista de prestación de servicios»).
- `personas.grado` es el grado salarial (01 a 10). `encargo = true` cuando el cargo se ejerce en encargo (las «(E)» de la planta).
- `personas.dependencia_id` es la **ubicación real** de la persona (columna «REAL UBICACIÓN» de la planta), no la dependencia nominal del cargo.
- `fecha_fin_vinculacion` (fin de contrato o retiro) alimenta el paz y salvo (RF-23).
- **Minimización (Ley 1581 de 2012):** la planta Central tiene 77 columnas y la de Educación 46, con salario, RH, estado civil, etnia, discapacidad, hijos, dirección, EPS y fondos. **Nada de eso entra al sistema.** Solo se guardan identificación, nombre, correo institucional, cargo, grado, dependencia, jefe, planta, vinculación, provisión y fechas de vinculación.

### 4.4 `equipos` (columnas nuevas)

| Columna | Regla |
|---|---|
| `numero_activo` | Consecutivo del FOR.01 («ID ACTIVO»). Lo asigna el sistema al crear el equipo y nunca cambia. |
| `modelo_id` | Del catálogo `modelos` (marca + tipo + nombre). Reemplaza el texto libre. |
| `descripcion` | «Descripción Activo» del FOR.01. |
| `proceso_id` | Proceso al que da soporte el equipo. Por defecto, el de su dependencia. |
| `proveedor_tercero_id` | Obligatorio si `propiedad = tercero` (RN-16). Reemplaza `propietario_tercero` en texto. |
| `forma_ingreso` | `compra`, `donacion`, `comodato`, `convenio`, `arrendamiento` o `carga_inicial`. |
| `fecha_adquisicion`, `valor_adquisicion` | Valor en pesos (COP, RNF-14). Opcionales en `carga_inicial`. |
| `proveedor_id`, `numero_contrato`, `numero_factura`, `fecha_fin_garantia`, `vida_util_meses` | Datos de compra y garantía. Con la vida útil se calcula la obsolescencia. |
| `estado_funcionamiento` | Pasa de texto a enum: `bueno`, `regular`, `malo`, `no_funcional`. |

### 4.5 `valoraciones_activo` (GT-M4)

| Columna | Valores | Origen en GT-M4 |
|---|---|---|
| `contiene_datos_personales` | si / no / no_aplica | 4.2.1 |
| `clasificacion_datos` | publico, privado, semiprivado, sensible, reservado, clasificado, no_aplica | 4.2.1 |
| `infraestructura_critica`, `continuidad_negocio` | si / no / no_aplica | 4.2.2 |
| `propietario_cargo` | Cargo del director o líder del proceso | 4.2.3 |
| `custodio_cargo` | Cargo de quien administra el activo (p. ej. «Ingeniero de Soporte») | 4.2.3 |
| `tipo_ubicacion` | fisico, electronico, fisico_electronico | 4.2.4 |
| `tiempo_retencion` | Texto: «Indeterminado», «6 meses», «1 año» | Columna «RETENCIÓN» del FOR.01 |
| `confidencialidad`, `integridad`, `disponibilidad` | 1 a 4 | Tablas 3, 4 y 5 |
| `total` | C + I + D (3 a 12) | Tabla 6 |
| `importancia` | baja (0–3), media (4–6), alta (7–9), muy_alta (10–12) | Tabla 6 |
| `requiere_gestion_riesgo` | true si algún criterio vale 4, o si la importancia es alta o muy alta | 4.2.6 a) y b) |
| `controles_monitoreo` | Cámaras, sensor de humo, de temperatura, de humedad, o «No se cuenta con control» | Hoja «Datos» del FOR.01 |

Reglas: una sola valoración `vigente` por equipo; una reclasificación crea una fila nueva con un evento `valoracion` y marca la anterior como no vigente. `total`, `importancia` y `requiere_gestion_riesgo` se calculan, nunca se digitan (en MySQL, `total` puede ser columna generada; los otros dos los calcula el modelo de Eloquent). No aplica a equipos de tipo `soporte`, que según GT-M4 «no tienen clasificación de la información».

---

## 5. Valores oficiales de los catálogos (para los seeders)

Estos valores reemplazan los de `CatalogosSeeder` actual, que eran inventados: sedes como «Sede Alcaldía Mayor» o dependencias como «Secretaría de Agricultura» no existen en la estructura oficial. Lo marcado **(por confirmar)** sale de una sola fuente o tiene contradicciones; está en la lista de la sección 8.

### 5.1 Procesos (FOR.01, hoja «Datos»)

| Proceso | Tipo |
|---|---|
| Planificación Departamental | estratégico |
| Gestión de Mejora Continua y Planificación del SIG | estratégico |
| Gestión de Comunicaciones | estratégico |
| Gestión del Desarrollo Político | misional |
| Gestión del Desarrollo Social | misional |
| Gestión del Desarrollo Territorial | misional |
| Gestión del Riesgo y Desarrollo Ambiental | misional |
| Gestión del Desarrollo Económico | misional |
| Atención al Ciudadano | apoyo |
| Gestión Documental | apoyo |
| Contratación | apoyo |
| Gestión de Hacienda | apoyo |
| Gestión del Talento Humano | apoyo |
| Gestión Jurídica | apoyo |
| Gestión de Recursos Físicos | apoyo |
| Gestión Tecnológica y Sistemas de Información | apoyo |
| Evaluación y Control | evaluación y control |

El tipo de cada proceso es **(por confirmar)** con el mapa de procesos del SIG: el FOR.01 no lo trae, y la tabla 1 del GT-M4 es de una plantilla de empresa privada (habla de «Comercial», «Postventa» y «SAGRILAFT»), no de la Gobernación.

### 5.2 Dependencias (Decreto 609 de 2023 + planta Central mar-2026 + PAMP)

```
Despacho del Gobernador
├── Secretaría General
├── Oficina de Asesores
├── Oficina Asesora Jurídica
├── Oficina de Control Interno
├── Oficina de Control Interno Disciplinario
├── Dirección de Ambiente y Gestión del Riesgo
├── Dirección de Protocolo, Prensa y Comunicaciones
├── Departamento Administrativo de Planeación
│   ├── Dirección Departamental de Regalías            (por confirmar: el organigrama está borroso)
│   └── Dirección Departamental de Proyectos
├── Secretaría de Hacienda
│   ├── Dirección Financiera de Presupuesto
│   ├── Dirección de Contabilidad
│   ├── Dirección de Tesorería
│   ├── Dirección de Rentas
│   │   └── Grupo Anticontrabando                      (por confirmar)
│   ├── Dirección de Cobro Persuasivo y Jurisdicción Coactiva
│   └── Dirección Administrativa de Seguimiento y Control al Proceso de Ley 550
├── Secretaría de Gestión Administrativa
│   ├── Dirección de Talento Humano
│   │   ├── Nómina
│   │   ├── Seguridad y Salud en el Trabajo
│   │   ├── Historias Laborales
│   │   └── Bienestar y Capacitación
│   ├── Dirección Administrativa de Contratación
│   ├── Dirección de Tecnología, Informática y Comunicaciones (TICS)
│   ├── Dirección Administrativa de Atención al Ciudadano
│   ├── Almacén General
│   ├── Gestión Documental
│   │   └── Archivo (Fondo Acumulado)
│   └── Pensiones (Fondo Territorial de Pensiones)     (por confirmar: el PAMP lo pone en Hacienda)
├── Secretaría de Infraestructura
│   ├── Dirección Administrativa y Financiera de Infraestructura
│   ├── Dirección Técnica y Administrativa de Infraestructura
│   └── Dirección Departamental de Vivienda
├── Secretaría de Educación                            (planta = educacion)
│   ├── Calidad Educativa
│   ├── Cobertura
│   ├── Inspección y Vigilancia
│   ├── Talento Humano (Recurso Humano)
│   ├── Nómina
│   ├── Escalafón
│   ├── Jurídica
│   ├── Planeación
│   ├── Financiera
│   ├── Administrativa
│   ├── Servicio de Atención al Ciudadano (SAC)
│   ├── Archivo de Educación
│   └── Dirección de Seguridad Alimentaria (PAE)
├── Secretaría de Desarrollo de la Salud
│   ├── Dirección Administrativa de Inspección, Vigilancia y Control
│   ├── Área Financiera
│   ├── Aseguramiento
│   ├── Salud Pública
│   │   └── Laboratorio de Salud Pública
│   ├── Salud y Riesgos Laborales
│   ├── Autorizaciones, Radicación de Cuentas Médicas y Auditoría
│   ├── RETHUS
│   ├── Promoción Social
│   ├── Planeación en Salud
│   └── Centro Regulador de Urgencias y Emergencias (CRUE)
├── Secretaría del Interior y Participación Ciudadana
│   ├── Dirección Técnica de Atención a las Víctimas del Conflicto Armado
│   ├── Dirección Administrativa de Discapacidad
│   └── Oficina de Pasaportes
├── Secretaría de Desarrollo Económico y Agroindustrial
│   └── Dirección de Turismo y Artesanías
├── Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor
│   ├── Casa de la Mujer
│   └── Casa Refugio
├── Secretaría de Cultura
│   └── Biblioparque                                   (por confirmar)
├── Secretaría de Juventud
├── Secretaría de Tránsito y Movilidad
├── Secretaría de Competitividad y Cooperación Internacional
│   └── Dirección de Ciencia, Tecnología e Innovación
└── Secretaría de Minas y Energía                      (creada por el Decreto 609)
```

**Equivalencias iniciales** (`dependencia_equivalencias`), de los cambios de nombre del Decreto 609 (art. 3):

| Nombre anterior (aparece en FOR.01 y en Excel viejos) | Nombre oficial |
|---|---|
| Dirección Técnica de Sistemas · DIRECION DE SISTEMAS | Dirección de Tecnología, Informática y Comunicaciones (TICS) |
| Dirección de Tecnologías de la Información y Telecomunicaciones (Sec. Competitividad) | Dirección de Ciencia, Tecnología e Innovación |
| Secretaría de Mujer, Género y Desarrollo Social | Secretaría de la Mujer, Equidad, Infancia, Adolescencia y Adulto Mayor |
| Secretaría de Tránsito y Transporte | Secretaría de Tránsito y Movilidad |
| Dirección Técnica de Prensa | Dirección de Protocolo, Prensa y Comunicaciones |
| Dirección Administrativa de Personal · DIRECCION DE PERSONAL | Dirección de Talento Humano |
| Dirección Técnica de Información Financiera | Dirección de Tesorería |
| Dirección Financiera de Gestión de Ingresos · DIRECCION DE GESTION DE INGRESOS | Dirección de Rentas |
| Director Financiero con funciones de Contador | Dirección de Contabilidad |
| DIRECCION DE CONTROLY VIGILANCIA | Dirección Administrativa de Inspección, Vigilancia y Control |
| Control Interno Administrativo (PAMP) | Oficina de Control Interno |

### 5.3 Municipios (DIVIPOLA, departamento 23)

Montería 23001 · Ayapel 23068 · Buenavista 23079 · Canalete 23090 · Cereté 23162 · Chimá 23168 · Chinú 23182 · Ciénaga de Oro 23189 · Cotorra 23300 · La Apartada 23350 · Lorica 23417 · Los Córdobas 23419 · Momil 23464 · Montelíbano 23466 · Moñitos 23500 · Planeta Rica 23555 · Pueblo Nuevo 23570 · Puerto Escondido 23574 · Puerto Libertador 23580 · Purísima 23586 · Sahagún 23660 · San Andrés de Sotavento 23670 · San Antero 23672 · San Bernardo del Viento 23675 · San Carlos 23678 · San José de Uré 23682 · San Pelayo 23686 · Tierralta 23807 · Tuchín 23815 · Valencia 23855.

Verificar los códigos contra la DIVIPOLA vigente del DANE antes de sembrarlos.

### 5.4 Sedes y pisos (PAMP vigencia 2026 + FOR.01)

| Sede | Dirección (Montería) | Tipo | Pisos con dependencias |
|---|---|---|---|
| Palacio de Naín | Calle 27 No. 3-28 | administrativa · propia | 1 a 8 |
| Edificio Morindó | Calle 29 No. 2-43 | administrativa · (por confirmar) | 4, 6, 7, 8 |
| Edificio Victoria Char | Calle 26 No. 3-20 | administrativa · (por confirmar) | 1 a 5 |
| Places Mall | Carrera 6 No. 65-24, Local 44 | administrativa · arrendada (por confirmar) | 2 |
| Sede Secretaría de Cultura | Calle 29 No. 5-64 | cultural | 2 |
| Biblioparque | (por confirmar) | cultural | 2 |
| Casa de la Mujer | Calle 27 No. 6-49, Centro | social | 1 |
| Casa Refugio | **Reservada** (barrio Costa de Oro) | social · `direccion_reservada` | 1 |
| Laboratorio de Salud Pública | Carrera 15 No. 22-151, barrio Costa de Oro | salud | 1 |
| CRUE | Calle 42 No. 8-15 | salud | 1 |
| Suricentro | (por confirmar) | (por confirmar) | — |

Suricentro aparece en la hoja «Datos» del FOR.01, pero no en el PAMP 2026. Las 166 instituciones educativas (201 sedes en 71 municipios, según la planta de Educación) quedan **fuera de la carga inicial** hasta que se decida si sus equipos entran al alcance (sección 8).

### 5.5 Ubicación de dependencias por piso (PAMP 2026)

| Sede · piso | Dependencias | Equipos / impresoras |
|---|---|---|
| Palacio · 8 | Dirección de Contratación, Escalafón (Educación), Archivo, Dirección de Discapacidad | 24 / 1 |
| Palacio · 7 | Secretaría de Infraestructura, Dirección de Turismo, Nómina Central, Dirección de Talento Humano | 40 / 2 |
| Palacio · 6 | Dirección TICS, Nómina Administrativa, Oficina de Control Interno, Dirección de Ambiente y Gestión del Riesgo, Secretaría de Gestión Administrativa | 44 / 4 |
| Palacio · 5 | Secretaría de Educación (despacho, general, Calidad, Recurso Humano, Nómina, Cobertura, Inspección y Vigilancia, Jurídica, Planeación) | 86 / 9 |
| Palacio · 4 | Dirección de Vivienda, Departamento Administrativo de Planeación, Control Interno Disciplinario, Dirección de Víctimas, Gestión Documental, Secretaría del Interior | 38 / 4 |
| Palacio · 3 | Despacho del Gobernador, Oficina de Asesores, Dirección de Prensa y Protocolo, Oficina Asesora Jurídica | 13 / 0 |
| Palacio · 2 | Tesorería, Almacén, Seguridad Alimentaria (PAE), Secretaría de Hacienda, Fondo Territorial de Pensiones, Presupuesto, Ley 550, Contabilidad | 67 / 9 |
| Palacio · 1 | Historias Laborales, Atención al Ciudadano, SAC Educación, Seguridad y Salud en el Trabajo | 20 / 9 |
| Morindó · 8 | Secretaría de Salud (despacho) | 9 / 1 |
| Morindó · 7 | Área Financiera, Aseguramiento, Salud Pública, Salud y Riesgos Laborales | 17 / 1 |
| Morindó · 6 | Autorizaciones y Cuentas Médicas, RETHUS (Salud), Promoción Social | 15 / 0 |
| Morindó · 4 | Archivo de Educación | 4 / 3 |
| Victoria Char · 5 | Secretaría de Tránsito, Secretaría de Desarrollo Económico | 13 / 1 |
| Victoria Char · 4 | Secretaría de Juventud, Secretaría de la Mujer | 16 / 1 |
| Victoria Char · 3 | Secretaría de Minas y Energía, Secretaría de Competitividad | 7 / 1 |
| Victoria Char · 2 | Grupo Anticontrabando | 0 / 0 |
| Victoria Char · 1 | RETHUS, Oficina de Pasaportes | 4 / 1 |
| Places Mall · 2 | Dirección de Cobro Coactivo, Dirección de Rentas | 27 / 7 |
| Cultura · 2 | Secretaría de Cultura | 27 / 3 |
| Biblioparque · 2 | Biblioparque | 2 / 0 |
| Casa de la Mujer · 1 | Casa de la Mujer | 10 / 0 |
| Casa Refugio · 1 | Casa Refugio | 1 / 0 |
| Laboratorio · 1 | Laboratorio de Salud Pública | 10 / 0 |
| CRUE · 1 | CRUE | 13 / 7 |

Totales del PAMP: Palacio de Naín 332 equipos y 38 impresoras; sedes externas 175 equipos y 26 impresoras. Son conteos estimados, no inventario: sirven solo para comparar contra lo cargado y lo verificado. Del PAMP se toman únicamente sedes, pisos y ubicaciones; su programación de mantenimiento no entra al sistema.

### 5.6 Cargos (Decreto 607 de 2023)

| Código | Denominación | Nivel |
|---|---|---|
| 001 | Gobernador | directivo |
| 006 | Jefe de Oficina | directivo |
| 009 | Director Administrativo, Financiero, Técnico u Operativo | directivo |
| 020 | Secretario de Despacho | directivo |
| 055 | Director de Departamento Administrativo | directivo |
| 105 | Asesor | asesor |
| 115 | Jefe de Oficina Asesora Jurídica | asesor |
| 215 | Almacenista General | profesional |
| 219 | Profesional Universitario | profesional |
| 222 | Profesional Especializado | profesional |
| 237 | Profesional Universitario Área Salud | profesional |
| 242 | Profesional Especializado Área Salud | profesional |
| 314 | Técnico Operativo | técnico |
| 323 | Técnico Área Salud | técnico |
| 367 | Técnico Administrativo | técnico |
| 407 | Auxiliar Administrativo | asistencial |
| 412 | Auxiliar Área Salud | asistencial |
| 430 | Secretario Ejecutivo del Despacho del Gobernador | asistencial |
| 440 | Secretario | asistencial |
| 470 | Auxiliar de Servicios Generales | asistencial |
| 477 | Celador | asistencial |
| — | Contratista de prestación de servicios | (según objeto; se usa `profesional`) |
| — | Pasante | asistencial |

El texto escaneado del art. 19 del Decreto 607 le asigna el código 430 al Auxiliar de Servicios Generales; la planta de Educación usa 470, igual que el art. 15. Se toma **470**.

### 5.7 Tipos de equipo

| Tipo | Familia | Tipo de activo | Configuración de cómputo |
|---|---|---|---|
| PC de escritorio | computo | hardware | sí |
| Portátil | computo | hardware | sí |
| All in One | computo | hardware | sí |
| Servidor físico | computo | hardware | sí |
| Monitor | video | hardware | no |
| Impresora | impresion | hardware | no |
| Multifuncional | impresion | hardware | no |
| Escáner | digitalizacion | hardware | no |
| Switch | conectividad | hardware | no |
| Router | conectividad | hardware | no |
| Access Point | conectividad | hardware | no |
| Firewall | conectividad | hardware | no |
| NAS (almacenamiento en red) | computo | hardware | no |
| Videograbador (DVR/NVR) | seguridad_electronica | hardware | no |
| Cámara de vigilancia | seguridad_electronica | hardware | no |
| Control de acceso (biométrico) | seguridad_electronica | hardware | no |
| Video beam | proyeccion | hardware | no |
| Parlante / sistema de audio | proyeccion | hardware | no |
| Teléfono IP | telefonia | hardware | no |
| Planta telefónica | telefonia | hardware | no |
| UPS | energia | soporte | no |
| Planta eléctrica | energia | soporte | no |
| Aire acondicionado (cuarto técnico) | climatizacion | soporte | no |
| Otro | computo | hardware | no |

«Otro» es el respaldo del importador cuando un tipo no coincide. El resto sale de los activos de ejemplo del FOR.01 (NAS, videograbadores, control de acceso, equipos de comunicación, servidores, recurso audiovisual) y de la tabla 2 del GT-M4 (soporte: UPS, aire acondicionado, planta eléctrica).

### 5.8 Otros catálogos

- **Tipos de componente:** RAM*, Disco*, Procesador*, Tarjeta de red, Tarjeta de video, Fuente de poder, Teclado, Mouse, Sonido, Cámara web (* `obligatorio_en_computo`).
- **Sistemas operativos (fin de soporte):** Windows 7 (2020-01-14), Windows 8.1 (2023-01-10), Windows 10 Home/Pro (2025-10-14), Windows 11 Home/Pro (null), Windows Server 2016/2019/2022, Linux, N/A.
- **Motivos de baja:** Obsolescencia, Daño irreparable, Pérdida, Hurto, Devolución al propietario (tercero), Reposición.
- **Controles de monitoreo (FOR.01):** cámaras de vigilancia, sensor de humo, sensor de temperatura, sensor de humedad y sus combinaciones; «No se cuenta con control de monitoreo».

---

## 6. Migración en dos tiempos

Renombrar o borrar columnas de una vez habría roto los bloques de todos antes de que empezaran. Por eso la migración va en dos tiempos (patrón *expand/contract*):

### 6.1 Primer tiempo — hecho (rama `feature/nucleo-modelo-v4`)

Quince migraciones `2026_10_18_1000xx_*` que **solo agregan**: las 9 tablas nuevas y todas las columnas nuevas. Las columnas viejas siguen existiendo y los modelos las mantienen sincronizadas, así el código que todavía las usa funciona igual. Con eso pasan las 212 pruebas que ya existían, más 28 nuevas del núcleo (`tests/Feature/ModeloV4Test.php`).

| Columna vieja (sigue existiendo) | Columna nueva (usar en código nuevo) | Quién las sincroniza |
|---|---|---|
| `personas.nombre` | `personas.nombres` + `personas.apellidos` | `Persona::sincronizarCamposViejos()` |
| `personas.cedula` | `personas.numero_documento` (+ `tipo_documento`) | `Persona` |
| `personas.cargo` (texto) | `personas.cargo_id` → `cargos` (+ `grado`, `encargo`) | `Persona` |
| `equipos.modelo` (texto) | `equipos.modelo_id` → `modelos` | `Equipo::sincronizarCamposViejos()` |
| `equipos.propietario_tercero` (texto) | `equipos.proveedor_tercero_id` → `proveedores` | `Equipo` |
| `asignaciones.sede_id` | `asignaciones.piso_id` → `pisos.sede_id` | `Asignacion` (copia la sede del piso) |
| `puestos_trabajo.sede_id` | `puestos_trabajo.piso_id` → `pisos.sede_id` | `PuestoTrabajo` |

Además quedan opcionales mientras dure la transición: `pisos.sede_id` y `sedes.municipio_id` (las pruebas viejas crean sedes y pisos sueltos), y `equipos.estado_funcionamiento` sigue siendo texto («Bueno», «Regular», «Malo»).

Se conservaron cuatro nombres de v3 en lugar de renombrarlos, porque cambiarlos no aportaba nada y rompía código: `personas.activo`, `configuraciones_computo.licencia` (es la del sistema operativo), `puestos_trabajo.nombre_o_codigo` y el valor `proyeccion` de `tipos_equipo.familia`. El DBML y el SQL ya usan esos nombres.

### 6.2 Segundo tiempo — al cerrar el día 2 (Jhon)

Cuando todos los bloques usen las columnas nuevas (días 1 y 2 del plan), Jhon crea la migración de cierre:

1. Borra `personas.nombre`, `personas.cedula`, `personas.cargo`, `equipos.modelo`, `equipos.propietario_tercero`, `asignaciones.sede_id` y `puestos_trabajo.sede_id`. ✅ hecho
2. Vuelve obligatorios `pisos.sede_id` y `sedes.municipio_id`. ✅ hecho. **`personas.numero_documento` NO se volvió obligatorio** (cambio frente al plan original): la carga de inventario crea a propósito responsables sin documento cuando el Excel no trae la cédula (`CargaEquipos::personaSinDocumento`, RF-38/39) y los reutiliza por nombre; el índice único ya convive con varios `NULL` en MySQL.
3. ✅ hecho (PR aparte): `equipos.estado_funcionamiento` y `componentes.estado` pasaron a la lista cerrada `bueno`, `regular`, `malo`, `no_funcional` (enum). La clave se guarda y la etiqueta se muestra con `Equipo::ESTADOS_FUNCIONAMIENTO` / `Componente::ESTADOS` (migración `2026_10_21_100001_estado_funcionamiento_a_enum`).
4. Quita de los modelos la sincronización de los campos viejos. ✅ hecho

Migración: `database/migrations/2026_10_20_120001_cerrar_migracion_dos_tiempos.php`.

**Decisiones registradas al cerrar la Fase 2** (no son fallas; las tomó el líder):

- **`personas.numero_documento` nullable** — ver el punto 2.
- **El tablero no compara contra el PAMP** (RF-36): falta esa fuente de datos en el sistema.
- **RF-39: la tabla editable de equivalencias es solo para dependencias** (`dependencia_equivalencias`, pantalla Administración → Dependencias), que es donde hacía falta por las 175 variantes del inventario 2026 y los renombres del Decreto 609. Las variantes de **sedes, marcas, modelos y sistemas operativos** se resuelven en código durante la carga (normalización en `Importacion/Soporte/Normalizar` y `ConversorInventario2026`, más emparejado por nombre o `firstOrCreate`), sin tabla editable ni migración. Si aparece un caso que el código no resuelve, se ajusta la normalización.

**Antes de abrir el PR de cierre**, cada dueño confirma que su bloque ya no usa ninguna columna vieja:

```bash
grep -rnE "cedula|propietario_tercero|->cargo\b|'cargo'|->modelo\b|'modelo'|sede_id" app/Livewire/<Bloque> resources/views/livewire/<bloque>
```

---

## 7. Reglas que el esquema no puede garantizar solo (las valida el modelo de Laravel)

1. Una sola asignación abierta y una sola valoración vigente por equipo (MySQL no tiene índice único parcial).
2. `dependencias.padre_id` sin ciclos.
3. `configuraciones_computo` solo para tipos con `usa_configuracion_computo = true`, y `valoraciones_activo` solo para `tipo_activo = hardware`.
4. El evento 1 a 1 (`cambios_componente`, `diagnosticos`) debe tener el `tipo` que le corresponde.
5. `proveedor_tercero_id` es obligatorio si `propiedad = tercero`.
6. `total`, `importancia` y `requiere_gestion_riesgo` siempre calculados.
7. La dirección de una sede con `direccion_reservada` nunca sale en vistas, exportaciones ni PDF.

---

## 8. Preguntas abiertas sobre el modelo

| # | Pregunta | Supuesto del modelo |
|---|---|---|
| 1 | ¿Los equipos de las 166 instituciones educativas entran al sistema? | No en la carga inicial; el enum `tipo_sede` ya lo prevé. |
| 2 | ¿Se registran solo hardware y soporte, o también software, servicios e información (los otros tipos del GT-M4)? | Solo hardware y soporte: es una hoja de vida de equipos. |
| 3 | ¿Propietario y custodio se guardan como cargo (texto, como dice el GT-M4) o como persona? | Cargo en la valoración y persona en el acta del levantamiento. |
| 4 | Tipo (estratégico, misional, apoyo) de cada proceso según el mapa del SIG. | El de la tabla 5.1. |
| 5 | Tenencia (propia o arrendada) de Morindó, Victoria Char, Places Mall y Suricentro, y si Suricentro sigue en uso. | `por_confirmar`. |
| 6 | ¿Pensiones depende de Gestión Administrativa (planta) o de Hacienda (PAMP)? ¿Biblioparque de Cultura? ¿Grupo Anticontrabando de Rentas? | Los de la sección 5.2. |
| 7 | Funciones de la Dirección TICS y del Almacén General en el Decreto 608: están en las páginas 334 a 353 y la herramienta de lectura solo extrajo las 80 primeras de 639. | Pendiente de leer el PDF completo. |
| 8 | Dónde funcionan las 11 dependencias que el PAMP no ubica: Secretaría General, Dirección Departamental de Regalías, Dirección Departamental de Proyectos, Dirección de Ciencia, Tecnología e Innovación, Dirección Administrativa y Financiera de Infraestructura, Dirección Técnica y Administrativa de Infraestructura, Dirección Administrativa de Inspección, Vigilancia y Control (Salud), Planeación en Salud, Bienestar y Capacitación, y Financiera y Administrativa de Educación. | Sin fila en `ubicaciones_dependencia`. |

---

## 9. DBML completo (copiar y pegar en dbdiagram.io)

```dbml
// =====================================================================
// Sistema de Hoja de Vida de Activos Tecnológicos
// Gobernación de Córdoba · Dirección TIC · Modelo de datos v4
// ---------------------------------------------------------------------
// Fuentes oficiales: Manual GT-M4 (activos de información, 28/09/2023),
// Formato FOR.01 inventario de activos v00, Programa Anual de
// Mantenimiento Preventivo 2026 (solo como fuente de sedes, pisos y
// dependencias: el mantenimiento sigue fuera del alcance), Decretos 607,
// 608 y 609 de 2023 y plantas de personal Central y Educación.
//
// Cómo verlo: pegar todo este archivo en https://dbdiagram.io/d
// Documentación completa: docs/base-de-datos.md
// =====================================================================

Project hoja_vida_activos {
  database_type: 'MySQL'
  Note: '''
  Modelo v4. Reemplaza el modelo v3, que salía de las 53 columnas del
  inventario 2026 (Microsoft Forms). Ese Excel ya no define la
  estructura: solo se usa después, como fuente de datos para la carga.
  '''
}

// ---------------------------------------------------------------------
// ENUMS
// ---------------------------------------------------------------------

Enum tipo_proceso {
  estrategico
  misional
  apoyo
  evaluacion_control
}

Enum tipo_dependencia {
  despacho [note: 'Despacho del Gobernador']
  secretaria [note: 'Secretaría de Despacho (código 020)']
  departamento_administrativo [note: 'Departamento Administrativo de Planeación (código 055)']
  oficina [note: 'Oficina asesora o de control (códigos 006, 115)']
  direccion [note: 'Dirección administrativa, financiera, técnica u operativa (código 009)']
  area [note: 'Área o grupo interno sin cargo directivo propio (Nómina, Cobertura, RETHUS...)']
}

Enum planta_personal {
  central
  educacion
}

Enum tenencia_sede {
  propia
  arrendada
  comodato
  por_confirmar
}

Enum tipo_sede {
  administrativa
  salud [note: 'Laboratorio, CRUE']
  social [note: 'Casa de la Mujer, Casa Refugio']
  cultural [note: 'Biblioparque']
  institucion_educativa [note: 'Fuera del alcance inicial']
}

Enum nivel_jerarquico {
  directivo [note: 'Código 0xx (Decreto 607, art. 3)']
  asesor [note: 'Código 1xx']
  profesional [note: 'Código 2xx']
  tecnico [note: 'Código 3xx']
  asistencial [note: 'Código 4xx']
}

Enum tipo_vinculacion {
  planta
  contratista
  pasante
}

Enum tipo_provision {
  carrera_administrativa [note: 'C.A.']
  libre_nombramiento [note: 'L.N.']
  provisional [note: 'P.P. / PR']
  temporal
  periodo [note: 'E.P. (Gobernador)']
  no_aplica [note: 'Contratistas y pasantes']
}

Enum tipo_documento_identidad {
  CC
  CE
  PPT
  PA
  TI
}

Enum tipo_activo {
  hardware [note: 'GT-M4 tabla 2: equipos de cómputo, comunicaciones y protección']
  soporte [note: 'GT-M4 tabla 2: UPS, aire acondicionado, planta eléctrica. Sin clasificación de información']
}

Enum familia_equipo {
  computo
  video
  impresion
  digitalizacion
  conectividad
  seguridad_electronica
  energia
  climatizacion
  proyeccion [note: 'Video beam y audio']
  telefonia
}

Enum propiedad_equipo {
  gobernacion
  tercero
}

Enum figura_tercero {
  comodato
  convenio
  proveedor
  arrendamiento
}

Enum forma_ingreso {
  compra
  donacion
  comodato
  convenio
  arrendamiento
  carga_inicial [note: 'Equipos que ya existían al arrancar el sistema']
}

Enum estado_funcionamiento {
  bueno
  regular [note: 'Funciona con fallas menores']
  malo [note: 'Requiere cambio de componentes']
  no_funcional [note: 'Requiere ser dado de baja']
}

Enum estado_ciclo_vida {
  en_servicio
  sin_asignar
  dado_de_baja
}

Enum estado_verificacion {
  verificado
  pendiente_de_verificar
}

Enum si_no_na {
  si
  no
  no_aplica
}

Enum clasificacion_datos_personales {
  publico
  privado
  semiprivado
  sensible
  reservado
  clasificado
  no_aplica
}

Enum tipo_ubicacion_activo {
  fisico
  electronico
  fisico_electronico
}

Enum importancia_activo {
  baja [note: 'Total CID 0 a 3']
  media [note: 'Total CID 4 a 6']
  alta [note: 'Total CID 7 a 9']
  muy_alta [note: 'Total CID 10 a 12']
}

Enum tipo_evento {
  alta
  traslado_responsable
  cambio_componente
  diagnostico
  valoracion [note: 'Nuevo en v4 (GT-M4): reclasificación CID']
  baja
  actualizacion_datos
  anulacion_aclaracion
}

Enum estado_firma {
  no_aplica
  pendiente_de_firma
  completo
}

Enum accion_componente {
  agregar
  cambiar
  quitar
}

Enum destino_retirado {
  bodega
  otro_equipo
  descarte
}

Enum estado_levantamiento {
  en_curso
  pendiente_de_firma
  aceptado
}

Enum tipo_documento_adjunto {
  formato_entrega
  formato_baja
  acta_aceptacion_activos
  foto
  otro
}

Enum motivo_etiqueta {
  primera_impresion
  reposicion
}

Enum perfil_usuario {
  administrador
  usuario
}

// ---------------------------------------------------------------------
// 1. ESTRUCTURA ORGANIZACIONAL (Decretos 607 y 609, plantas de personal)
// ---------------------------------------------------------------------

Table procesos {
  id bigint [pk, increment]
  nombre varchar(120) [not null, unique]
  tipo tipo_proceso [not null]
  activo boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
  Note: 'Mapa de procesos del SIG. 17 procesos listados en la hoja "Datos" del formato FOR.01.'
}

Table dependencias {
  id bigint [pk, increment]
  padre_id bigint [ref: > dependencias.id, note: 'Null solo para el Despacho del Gobernador']
  tipo tipo_dependencia [not null]
  nombre varchar(160) [not null, note: 'Nombre oficial según Decreto 609 de 2023']
  nombre_corto varchar(60) [note: 'Como se muestra en listas: "Dirección TIC"']
  sigla varchar(20)
  proceso_id bigint [ref: > procesos.id, note: 'Proceso al que pertenece (GT-M4 4.2.1)']
  planta planta_personal [not null, default: 'central']
  acto_administrativo varchar(60) [note: 'Ej.: "Decreto 609 de 2023"']
  activa boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  indexes {
    (padre_id, nombre) [unique]
  }
  Note: 'Árbol: Despacho → Secretaría/Oficina/DAP → Dirección → Área. Reemplaza la lista plana de v3.'
}

Table dependencia_equivalencias {
  id bigint [pk, increment]
  dependencia_id bigint [not null, ref: > dependencias.id]
  texto varchar(200) [not null, unique, note: 'Variante tal como llega en un Excel: "DIRECION DE SISTEMAS", "Sec Educación"']
  origen varchar(60) [note: 'FOR.01, inventario 2026, PAMP, planta…']
  created_at timestamp
  Note: 'Tabla de equivalencias editable (RF-39). Incluye los nombres anteriores al Decreto 609.'
}

Table municipios {
  id bigint [pk, increment]
  codigo_dane char(5) [not null, unique]
  nombre varchar(80) [not null, unique]
  Note: 'Los 30 municipios de Córdoba (DIVIPOLA).'
}

Table sedes {
  id bigint [pk, increment]
  nombre varchar(120) [not null, unique]
  direccion varchar(160) [note: 'Reservada en Casa Refugio: no se muestra en listados ni reportes']
  barrio varchar(80)
  municipio_id bigint [not null, ref: > municipios.id]
  tipo tipo_sede [not null, default: 'administrativa']
  tenencia tenencia_sede [not null, default: 'por_confirmar']
  direccion_reservada boolean [not null, default: false]
  activa boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
  Note: 'GT-M4 lo llama "Sucursal". Valores reales en el PAMP 2026.'
}

Table pisos {
  id bigint [pk, increment]
  sede_id bigint [not null, ref: > sedes.id]
  numero smallint [not null, note: '0 = planta baja o local único']
  nombre varchar(60) [note: 'Ej.: "Local 44"']
  created_at timestamp
  updated_at timestamp

  indexes {
    (sede_id, numero) [unique]
  }
  Note: 'En v3 los pisos eran 1 a 8 para todas las sedes. Ahora cada sede tiene los suyos.'
}

Table ubicaciones_dependencia {
  id bigint [pk, increment]
  dependencia_id bigint [not null, ref: > dependencias.id]
  piso_id bigint [not null, ref: > pisos.id]
  created_at timestamp

  indexes {
    (dependencia_id, piso_id) [unique]
  }
  Note: 'En qué sede y piso funciona cada dependencia (tablas del PAMP). Sirve para prellenar traslados y validar ubicaciones.'
}

Table cargos {
  id bigint [pk, increment]
  codigo char(3) [note: 'Primer dígito = nivel (Decreto 607, art. 16). Null para contratistas']
  denominacion varchar(120) [not null]
  nivel nivel_jerarquico [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (codigo, denominacion) [unique]
  }
  Note: 'Nomenclatura oficial de empleos (Decreto 607 de 2023).'
}

Table personas {
  id bigint [pk, increment]
  tipo_documento tipo_documento_identidad [not null, default: 'CC']
  numero_documento varchar(20) [not null, unique, note: 'Dato personal (Ley 1581): enmascarado en listados']
  nombres varchar(80) [not null]
  apellidos varchar(80) [not null]
  correo_institucional varchar(120)
  cargo_id bigint [ref: > cargos.id]
  grado char(2) [note: 'Grado salarial del empleo (01 a 10)']
  encargo boolean [not null, default: false, note: 'Cargo ejercido en encargo (E)']
  dependencia_id bigint [ref: > dependencias.id, note: 'Ubicación real ("REAL UBICACIÓN" de la planta)']
  jefe_inmediato_id bigint [ref: > personas.id]
  planta planta_personal
  tipo_vinculacion tipo_vinculacion [not null]
  tipo_provision tipo_provision [not null, default: 'no_aplica']
  numero_contrato varchar(40) [note: 'Solo contratistas']
  fecha_fin_vinculacion date [note: 'Fin de contrato o retiro: dispara el paz y salvo (RF-23)']
  activo boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
  Note: 'Minimización de datos (Ley 1581): de las 77 columnas de la planta solo se guardan las necesarias para responder por un equipo. No se guardan salario, RH, estado civil, etnia, dirección, hijos ni salud.'
}

// ---------------------------------------------------------------------
// 2. CATÁLOGOS DEL ACTIVO
// ---------------------------------------------------------------------

Table tipos_equipo {
  id bigint [pk, increment]
  nombre varchar(80) [not null, unique]
  familia familia_equipo [not null]
  tipo_activo tipo_activo [not null, default: 'hardware']
  usa_configuracion_computo boolean [not null, default: false, note: 'Muestra SO, antivirus, red y componentes internos']
  campos_aplicables json [note: 'Claves de equipos.caracteristicas que aplican: pulgadas, VA, puertos, lúmenes…']
  vida_util_meses smallint [note: 'Valor sugerido para equipos nuevos']
  activo boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}

Table marcas {
  id bigint [pk, increment]
  nombre varchar(60) [not null, unique]
  created_at timestamp
  updated_at timestamp
}

Table modelos {
  id bigint [pk, increment]
  marca_id bigint [not null, ref: > marcas.id]
  tipo_equipo_id bigint [not null, ref: > tipos_equipo.id]
  nombre varchar(100) [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (marca_id, tipo_equipo_id, nombre) [unique]
  }
  Note: 'Nuevo en v4: el modelo deja de ser texto libre.'
}

Table sistemas_operativos {
  id bigint [pk, increment]
  nombre varchar(60) [not null, unique]
  fin_soporte date [note: 'Fecha de fin de soporte del fabricante: alimenta el reporte de obsolescencia']
  created_at timestamp
  updated_at timestamp
}

Table tipos_componente {
  id bigint [pk, increment]
  nombre varchar(60) [not null, unique]
  es_periferico boolean [not null, default: false]
  obligatorio_en_computo boolean [not null, default: false, note: 'RAM, disco y procesador']
  created_at timestamp
  updated_at timestamp
}

Table motivos_baja {
  id bigint [pk, increment]
  nombre varchar(80) [not null, unique]
  created_at timestamp
  updated_at timestamp
}

Table proveedores {
  id bigint [pk, increment]
  nit varchar(20) [unique]
  razon_social varchar(160) [not null]
  telefono varchar(30)
  correo varchar(120)
  created_at timestamp
  updated_at timestamp
  Note: 'Quien vendió, donó o presta el equipo.'
}

// ---------------------------------------------------------------------
// 3. EQUIPO Y SU HOJA DE VIDA
// ---------------------------------------------------------------------

Table equipos {
  id bigint [pk, increment]
  qr_uuid char(36) [not null, unique, note: 'Identificador permanente del QR (RN-19)']
  numero_activo int [not null, unique, note: 'Consecutivo "ID ACTIVO" del FOR.01, lo asigna el sistema']
  serial varchar(80) [not null, unique, note: 'PENDIENTE-REG-###### si aún no se ha tomado (RN-02)']
  codigo_activo varchar(20) [note: 'Placa de Almacén I1-######. Índice no único: RN-03']
  codigo_activo_justificacion text
  tipo_equipo_id bigint [not null, ref: > tipos_equipo.id]
  marca_id bigint [not null, ref: > marcas.id]
  modelo_id bigint [ref: > modelos.id]
  descripcion varchar(255) [note: '"Descripción Activo" del FOR.01']
  caracteristicas json [note: 'Valores según tipos_equipo.campos_aplicables']
  proceso_id bigint [ref: > procesos.id, note: 'Proceso al que da soporte (FOR.01)']
  propiedad propiedad_equipo [not null, default: 'gobernacion']
  proveedor_tercero_id bigint [ref: > proveedores.id, note: 'Dueño si propiedad = tercero']
  figura_tercero figura_tercero
  forma_ingreso forma_ingreso [not null]
  fecha_adquisicion date
  valor_adquisicion decimal(15,2) [note: 'COP']
  proveedor_id bigint [ref: > proveedores.id, note: 'A quién se compró']
  numero_contrato varchar(40)
  numero_factura varchar(40)
  fecha_fin_garantia date
  vida_util_meses smallint
  estado_funcionamiento estado_funcionamiento [not null, default: 'bueno']
  estado_ciclo_vida estado_ciclo_vida [not null, default: 'en_servicio']
  verificacion estado_verificacion [not null, default: 'pendiente_de_verificar']
  puesto_trabajo_id bigint [ref: > puestos_trabajo.id]
  importacion_id bigint [ref: > importaciones.id]
  fila_origen_importacion int
  observaciones text
  created_at timestamp
  updated_at timestamp

  indexes {
    codigo_activo
    (estado_ciclo_vida, tipo_equipo_id)
  }
  Note: 'Centro del modelo. Ubicación y responsable actuales viven en asignaciones (la abierta).'
}

Table valoraciones_activo {
  id bigint [pk, increment]
  equipo_id bigint [not null, ref: > equipos.id]
  evento_id bigint [ref: - eventos.id, note: 'Evento "valoracion" que la creó']
  levantamiento_id bigint [ref: > levantamientos_activos.id]
  contiene_datos_personales si_no_na [not null]
  clasificacion_datos clasificacion_datos_personales [not null, default: 'no_aplica']
  infraestructura_critica si_no_na [not null]
  continuidad_negocio si_no_na [not null]
  propietario_cargo varchar(120) [not null, note: 'Director o líder del proceso (GT-M4 4.2.3)']
  custodio_cargo varchar(120) [not null, note: 'Cargo que administra el activo: "Ingeniero de Soporte"']
  tipo_ubicacion tipo_ubicacion_activo [not null, default: 'fisico']
  tiempo_retencion varchar(40) [note: '"Indeterminado", "6 meses", "1 año"']
  confidencialidad tinyint [not null, note: '1 a 4 (GT-M4 tabla 3)']
  integridad tinyint [not null, note: '1 a 4 (tabla 4)']
  disponibilidad tinyint [not null, note: '1 a 4 (tabla 5)']
  total tinyint [not null, note: 'Columna generada: C + I + D']
  importancia importancia_activo [not null, note: 'Calculada con la tabla 6']
  requiere_gestion_riesgo boolean [not null, note: 'Algún criterio = 4, o importancia alta/muy alta (GT-M4 4.2.6)']
  controles_monitoreo varchar(200) [note: 'Cámaras, sensor de humo, temperatura, humedad (hoja "Datos")']
  vigente boolean [not null, default: true, note: 'Solo una vigente por equipo']
  fecha date [not null]
  usuario_id bigint [not null, ref: > users.id]
  created_at timestamp
  Note: 'Valoración CID del Manual GT-M4. Histórica: un incidente o el líder del proceso pueden reclasificarla. Solo aplica a tipo_activo = hardware.'
}

Table configuraciones_computo {
  id bigint [pk, increment]
  equipo_id bigint [not null, unique, ref: - equipos.id]
  sistema_operativo_id bigint [ref: > sistemas_operativos.id]
  licencia varchar(80) [note: 'Licencia del sistema operativo']
  ofimatica varchar(80) [note: 'Ej.: Office Home and Business']
  licencia_ofimatica varchar(80)
  tiene_antivirus boolean [not null, default: false]
  antivirus_producto varchar(80)
  nombre_equipo varchar(60) [note: 'Hostname']
  dominio varchar(60)
  direccion_ip varchar(45)
  direccion_mac varchar(17)
  created_at timestamp
  updated_at timestamp
}

Table componentes {
  id bigint [pk, increment]
  equipo_id bigint [not null, ref: > equipos.id]
  tipo_componente_id bigint [not null, ref: > tipos_componente.id]
  capacidad_caracteristica varchar(80)
  marca varchar(60)
  modelo varchar(80)
  serial varchar(80)
  estado estado_funcionamiento
  fecha_instalacion date
  fecha_retiro date [note: 'Null = instalado']
  created_at timestamp
  updated_at timestamp
}

Table puestos_trabajo {
  id bigint [pk, increment]
  nombre_o_codigo varchar(255) [not null]
  piso_id bigint [not null, ref: > pisos.id]
  dependencia_id bigint [ref: > dependencias.id]
  created_at timestamp
  updated_at timestamp
}

Table asignaciones {
  id bigint [pk, increment]
  equipo_id bigint [not null, ref: > equipos.id]
  persona_id bigint [ref: > personas.id, note: 'Null = sin responsable (bodega)']
  piso_id bigint [not null, ref: > pisos.id, note: 'El piso ya define la sede']
  dependencia_id bigint [ref: > dependencias.id]
  puesto_trabajo_id bigint [ref: > puestos_trabajo.id]
  fecha_inicio date [not null]
  fecha_fin date [note: 'Null = asignación actual']
  evento_origen_id bigint [ref: > eventos.id]
  created_at timestamp
  updated_at timestamp
  Note: 'Una sola abierta por equipo (RN-04).'
}

// ---------------------------------------------------------------------
// 4. EVENTOS (historial inmutable)
// ---------------------------------------------------------------------

Table eventos {
  id bigint [pk, increment]
  equipo_id bigint [not null, ref: > equipos.id]
  tipo tipo_evento [not null]
  fecha datetime [not null]
  es_historico boolean [not null, default: false, note: 'Alta de carga inicial: exento de RN-13']
  descripcion text
  valores json [note: '{antes: {...}, despues: {...}}']
  usuario_id bigint [not null, ref: > users.id, note: 'Autor, nunca cambia (RF-11)']
  estado_firma estado_firma [not null, default: 'no_aplica']
  evento_anulado_id bigint [ref: > eventos.id]
  created_at timestamp
  updated_at timestamp
  Note: 'Solo se escribe vía HistorialService::registrar(). update()/delete() bloqueados.'
}

Table cambios_componente {
  id bigint [pk, increment]
  evento_id bigint [not null, unique, ref: - eventos.id]
  accion accion_componente [not null]
  componente_retirado_id bigint [ref: > componentes.id]
  serial_retirado varchar(80)
  componente_instalado_id bigint [ref: > componentes.id]
  serial_instalado varchar(80)
  motivo text
  destino_retirado destino_retirado
  equipo_destino_id bigint [ref: > equipos.id, note: 'Si destino = otro_equipo']
  created_at timestamp
}

Table diagnosticos {
  id bigint [pk, increment]
  evento_id bigint [not null, unique, ref: - eventos.id]
  estado_encontrado text [not null]
  causa text
  recomendaciones text
  es_baja boolean [not null, default: false]
  motivo_baja_id bigint [ref: > motivos_baja.id]
  created_at timestamp
}

Table documentos {
  id bigint [pk, increment]
  evento_id bigint [not null, ref: > eventos.id]
  tipo tipo_documento_adjunto [not null]
  consecutivo varchar(30)
  archivo_path varchar(255) [not null]
  firmado boolean [not null, default: false]
  fecha date [not null]
  created_at timestamp
  updated_at timestamp
}

Table etiquetas_qr {
  id bigint [pk, increment]
  equipo_id bigint [not null, ref: > equipos.id]
  fecha_impresion datetime [not null]
  usuario_id bigint [not null, ref: > users.id]
  motivo motivo_etiqueta [not null]
  lote varchar(40)
  created_at timestamp
}

// ---------------------------------------------------------------------
// 5. LEVANTAMIENTO Y ACEPTACIÓN DE ACTIVOS (GT-M4 4.1)
// ---------------------------------------------------------------------

Table levantamientos_activos {
  id bigint [pk, increment]
  codigo varchar(30) [not null, unique]
  dependencia_id bigint [not null, ref: > dependencias.id]
  proceso_id bigint [ref: > procesos.id]
  sede_id bigint [not null, ref: > sedes.id]
  fecha_inicio date [not null]
  fecha_aceptacion date
  propietario_persona_id bigint [ref: > personas.id, note: 'Director o líder del proceso']
  custodio_persona_id bigint [ref: > personas.id]
  estado estado_levantamiento [not null, default: 'en_curso']
  acta_path varchar(255) [note: 'Acta de identificación y aceptación firmada']
  usuario_id bigint [not null, ref: > users.id]
  created_at timestamp
  updated_at timestamp
  Note: 'La matriz de activos por proceso y su acta firmada (GT-M4 4.1 c y 4.1.2). Reemplaza la "verificación en sitio" informal de v3.'
}

// ---------------------------------------------------------------------
// 6. SISTEMA: usuarios, auditoría, importación
// ---------------------------------------------------------------------

Table users {
  id bigint [pk, increment]
  name varchar(255) [not null]
  email varchar(255) [not null, unique]
  password varchar(255) [not null]
  persona_id bigint [ref: - personas.id, note: 'Funcionario o contratista TIC que usa el sistema']
  perfil perfil_usuario [not null, default: 'usuario']
  activo boolean [not null, default: true]
  debe_cambiar_contrasena boolean [not null, default: true]
  remember_token varchar(100)
  created_at timestamp
  updated_at timestamp
}

Table auditorias {
  id bigint [pk, increment]
  usuario_id bigint [ref: > users.id]
  accion varchar(60) [not null]
  entidad varchar(60) [not null]
  entidad_id bigint
  valor_anterior json
  valor_nuevo json
  ip varchar(45)
  created_at timestamp [not null]
  Note: 'Inmutable, mínimo 5 años (RNF-10).'
}

Table importaciones {
  id bigint [pk, increment]
  tipo varchar(30) [not null, note: 'equipos | personas | valoraciones']
  archivo varchar(255) [not null]
  fecha datetime [not null]
  usuario_id bigint [not null, ref: > users.id]
  filas int [not null]
  errores json
  created_at timestamp
  updated_at timestamp
}
```

## 10. Fase 3 — cambios de esquema (los crea el líder)

> Esta sección **describe** lo que la Fase 3 necesita en la base de datos; las migraciones todavía no existen. **Solo Jhon las crea** ([normas-de-trabajo.md, sección 2](./normas-de-trabajo.md#2-migraciones)). La migración en dos tiempos de la Fase 2 está **cerrada** y no se reabre: estas son migraciones nuevas, cada una con su `down()`. El reparto y el orden están en [plan-fase3.md](./plan-fase3.md) y [jhon.md](./jhon.md).

### 10.1 Lo que ya existe y no necesita migración

| RF | Ya está en el esquema |
|---|---|
| RF-05 Puestos de trabajo | `puestos_trabajo` (`nombre_o_codigo`, `piso_id`, `dependencia_id`; su `sede_id` se borró en el cierre de la Fase 2), `asignaciones.puesto_trabajo_id` y `equipos.puesto_trabajo_id`. El seeder siembra 25 puestos de ejemplo. Falta la pantalla, no la tabla (salvo los ajustes de la migración F3-1). |
| RF-16 Configuración en una fecha | `componentes.fecha_instalacion` y `componentes.fecha_retiro` (null = instalado hoy), y `eventos.valores` (JSON `antes`/`despues`) para los cambios de la configuración de cómputo. |
| RF-17 Destino de lo retirado | `cambios_componente.destino_retirado` (`bodega`, `otro_equipo`, `descarte`) y `cambios_componente.equipo_destino_id` (agregado en la Fase 2, todavía sin uso en pantalla). |
| RF-31 Pendientes de firma | `eventos.estado_firma` y `documentos.firmado`. |
| RF-42 Cargas posteriores | `equipos.serial` único, `equipos.codigo_activo` indexado, `eventos.valores` para registrar qué cambió, `importaciones` para la trazabilidad. |

### 10.2 Migraciones a crear, en este orden

| # | Migración | Qué hace | Habilita |
|---|---|---|---|
| **F3-1** | `ajustar_puestos_trabajo` | `puestos_trabajo` + `activo` (boolean, default `true`) y `unique(piso_id, nombre_o_codigo)`. **Decisión del líder:** la fuente de verdad del puesto de un equipo es la asignación abierta (`asignaciones.puesto_trabajo_id`), igual que el piso; `equipos.puesto_trabajo_id` es un resto de v3 que ningún código escribe hoy. Recomendación: borrarlo en esta misma migración (o dejarlo y documentarlo como no usado). | RF-05 (Juan José, Anuar) |
| **F3-2** | `create_fotos_equipo_table` | Tabla nueva `fotos_equipo` (ver 10.3). | RF-06 (Juan José) |
| **F3-3** | `create_tipos_evento_table` | Tabla nueva `tipos_evento` (ver 10.4), sembrada con los 8 tipos del sistema; `eventos.tipo` pasa de `enum` a `varchar(40)` con llave foránea a `tipos_evento.clave`, **conservando exactamente las mismas claves** para no tocar ningún evento existente. | RF-13 (Jhon, Manuel, Alex) |

### 10.3 `fotos_equipo` (RF-06)

| Columna | Tipo | Regla |
|---|---|---|
| `id` | bigint PK | |
| `equipo_id` | FK → equipos | `restrict`: la foto es parte de la hoja de vida y no se borra con el equipo (los equipos tampoco se borran). |
| `archivo_path` | varchar(255) | Ruta en el mismo disco privado de los documentos firmados (`FormatosPdf::DISCO`); nunca en `public/`. |
| `descripcion` | varchar(160) nullable | «Vista frontal», «placa con el serial»… |
| `tomada_en` | date nullable | Fecha de la foto (puede ser anterior a la carga). |
| `es_principal` | boolean, default `false` | La que se muestra en la ficha; como máximo una por equipo (lo valida el modelo, igual que la valoración vigente). |
| `subida_por` | FK → users | Autor (RF-11). |
| `created_at`, `updated_at` | timestamp | |

Índice: `equipo_id`. No es un `documento` porque los documentos cuelgan de un evento (`documentos.evento_id` es obligatorio) y la foto del equipo no; subir o retirar una foto sí deja un evento `actualizacion_datos` en el historial.

### 10.4 `tipos_evento` (RF-13)

| Columna | Tipo | Regla |
|---|---|---|
| `id` | bigint PK | |
| `clave` | varchar(40), único | La que se guarda en `eventos.tipo`. Las 8 del sistema: `alta`, `traslado_responsable`, `cambio_componente`, `diagnostico`, `valoracion`, `baja`, `actualizacion_datos`, `anulacion_aclaracion`. |
| `nombre` | varchar(80) | Etiqueta en pantalla («Traslado / cambio de responsable»). |
| `descripcion` | varchar(255) nullable | Para qué se usa. |
| `requiere_firma` | boolean, default `false` | Si es `true`, el evento nace «pendiente de firma» (RF-30, RN-05). |
| `es_sistema` | boolean, default `false` | Los 8 del sistema: su clave no se edita ni se desactiva, porque tienen efectos propios (traslado, baja, valoración…). |
| `activo` | boolean, default `true` | Un tipo configurable se desactiva, nunca se borra (los eventos lo referencian). |
| `created_at`, `updated_at` | timestamp | |

`eventos.tipo` → `varchar(40)` + `foreign('tipo')->references('clave')->on('tipos_evento')`. Los tipos configurables **no tienen efectos sobre el equipo**: solo registran un hecho con fecha, descripción, autor y adjuntos, y pasan por `HistorialService::registrar()` como todos.

### 10.5 Sin migración

RF-16, RF-17, RF-31 y RF-42 se resuelven con el esquema actual. Si en el camino aparece la necesidad de una columna, se le pide a Jhon el mismo día (no se agrega por cuenta propia).

---

**Ver también:** [Análisis de requerimientos v4](./analisis-requerimientos.md) · [Plan de Fase 3](./plan-fase3.md) · [Normas de trabajo](./normas-de-trabajo.md)
