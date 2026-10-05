# Análisis de requerimientos — Sistema de Hoja de Vida de Equipos

**Gobernación de Córdoba · Dirección TIC · Fase 1: Análisis · Versión 3**
Oct 2, 2026 · @JWRCompany

> Este es el documento de referencia permanente del proyecto: 35 requerimientos funcionales esenciales, modelo de dominio, reglas de negocio y casos de uso. Consúltalo aquí en vez de depender del Google Doc original.

---

## 1. Introducción

El sistema será una aplicación web independiente que lleve la hoja de vida de cada equipo tecnológico de la Gobernación de Córdoba, desde su registro hasta su baja, tomando como punto de partida el inventario de marzo de 2026 (461 registros, 930 equipos). Esta versión 3 incorpora los códigos QR en las hojas de vida, para llegar a la información de un equipo escaneando una etiqueta pegada en él. Este documento cierra la fase de análisis: fija qué debe hacer el sistema para que la fase de diseño parta de una base acordada.

### 1.1 Propósito

Responder, con evidencia, cuatro preguntas que hoy no tienen respuesta en un solo lugar:

1. Qué equipos tiene la entidad.
2. Dónde están y quién responde por ellos.
3. Qué cambios se les han hecho.
4. Cuáles salieron de servicio.

### 1.2 Alcance

**Incluido:**

- Registro y ficha de cada equipo (computadores, monitores, impresoras, escáneres, UPS, switches, access points, servidores, video beam), identificado por su serial y su código de activo.
- Historial de eventos por equipo: alta, traslado o cambio de responsable, cambio de componentes (agregar, cambiar o quitar RAM, disco, monitor, periféricos, etc.), diagnóstico y baja.
- Generación de los dos formatos que usa la Dirección TIC (formato de entrega y formato de baja) y carga de los documentos firmados.
- Código QR por equipo y etiquetas imprimibles: al escanear la etiqueta se abre la hoja de vida del equipo.
- Carga inicial del inventario 2026 y su depuración.
- Consultas y reportes sobre todo el parque tecnológico.
- Usuarios creados por un administrador; cada evento queda a nombre de quien lo registra.

**Excluido:**

- Mantenimiento preventivo y correctivo: es un procedimiento que se gestiona en otro sistema, fuera del control de la Dirección TIC. Solo los cambios de componentes que resulten de él se registran en la hoja de vida.
- Equipos personales de los funcionarios: no se registran ni se intervienen.
- El trámite administrativo o contable de la baja: la Dirección TIC deja la baja especificada en la hoja de vida; no recibe constancia de otra dependencia.
- Mesa de ayuda y tickets: el sistema no se conecta con osTicket ni con SIGSA-TIC.
- Inventario contable de Almacén: se usa su código de activo como referencia, pero el sistema no lo administra.
- Monitoreo de red y servidores.

### 1.3 Relación con otros documentos

El análisis SIGSA-TIC (v2.0, 04-09-2026) se usa como referencia de método, no como alcance. El formato «Hoja de vida / baja» v1.0 (02-09-2024) y el formato de entrega son las dos salidas oficiales del sistema, con libertad para mejorarlos. Los prototipos de interfaz se mantienen en Claude Design.

### 1.4 Control de versiones

| Versión | Cambios principales |
|---|---|
| 1 | Análisis inicial a partir del inventario 2026 y la primera reunión con el jefe. |
| 2 | Segunda revisión: administrador y usuarios, solo dos formatos (entrega y baja), traslado con dos documentos firmados, mantenimiento fuera del alcance, módulo de componentes, serial y código de activo como identificadores, baja sin constancia externa, equipos de terceros. |
| 3 | Se incorporan los códigos QR: RF-08 pasa de deseable a esencial y se agrega el módulo 5.10 (RF-48 a RF-51), dos requerimientos no funcionales, tres reglas de negocio, la entidad EtiquetaQR, dos casos de uso, un indicador en el reporte de calidad y tres preguntas abiertas. |

### 1.5 Definiciones

| Término | Significado en este documento |
|---|---|
| **Equipo** | Bien tecnológico individual con hoja de vida propia: un PC, un monitor, una impresora, una UPS, etc. |
| **Componente** | Parte interna (RAM, disco, procesador, tarjeta) o periférico (teclado, mouse, sonido, cámara) de un equipo de cómputo. Se registra dentro de la hoja de vida del equipo. |
| **Cambio de componente** | Agregar, cambiar o quitar un componente a un equipo. Forma parte de la hoja de vida, no del mantenimiento. |
| **Parque tecnológico** | El conjunto de todos los equipos tecnológicos de la entidad. |
| **Puesto de trabajo** | Conjunto de equipos que usa un funcionario en un lugar. Es la unidad con la que se levantó el inventario 2026 (una fila = un puesto). |
| **Serial** | Número de serie del fabricante. Identificador obligatorio y único de cada equipo. |
| **Código de activo** | Código de Almacén pegado en el equipo (formato `I1-######`). Identificador obligatorio cuando el equipo lo tiene. |
| **Código QR** | Código de barras bidimensional impreso en una etiqueta pegada al equipo. Contiene solo un enlace que abre su hoja de vida en el sistema. |
| **Etiqueta QR** | Adhesivo resistente con el código QR, el serial y el código de activo del equipo. |
| **Hoja de vida** | Ficha del equipo más todo su historial de eventos. |
| **Evento** | Hecho registrado sobre un equipo, con fecha, autor y soporte: traslado, cambio de componente, diagnóstico, baja, etc. |
| **Formato de entrega** | Documento que firma quien recibe un equipo. |
| **Formato de baja** | Documento que firma quien entrega un equipo, ya sea para pasarlo a otra persona o porque sale definitivamente de servicio. Incluye diagnóstico y recomendaciones del área de sistemas. |
| **Equipo de tercero** | Equipo que está en la entidad pero no es 100 % de la Gobernación (comodato, convenio, proveedor). Se registra indicando su propietario. |

---

## 2. Decisiones del levantamiento

Tras la segunda revisión quedaron resueltas 12 de las 14 preguntas; siguen abiertas la carga de hojas de vida antiguas (7) y si el traslado podrá hacerse con un solo documento (10). A ellas se suman tres decisiones nuevas: registrar todos los equipos existentes, no manejar equipos personales e incorporar códigos QR.

| # | Pregunta | Respuesta | Implicación para el sistema |
|---|---|---|---|
| 1 | Alojamiento y tecnologías | Se define después | El análisis no fija tecnología. RNF de portabilidad: debe poder instalarse en servidor propio o intranet. |
| 2 | Usuarios, roles y permisos | Un administrador crea los usuarios con su contraseña; los usuarios hacen todo lo demás | Dos perfiles: Administrador y Usuario. Cada evento queda registrado automáticamente a nombre del usuario que lo hizo. Los funcionarios responsables no ingresan; firman los formatos. |
| 3 | Firma de los funcionarios | Se necesita firmar | Cada movimiento genera su formato prellenado, se imprime, se firma y se sube escaneado al evento. La firma digital queda como mejora futura. |
| 4 | Fuente oficial de datos | El inventario 2026 es la base y tiene más información que el formato | El modelo de datos se construye a partir de las 53 columnas del inventario. |
| 5 | Identificación del equipo | Lo más importante son el serial y el código de activo | No se crea código interno visible. El serial es obligatorio y único; el código de activo es obligatorio cuando el equipo lo tiene. |
| 6 | Tipos de equipo | Todos (confirmado por el inventario) | 12 tipos iniciales; el catálogo de tipos es editable y el formulario de registro cambia según el tipo. |
| 7 | Carga inicial o migración de hojas antiguas | Sin respuesta | Supuesto: se carga el inventario 2026 como estado inicial y el historial empieza desde ahí. |
| 8 | Eventos a registrar | Ajustado en la revisión | Alta, traslado o cambio de responsable, cambio de componente, diagnóstico y baja. El mantenimiento no se registra. |
| 9 | Proceso de baja | La Dirección TIC emite el diagnóstico y lo especifica en la hoja de vida; no recibe constancia de nadie | Al registrar la baja con su formato firmado, el equipo queda «Dado de baja». No hay estado intermedio de espera. |
| 10 | Traslados | Se suben dos documentos: el formato de baja de quien entrega y el formato de entrega de quien recibe, cada uno con su firma | El traslado exige los dos documentos firmados. Se está averiguando si pueden unirse en uno solo; el sistema debe permitir ambas opciones. |
| 11 | Mantenimiento | Es un procedimiento aparte, fuera del alcance | No hay módulo de mantenimiento. Los cambios de componentes (agregar, cambiar, quitar) sí son parte de la hoja de vida. |
| 12 | Formatos | No hay código institucional; solo existen dos formatos: entrega y baja, que sirven para casi todo | El sistema genera esos dos formatos prellenados y los mejora; no se crean actas adicionales. |
| 13 | Reportes | Todos | Catálogo de reportes de la sección 11 con filtros combinables y exportación. |
| 14 | Datos sensibles | Aplica Ley 1581 de 2012 | La cédula se trata como dato personal: enmascarada en listados y con registro de quién la consulta o exporta. |
| — | Nuevo: registro de cada equipo | Hay que registrar todos los equipos | El sistema es también el inventario maestro: cada equipo existe en él aunque nunca haya tenido un evento. |
| — | Nuevo: propiedad | Hay equipos que no son 100 % de la Gobernación y se indican; los personales no se manejan | Los equipos de terceros se registran con su propietario; los personales no se registran ni se cargan desde el inventario. |
| — | Nuevo (v3): códigos QR | Incorporar QR a las hojas de vida de los PC para acceder más fácil a su información | Cada equipo tiene un QR con un enlace a su hoja de vida, impreso en una etiqueta pegada al equipo (módulo 5.10). Supuesto: se genera para todos los equipos, empezando por los PC, y solo lo abre el personal con sesión iniciada. |

---

## 3. Diagnóstico del inventario 2026

El inventario sirve como punto de partida, pero no puede cargarse tal cual: está organizado por puesto de trabajo y no por equipo, y 146 de sus 930 equipos (16 %) no tienen un código de activo utilizable. Se levantó con un formulario de Microsoft Forms entre el 16 y el 25 de marzo de 2026, por 83 personas distintas, en 11 sedes.

### 3.1 Estructura del archivo

Cada una de las 461 filas describe un puesto de trabajo y puede contener hasta seis equipos y tres periféricos. Las 53 columnas se agrupan así:

| Grupo | Columnas | Contenido |
|---|---|---|
| Metadatos del formulario | 6 | ID, hora de inicio y fin, correo (siempre «anonymous»), nombre, última modificación |
| Equipos (6 bloques de 4 campos) | 24 | Procesamiento, video, impresión, conectividad, digitalización y otros; cada bloque con tipo, marca, modelo y código de activo |
| Software | 2 | Versión de sistema operativo y antivirus |
| Periféricos (3 bloques) | 12 | Teclado, mouse y sonido: si tiene, estado, marca y serial |
| Estado | 2 | Estado de funcionamiento (4 opciones) y observaciones |
| Ubicación | 3 | Sede, piso y dependencia |
| Responsable | 3 | Nombre, cédula y tipo de vinculación (planta o contratista) |
| Levantamiento | 1 | Nombre de quien hizo el inventario |

Al separar los bloques, las 461 filas producen 930 equipos individuales. Palacio Naín concentra 263 puestos (57 %), seguido de Morindo (52) y Edificio Victoria Char (50).

> Inventario de la infraestructura tecnológica 2026 (1-461) · 930 equipos, 12 tipos. Los equipos de cómputo dominan, pero 187 equipos de impresión, energía, conectividad y digitalización también necesitan hoja de vida.

### 3.2 Problemas de calidad encontrados

| ID | Problema | Magnitud | Ejemplo | Consecuencia para el sistema |
|---|---|---|---|---|
| D-01 | Datos por puesto, no por equipo | 461 filas → 930 equipos | Una fila con PC, monitor e impresora | La carga debe dividir cada fila en varios equipos y conservar el puesto como agrupación. |
| D-02 | Equipos sin código de activo | 146 equipos (16 %) | «N/A», «Sin código», «No se ve», «No legible» | El equipo se identifica por serial y código de activo; los que no tengan código quedan marcados y se priorizan en la verificación en sitio. |
| D-03 | Formato de código inconsistente | 4 escrituras del mismo patrón; 11 con «L1» en vez de «I1»; números de 5 y 6 dígitos (425 y 266) | I1-25646, I1 24147, I125547, L1 25558, IU-25515 | Normalizar al cargar y validar el formato al registrar. Confirmar con Almacén si 25646 y 025646 son series distintas. |
| D-04 | Códigos de activo repetidos | 12 códigos repetidos en 24 equipos | PC y monitor con I1-25614; I1-23999 en dos puestos | En All in One el monitor es parte del PC (no es duplicado real); en los demás, revisar en sitio. |
| D-05 | Valores que no son código | Cerca de 45 | Seriales (VNB4513213, X2HJ044200) o frases | Separar código de activo y serial en campos distintos. |
| D-06 | No se capturó el serial del equipo principal | 930 equipos sin serial | Solo hay serial de teclado, mouse y sonido | Sin código ni serial, un equipo no puede identificarse con certeza. El serial pasa a ser obligatorio desde el registro. |
| D-07 | Texto libre sin normalizar | 175 variantes de dependencia; unas 25 de sistema operativo | «Secretaría de educación», «Sec Educación», «Educación»; «W10», «Windows 10» | Listas cerradas y administrables para dependencia, sede, piso, tipo, marca y sistema operativo. |
| D-08 | Responsable incompleto | 51 sin nombre, 199 sin cédula, 22 cédulas con texto | «No estaba funcionario» en el campo cédula | Permitir equipos sin responsable asignado como estado válido, y marcar los pendientes de completar. |
| D-09 | Estado por puesto, no por equipo | 30 puestos «No funcional, requiere ser dado de baja»; 34 «Malo, requiere cambio de componentes» | Un puesto malo puede tener un monitor bueno | El estado se registra por equipo. Los 64 puestos señalados son los primeros candidatos a diagnóstico. |
| D-10 | Equipos que no son de la entidad | Algunos casos | «No es de la gobernación», «es personal» | Los equipos de terceros se registran indicando su propietario; los personales se excluyen de la carga. |
| D-11 | Campos mezclados | Antivirus con Sí, No y nombres de producto | «Fortinet», «FortiEDR», «Si» | Separar «tiene antivirus» (sí/no) de «producto». |

Estas cifras salen de una revisión automática del archivo; la corrección final de cada equipo requiere verificación en sitio o con Almacén.

---

## 4. Usuarios y actores

El sistema tiene dos perfiles: un Administrador, que crea y gestiona a los usuarios, y los Usuarios del área de sistemas, que hacen todo el trabajo diario; cada evento queda a nombre de quien lo registró.

| Actor | ¿Ingresa al sistema? | Qué hace |
|---|---|---|
| **Administrador** | Sí | Crea, edita y desactiva usuarios y les asigna contraseña inicial; restablece contraseñas; mantiene las listas (sedes, dependencias, tipos, marcas). Además puede hacer todo lo que hace un usuario. |
| **Usuario** (ingeniero o técnico de soporte) | Sí, con usuario propio | Registra equipos, traslados, cambios de componentes, diagnósticos y bajas; genera los formatos; sube los documentos firmados; consulta y exporta reportes. |
| **Funcionario responsable del equipo** | No | Firma el formato de entrega cuando recibe un equipo y el formato de baja cuando lo entrega. |
| **Jefe de la Dirección TIC** | No | Recibe reportes. |
| **Dependencia que tramita la baja administrativa** | No | Puede recibir el listado de equipos dados de baja; no devuelve constancia al sistema. |
| **Almacén** | No | Fuente del código de activo; destinatario de conciliaciones. |

### 4.1 Valoración del esquema de administrador

El esquema propuesto (un administrador que crea los usuarios y usuarios que operan todo) es el adecuado para un equipo pequeño: es simple, evita cuentas compartidas y permite saber quién hizo cada cambio. Para que funcione bien se recomienda:

- Tener al menos dos personas con perfil de administrador, para no depender de una sola si falta o se va.
- Desactivar usuarios en lugar de borrarlos, para que el historial conserve siempre el nombre del autor.
- Obligar al usuario a cambiar la contraseña asignada en su primer ingreso; el administrador no debe conocer las contraseñas definitivas.
- Que ni el administrador pueda editar o borrar eventos: solo anularlos con un evento justificado.

---

## 5. Requerimientos funcionales

Son 51 requerimientos en 10 módulos: **35 esenciales** (van en la primera versión), 15 importantes y 1 deseable. La versión 2 eliminó el código interno y el módulo de mantenimiento y agregó el de componentes; la versión 3 sube el QR (RF-08) a esencial y agrega el módulo 5.10 de códigos QR.

### 5.1 Inventario maestro de equipos

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-01 | Registrar cualquier equipo de la Gobernación o de terceros, de los tipos del catálogo, con los campos de la sección 8, aunque aún no tenga responsable ni eventos. Los equipos personales no se registran. | Esencial |
| RF-02 | Identificar cada equipo por su serial (obligatorio y único) y su código de activo (obligatorio cuando el equipo lo tiene, formato I1-######), avisando si alguno ya existe en otro equipo y marcando los equipos sin código de activo. | Esencial |
| RF-03 | Mostrar campos distintos según el tipo de equipo (por ejemplo, sistema operativo y antivirus solo para equipos de cómputo). | Esencial |
| RF-04 | Registrar la propiedad: Gobernación o tercero (comodato, convenio, proveedor), con el nombre del propietario cuando es de tercero. | Esencial |
| RF-05 | Agrupar equipos en un puesto de trabajo (PC, monitor, impresora, UPS) y trasladar el puesto completo o un equipo suelto. | Importante |
| RF-06 | Adjuntar fotografías al equipo (chasis, etiqueta, serial). | Importante |
| RF-07 | Buscar un equipo por serial, código de activo, responsable, cédula, dependencia o sede, tolerando espacios y guiones (I1 24147 = I1-24147). | Esencial |

> El antiguo RF-08 (etiqueta con QR) pasa al módulo 5.10, ahora como esencial.

### 5.2 Hoja de vida e historial

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-09 | Mostrar en una sola vista la ficha del equipo, su configuración y componentes actuales, su responsable y ubicación, y su historial completo en orden cronológico. | Esencial |
| RF-10 | Registrar eventos sobre un equipo con tipo, fecha, descripción y adjuntos. Tipos iniciales: alta, traslado o cambio de responsable, cambio de componente, diagnóstico, baja, actualización de datos. | Esencial |
| RF-11 | Asignar como autor de cada evento, de forma automática, al usuario que inició sesión, sin posibilidad de cambiarlo. | Esencial |
| RF-12 | Impedir la edición o eliminación de eventos; una corrección se registra como un evento de anulación o aclaración. Registrar automáticamente un evento cuando cambian responsable, ubicación o estado, con valor anterior y nuevo. | Esencial |
| RF-13 | Permitir configurar nuevos tipos de evento sin desarrollo. | Importante |

### 5.3 Componentes y cambios de configuración

Reemplaza al módulo de mantenimiento de la versión 1: el mantenimiento se gestiona fuera del sistema, pero todo lo que se agrega, cambia o quita a un equipo es parte de su hoja de vida.

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-14 | Registrar los componentes de cada equipo de cómputo (RAM, disco, procesador, tarjeta de red o video, teclado, mouse, sonido, cámara) con tipo, capacidad o característica, marca, serial y estado. | Esencial |
| RF-15 | Registrar un cambio de componente (agregar, cambiar o quitar) con fecha, motivo, componente retirado y componente instalado con sus seriales; el cambio queda como evento y actualiza la configuración actual del equipo. Si el elemento cambiado es a su vez un equipo con hoja de vida propia (por ejemplo, un monitor), el cambio se registra en ambas hojas. | Esencial |
| RF-16 | Consultar el historial de cada componente y la configuración que tenía el equipo en una fecha dada. | Importante |
| RF-17 | Registrar el destino del componente retirado: bodega, otro equipo (con su serial) o descarte. | Importante |

### 5.4 Responsables, ubicación y traslados

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-18 | Asignar un responsable a cada equipo con nombre, cédula, cargo, dependencia y tipo de vinculación (planta o contratista). | Esencial |
| RF-19 | Registrar la ubicación con sede, piso y dependencia, tomados de listas administrables. | Esencial |
| RF-20 | Registrar un traslado o cambio de responsable con origen, destino, fecha y motivo, generando los dos formatos prellenados (baja de quien entrega y entrega de quien recibe) y exigiendo subir ambos firmados. Si la Dirección TIC aprueba un documento único, el sistema debe aceptarlo en su lugar. | Esencial |
| RF-21 | Permitir que un equipo quede sin responsable (en bodega) como estado válido y listarlo. | Esencial |
| RF-22 | Consultar todos los equipos a cargo de una persona y generar su formato de entrega consolidado. | Esencial |
| RF-23 | Generar el paz y salvo o listado de devolución cuando un funcionario se retira. | Importante |

### 5.5 Diagnóstico y baja

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-24 | Registrar un diagnóstico técnico con estado encontrado, causa, recomendación y evidencia. | Esencial |
| RF-25 | Registrar la baja del equipo con diagnóstico, motivo (obsolescencia, daño irreparable, pérdida), recomendaciones del área de sistemas y formato de baja firmado; el equipo pasa a «Dado de baja» y queda especificado en su hoja de vida. No se espera constancia de otra dependencia. | Esencial |
| RF-26 | Anular una baja registrada por error mediante un evento justificado, devolviendo el equipo a su estado anterior. | Importante |
| RF-27 | Generar el listado de equipos dados de baja en un período, por si se necesita entregar a la dependencia que tramita la baja administrativa. | Importante |

### 5.6 Formatos y firmas

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-28 | Generar en PDF el formato de entrega prellenado: datos del equipo, componentes, responsable que recibe, fecha y espacios de firma. | Esencial |
| RF-29 | Generar en PDF el formato de baja prellenado: datos del equipo, responsable que entrega, diagnóstico, recomendaciones y espacios de firma del ingeniero de soporte y del funcionario. | Esencial |
| RF-30 | Subir al evento los documentos firmados; mientras falte alguno, el evento queda «Pendiente de firma». | Esencial |
| RF-31 | Listar los eventos pendientes de firma. | Importante |
| RF-32 | Capturar la firma en pantalla (tableta o celular) en lugar de imprimir, si se aprueba. | Deseable |
| RF-33 | Exportar la hoja de vida completa (ficha, componentes e historial) como reporte imprimible, sin firma. | Importante |

### 5.7 Consultas y reportes

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-34 | Ofrecer el catálogo de reportes de la sección 11 con filtros combinables por sede, piso, dependencia, tipo, marca, estado, propiedad, responsable, vinculación y rango de fechas. | Esencial |
| RF-35 | Exportar cualquier listado o reporte a Excel y PDF. | Esencial |
| RF-36 | Mostrar un tablero con totales por tipo, estado, sede y dependencia. | Importante |
| RF-37 | Mostrar indicadores de calidad del inventario: equipos sin serial, sin código de activo, sin responsable, con código repetido y pendientes de verificar. | Esencial |

### 5.8 Carga inicial y depuración

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-38 | Importar el inventario 2026 desde Excel, dividiendo cada fila en equipos y componentes, conservando el número de fila de origen y excluyendo los equipos personales. | Esencial |
| RF-39 | Normalizar al importar: códigos de activo, dependencias, sedes, pisos, sistemas operativos y marcas, con tabla de equivalencias editable. | Esencial |
| RF-40 | Mostrar una vista previa con errores y advertencias por fila antes de confirmar la carga. | Esencial |
| RF-41 | Marcar como «Pendiente de verificar» los equipos importados sin serial o con datos dudosos, y permitir confirmarlos en sitio. | Esencial |
| RF-42 | Permitir cargas posteriores desde Excel para nuevos levantamientos o equipos nuevos. | Importante |

### 5.9 Usuarios y administración

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-43 | Manejar dos perfiles: Administrador y Usuario. Ambos operan todo el sistema; solo el Administrador gestiona usuarios y listas. | Esencial |
| RF-44 | Permitir al Administrador crear, editar y desactivar usuarios y asignar una contraseña inicial que el usuario debe cambiar en su primer ingreso. Los usuarios no se borran. | Esencial |
| RF-45 | Permitir al Administrador mantener las listas: sedes, pisos, dependencias, tipos de equipo, tipos de componente, marcas, sistemas operativos, tipos de evento, motivos de baja. | Esencial |
| RF-46 | Registrar una bitácora de auditoría de inicios de sesión, cambios, consultas y exportaciones de cédulas. | Esencial |
| RF-47 | Permitir al Administrador restablecer la contraseña de un usuario. | Importante |

### 5.10 Códigos QR y etiquetas (nuevo en v3)

El QR lleva a la hoja de vida del equipo sin tener que buscarlo: el técnico escanea la etiqueta con el celular y ve la ficha, los componentes, el responsable y el historial. El QR contiene solo un enlace; la información siempre se lee del sistema, así que nunca queda desactualizada.

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-08 | Generar automáticamente un código QR para cada equipo al registrarlo, con un enlace permanente a su hoja de vida. | Esencial |
| RF-48 | Imprimir la etiqueta QR de un equipo desde su hoja de vida, con el QR, el serial, el código de activo, el tipo y el texto «Gobernación de Córdoba · Dirección TIC». | Esencial |
| RF-49 | Imprimir etiquetas QR por lotes, filtrando por sede, piso, dependencia o tipo, o seleccionando equipos de un listado, en hojas de etiquetas adhesivas. | Importante |
| RF-50 | Al escanear el QR, abrir la hoja de vida del equipo. Si el usuario no tiene sesión iniciada, pedir el ingreso y después abrir directamente ese equipo. | Esencial |
| RF-51 | Reimprimir la etiqueta de un equipo (por daño o pérdida) con el mismo QR, registrando quién la reimprimió y cuándo. | Importante |

---

## 6. Requerimientos no funcionales

Son 18 condiciones de calidad, cada una con la forma de verificarla en la fase de pruebas; la tecnología se decide después, por eso se exige portabilidad.

| ID | Requerimiento | Criterio de verificación |
|---|---|---|
| RNF-01 | Aplicación web usable en computador, tableta y celular; el registro de eventos, la consulta de la hoja de vida y la apertura por QR deben funcionar en celular para trabajo en sitio. | Prueba en dispositivos reales durante una visita técnica. |
| RNF-02 | Interfaz en español, con la terminología de la entidad y mensajes de error que digan qué corregir. | Revisión de textos por la Dirección TIC. |
| RNF-03 | Registrar un evento común (traslado, cambio de componente) en menos de 2 minutos y en no más de 3 pantallas. | Prueba cronometrada con 3 técnicos. |
| RNF-04 | Soportar al menos 5.000 equipos con su historial y responder búsquedas y listados en menos de 2 segundos. | Prueba con datos sintéticos. |
| RNF-05 | Instalable en servidor propio o intranet, sin depender de servicios externos de pago; despliegue documentado y reproducible. | Instalación en un equipo limpio siguiendo el manual. |
| RNF-06 | Base de datos relacional con integridad referencial y unicidad garantizada para serial y código de activo. | Revisión del modelo físico. |
| RNF-07 | Acceso con usuario y contraseña personal creados por el Administrador, con cambio obligatorio en el primer ingreso; contraseñas guardadas con función de derivación segura; cierre de sesión por inactividad. | Revisión de código y prueba. |
| RNF-08 | Comunicación cifrada (HTTPS) y acceso solo desde la red interna o VPN. | Verificación de configuración. |
| RNF-09 | Tratamiento de cédulas conforme a la Ley 1581 de 2012: enmascaradas en listados y exportaciones masivas, completas solo en la ficha y en los formatos, y registro de cada consulta o exportación. | Prueba funcional y revisión de bitácora. |
| RNF-10 | Bitácora de auditoría inmutable y conservada mínimo 5 años. | Intento de modificación fallido. |
| RNF-11 | Copia de respaldo diaria de base de datos y adjuntos en ubicación separada; pérdida máxima de 24 horas. | Restauración de prueba documentada. |
| RNF-12 | Adjuntos en PDF, JPG o PNG, con tamaño máximo configurable (por defecto 10 MB). | Prueba de carga de archivos. |
| RNF-13 | PDF generados en tamaño carta, legibles en blanco y negro y con espacios de firma suficientes. | Impresión de muestra. |
| RNF-14 | Formatos colombianos: fecha DD/MM/AAAA, moneda COP, zona horaria de Colombia. | Revisión funcional. |
| RNF-15 | Código fuente en repositorio de la entidad con control de versiones; propiedad de la Gobernación. | Verificación del repositorio. |
| RNF-16 | Entrega con manual de usuario, manual de instalación y documentación del modelo de datos, para que otra persona pueda sostenerlo. | Revisión de entregables. |
| RNF-17 | Etiqueta QR en material resistente (poliéster o vinilo, no papel), de mínimo 5 × 3 cm, con el QR de al menos 2 × 2 cm, legible por la cámara de un celular común a 20 cm. | Prueba de lectura con tres celulares distintos sobre etiquetas pegadas en equipos. |
| RNF-18 | El enlace del QR debe usar una ruta corta y estable (por ejemplo, `/e/[identificador]`) para que, si cambia el dominio o el servidor, se redirija sin reimprimir las etiquetas. | Prueba de redirección tras cambiar el dominio en el ambiente de pruebas. |

---

## 7. Reglas de negocio

Estas 19 reglas son lo que el sistema no debe permitir; varias existen para que no se repitan los problemas de calidad de la sección 3.

| ID | Regla | Origen |
|---|---|---|
| RN-01 | Todo equipo de la Gobernación o de terceros que esté en la entidad debe estar registrado, aunque no tenga responsable ni eventos. Los equipos personales no se registran. | Decisión nueva |
| RN-02 | El serial es obligatorio y único en todo el inventario, y nunca se reutiliza, ni siquiera tras la baja. Los equipos importados sin serial quedan «Pendiente de verificar» hasta completarlo. | Pregunta 5, D-06 |
| RN-03 | El código de activo es obligatorio cuando el equipo lo tiene y debe cumplir el formato I1-######. Si ya existe en otro equipo, el sistema exige justificación; solo un All in One puede compartirlo con su pantalla integrada. | Pregunta 5, D-03, D-04 |
| RN-04 | Un equipo tiene como máximo un responsable a la vez. | — |
| RN-05 | Todo traslado o cambio de responsable exige dos documentos firmados: el formato de baja de quien entrega y el formato de entrega de quien recibe (o el documento único, si se aprueba). Sin ellos, el evento queda «Pendiente de firma». | Pregunta 10 |
| RN-06 | Los eventos son inmutables y su autor es siempre el usuario que inició sesión; no se puede registrar un evento a nombre de otro. Un error se corrige con un evento de anulación o aclaración. | Pregunta 2 |
| RN-07 | La configuración de un equipo solo cambia mediante un evento de cambio de componente, que registra el serial de lo retirado y de lo instalado. | Pregunta 11 |
| RN-08 | El mantenimiento preventivo y correctivo no se registra en este sistema; solo sus efectos sobre los componentes, como cambio de componente. | Pregunta 11 |
| RN-09 | La baja la registra la Dirección TIC con diagnóstico y formato de baja firmado, y queda especificada en la hoja de vida; no se espera constancia externa. | Pregunta 9 |
| RN-10 | Un equipo «Dado de baja» conserva su hoja de vida y no admite nuevos eventos, salvo una anulación justificada o una aclaración. | — |
| RN-11 | Sede, piso, dependencia, tipo, marca, tipo de componente y sistema operativo solo se eligen de listas; no se acepta texto libre en esos campos. | D-07 |
| RN-12 | La cédula se muestra enmascarada (****6226) en listados y exportaciones masivas; completa solo en la ficha y en los formatos para firma, y cada consulta queda en la bitácora. | Pregunta 14 |
| RN-13 | La fecha de un evento no puede ser futura ni anterior al alta del equipo (salvo eventos históricos marcados como tales). | — |
| RN-14 | El estado de funcionamiento se registra por equipo, no por puesto de trabajo. | D-09 |
| RN-15 | Solo el Administrador crea, edita, desactiva usuarios y restablece contraseñas; los usuarios nunca se eliminan. | Pregunta 2 |
| RN-16 | Un equipo de tercero se registra con el nombre de su propietario. | D-10 |
| RN-17 | El QR contiene únicamente el enlace a la hoja de vida con un identificador del equipo; nunca guarda datos del equipo ni datos personales. | v3 |
| RN-18 | La hoja de vida que abre el QR solo se muestra a usuarios con sesión iniciada; quien escanee sin cuenta ve la pantalla de ingreso. | v3 (supuesto, pregunta 10 de la sección 14) |
| RN-19 | El identificador del QR es permanente: no cambia aunque se corrija el serial o cambien el código de activo, el responsable o la ubicación. Un equipo dado de baja conserva su QR, que sigue mostrando su hoja de vida. | v3 |

---

## 8. Modelo de dominio y diccionario de datos

El eje del modelo es el **Equipo**, identificado por serial y código de activo: todo lo demás (componentes, responsable, ubicación, eventos, documentos) cuelga de él con historia, de modo que siempre se pueda reconstruir quién lo tuvo, dónde estuvo y cómo cambió. Es un modelo conceptual; tipos de dato físicos e índices se definen en diseño.

### 8.1 Entidades

| Entidad | Qué representa | Atributos clave | Relación principal |
|---|---|---|---|
| **Equipo** | Bien tecnológico con hoja de vida | Serial, código de activo, identificador del QR (interno, no visible), tipo, marca, modelo, propiedad, propietario (si es de tercero), estado, verificación | Centro del modelo |
| **TipoEquipo** | Catálogo de tipos | Nombre, familia (cómputo, video, impresión, conectividad, digitalización, energía, proyección), campos aplicables | 1 tipo → muchos equipos |
| **Marca** | Catálogo de marcas | Nombre | 1 marca → muchos equipos |
| **ConfiguracionComputo** | Software de equipos de cómputo | Sistema operativo, licencia, antivirus (sí/no + producto), nombre de red | 1 a 1 con Equipo de cómputo |
| **Componente** | Parte interna o periférico | Tipo (RAM, disco, procesador, teclado, mouse, sonido, cámara…), capacidad o característica, marca, serial, estado, fechas de instalación y retiro | Muchos por equipo, con histórico |
| **TipoComponente** | Catálogo de tipos de componente | Nombre, si es interno o periférico | 1 tipo → muchos componentes |
| **PuestoTrabajo** | Agrupación de equipos en un sitio | Nombre o código, ubicación | 1 puesto → muchos equipos |
| **Persona** | Responsable de equipos | Nombre, cédula (dato personal), cargo, dependencia, vinculación, estado | 1 persona → muchos equipos |
| **Sede / Piso / Dependencia** | Ubicación física y organizacional | Nombre normalizado | Listas administrables |
| **Asignacion** | Histórico de responsable y ubicación | Equipo, persona, sede, piso, dependencia, fecha inicio, fecha fin, evento que la originó | La asignación abierta es la actual |
| **Evento** | Hecho en la hoja de vida | Consecutivo, tipo, fecha, descripción, usuario autor, estado de firma, evento anulado (si aplica) | Muchos por equipo |
| **CambioComponente** | Detalle de un evento de cambio | Acción (agregar, cambiar, quitar), componente retirado y su serial, componente instalado y su serial, motivo, destino de lo retirado | Extiende Evento |
| **Diagnostico** | Detalle de un diagnóstico o baja | Estado encontrado, causa, recomendaciones, ¿es baja?, motivo de baja | Extiende Evento |
| **Documento** | Archivo generado o adjunto | Tipo (formato de entrega, formato de baja, foto, otro), consecutivo, archivo, firmado sí/no, fecha | Muchos por evento |
| **EtiquetaQR** (nueva en v3) | Cada impresión de la etiqueta de un equipo | Equipo, fecha de impresión, usuario, motivo (primera impresión o reposición), lote | 1 equipo → muchas impresiones |
| **Usuario** | Persona del área de sistemas que usa el sistema | Nombre, usuario, perfil (Administrador o Usuario), activo, debe cambiar contraseña | Autor de eventos |
| **Auditoria** | Traza de operaciones | Usuario, acción, entidad, valor anterior y nuevo, fecha, IP | Inmutable |
| **Importacion** | Registro de cada carga desde Excel | Archivo, fecha, usuario, filas, errores; cada equipo guarda su fila de origen | 1 importación → muchos equipos |

### 8.2 Diccionario del Equipo con origen en el inventario

| Campo | Obligatorio | Origen en el inventario 2026 | Observación |
|---|---|---|---|
| Serial | Sí | No existe para el equipo principal | Identificador único. Se completa en la verificación en sitio. El QR no usa el serial (podría corregirse), sino un identificador interno permanente que genera el sistema. |
| Código de activo (Almacén) | Sí, si el equipo lo tiene | «Código activo …» de cada bloque | Normalizado a I1-######. |
| Tipo | Sí | «Dispositivo de procesamiento / video / impresión / conectividad / digitalización / Otros» | 12 tipos iniciales. |
| Marca | Sí | «Marca …» de cada bloque | Normalizar (Hp, HP → HP). |
| Modelo | No | «Modelo …» de cada bloque | Texto. |
| Propiedad | Sí | Inferida de observaciones | Gobernación o tercero; los personales no se cargan. |
| Propietario | Si es de tercero | Observaciones | Nombre de la entidad o empresa dueña. |
| Estado de funcionamiento | Sí | «Describa el estado de funcionamiento actual» (por puesto) | Se copia a cada equipo del puesto y queda por verificar. |
| Estado del ciclo de vida | Sí | No existe | En servicio, Sin asignar o Dado de baja (sección 9). |
| Sistema operativo | Solo cómputo | «Versión Sistema operativo» | Lista normalizada (Windows 7, 8, 10, 11 y edición). |
| Antivirus | Solo cómputo | «El dispositivo cuenta con antivirus» | Separar sí/no y producto. |
| Componentes | Solo cómputo | 12 columnas de teclado, mouse y sonido | Se crean como Componente; RAM, disco y procesador se completan en sitio. |
| Sede, piso | Sí | «Sede…», «Piso…» | Listas: 11 sedes, pisos 1 a 8. |
| Dependencia | Sí | «Dependencia responsable del equipo» | De 175 variantes a una lista oficial. |
| Responsable | No | «Nombre del responsable» | Puede quedar vacío (sin asignar). |
| Cédula | Si hay responsable | «Numero de Cedula del responsable» | Dato personal; solo dígitos. |
| Tipo de vinculación | Si hay responsable | «Tipo de Vinculacion» | Planta o contratista. |
| Observaciones | No | «Describa las Observaciones adicionales» | Texto. |
| Levantado por y fecha | Sí (importación) | «Nombre de quien realiza el inventario», «Hora de finalización» | Queda como evento de alta histórico. |

> No se migran: correo (siempre «anonymous»), nombre y hora de última modificación del formulario (vacíos).

---

## 9. Estados del equipo y tipos de evento

Después del alta, un equipo solo tiene tres estados: **En servicio**, **Sin asignar** (bodega) y **Dado de baja**. Se elimina el estado «En reparación» porque el mantenimiento queda fuera del sistema, y se elimina «Recomendado para baja» porque la Dirección TIC no recibe constancia de otra dependencia: la baja queda registrada al subir el diagnóstico y el formato firmado.

> Ciclo de vida del equipo · 3 estados tras el alta.

Aparte del estado, cada equipo lleva una marca de verificación (Verificado o Pendiente de verificar) que indica si su serial y sus datos se confirmaron en sitio.

| Tipo de evento | Efecto en el estado | Documentos firmados | Datos propios |
|---|---|---|---|
| Alta | Crea el equipo «En servicio» (con responsable) o «Sin asignar» | Formato de entrega, si se entrega a alguien | Origen (compra, donación, tercero, importación 2026), fecha |
| Traslado o cambio de responsable | Ninguno, o «En servicio» ↔ «Sin asignar» si queda o deja de estar sin responsable | Formato de baja de quien entrega y formato de entrega de quien recibe | Origen, destino, motivo |
| Cambio de componente | Ninguno; actualiza la configuración | No | Acción (agregar, cambiar, quitar), seriales retirado e instalado, motivo, destino de lo retirado |
| Diagnóstico | Ninguno | Según el caso | Estado encontrado, causa, recomendaciones |
| Baja | → «Dado de baja» | Formato de baja | Diagnóstico, motivo, recomendaciones |
| Actualización de datos | Ninguno | No | Campo, valor anterior y nuevo |
| Anulación o aclaración | Revierte el efecto del evento anulado | No | Evento referenciado y motivo |

---

## 10. Casos de uso

Se identifican 15 casos de uso; los ejecuta cualquier usuario, salvo CU-12 y CU-13, que son del Administrador. Se detallan los siete que fijan el comportamiento crítico y el resto se especifica en diseño.

| ID | Caso de uso | Actor | Requerimientos |
|---|---|---|---|
| CU-01 | Importar el inventario desde Excel | Usuario | RF-38 a RF-42 |
| CU-02 | Verificar en sitio un equipo importado (puede iniciar escaneando el QR) | Usuario | RF-02, RF-41, RF-50 |
| CU-03 | Registrar un equipo nuevo | Usuario | RF-01 a RF-04, RF-08, RF-18, RF-19 |
| CU-04 | Consultar la hoja de vida de un equipo | Usuario | RF-07, RF-09 |
| CU-05 | Registrar traslado o cambio de responsable | Usuario | RF-20, RF-28 a RF-30 |
| CU-06 | Registrar un cambio de componente | Usuario | RF-14 a RF-17 |
| CU-07 | Registrar un diagnóstico | Usuario | RF-24 |
| CU-08 | Registrar la baja de un equipo | Usuario | RF-25 a RF-27, RF-29 |
| CU-09 | Subir documentos firmados | Usuario | RF-30, RF-31 |
| CU-10 | Generar formatos y hoja de vida en PDF | Usuario | RF-28, RF-29, RF-33 |
| CU-11 | Consultar y exportar reportes | Usuario | RF-34 a RF-37 |
| CU-12 | Administrar usuarios | Administrador | RF-43, RF-44, RF-47 |
| CU-13 | Administrar listas | Administrador | RF-45 |
| CU-14 | Generar e imprimir etiquetas QR (nuevo en v3) | Usuario | RF-08, RF-48, RF-49, RF-51 |
| CU-15 | Escanear el QR y consultar la hoja de vida (nuevo en v3) | Usuario | RF-50, RF-09 |

### 10.1 CU-01 · Importar el inventario desde Excel

**Precondición:** el archivo tiene la estructura de 53 columnas del inventario 2026.

**Flujo:**
1. El usuario sube el archivo.
2. El sistema divide cada fila en equipos y componentes y descarta los equipos marcados como personales.
3. Normaliza códigos, dependencias, sedes, pisos, marcas y sistemas operativos con la tabla de equivalencias.
4. Muestra la vista previa: equipos a crear, errores (sin tipo, sin sede) y advertencias (sin código, código repetido, sin cédula).
5. El usuario corrige equivalencias o confirma.
6. El sistema crea los equipos con un evento de alta histórico (fecha e inventariador) y los marca «Pendiente de verificar», porque el inventario no trae el serial del equipo principal.

**Postcondición:** los equipos existen con su fila de origen y un reporte de la importación.

### 10.2 CU-05 · Registrar traslado o cambio de responsable

**Precondición:** el equipo existe y no está dado de baja.

**Flujo:**
1. Buscar el equipo por serial, código de activo o responsable.
2. Elegir «Trasladar / reasignar».
3. Indicar nuevo responsable, sede, piso, dependencia, fecha y motivo (el sistema muestra los datos actuales).
4. Guardar.
5. El sistema cierra la asignación anterior, abre la nueva, crea el evento a nombre del usuario y genera dos PDF prellenados: formato de baja (quien entrega) y formato de entrega (quien recibe).
6. El evento queda «Pendiente de firma».
7. Al subir los dos documentos firmados, queda completo.

**Alternativo:** trasladar el puesto completo; se aplica a todos sus equipos con un juego de formatos.
**Alternativo:** si se aprueba un documento único, se sube ese solo.

### 10.3 CU-06 · Registrar un cambio de componente

**Precondición:** el equipo existe y no está dado de baja.

**Flujo:**
1. Abrir la hoja de vida.
2. Elegir «Cambio de componente».
3. Seleccionar la acción: agregar, cambiar o quitar.
4. Indicar el componente retirado (de la configuración actual) y el instalado, con tipo, capacidad, marca y serial.
5. Registrar motivo y destino de lo retirado.
6. Guardar.
7. El sistema crea el evento a nombre del usuario y actualiza la configuración del equipo.

**Alternativo:** el elemento cambiado es un monitor con hoja de vida propia; el sistema registra el evento en ambas hojas y actualiza el puesto de trabajo.

### 10.4 CU-08 · Registrar la baja de un equipo

**Precondición:** el equipo es de la Gobernación y no está dado de baja.

**Flujo:**
1. Abrir la hoja de vida.
2. Elegir «Baja».
3. Registrar estado encontrado, motivo, diagnóstico, recomendaciones y evidencia.
4. El sistema genera el formato de baja prellenado.
5. Se sube el formato firmado.
6. El equipo pasa a «Dado de baja» y la baja queda especificada en su hoja de vida.

**Alternativo:** la baja se registró por error; un usuario la anula con un evento justificado y el equipo vuelve a su estado anterior.

### 10.5 CU-12 · Administrar usuarios

**Actor:** Administrador.

**Flujo:**
1. Entrar a «Usuarios».
2. Crear un usuario con nombre, usuario, perfil y contraseña inicial.
3. El usuario ingresa y el sistema le obliga a cambiar la contraseña.
4. El Administrador puede editar datos, restablecer la contraseña o desactivar al usuario.

**Regla:** un usuario desactivado no puede ingresar, pero su nombre se conserva en todos los eventos que registró.

### 10.6 CU-14 · Generar e imprimir etiquetas QR

**Precondición:** el equipo existe (el QR se genera automáticamente al registrarlo o importarlo).

**Flujo individual:**
1. Abrir la hoja de vida.
2. Elegir «Imprimir etiqueta QR».
3. El sistema muestra la vista previa con el QR, el serial, el código de activo y el tipo.
4. Imprimir.

**Flujo por lotes:**
1. Entrar a «Etiquetas QR».
2. Filtrar por sede, piso, dependencia o tipo (por ejemplo, los PC de Palacio Naín, piso 5), o marcar equipos en un listado.
3. El sistema arma la hoja de etiquetas.
4. Imprimir y registrar el lote.

**Alternativo:** la etiqueta se dañó o se perdió; se reimprime con el mismo QR y queda registrado quién y cuándo.

### 10.7 CU-15 · Escanear el QR y consultar la hoja de vida

**Actor:** Usuario (personal TIC) con su celular.

**Flujo:**
1. Escanear la etiqueta con la cámara del celular.
2. El navegador abre el enlace del equipo.
3. Si el usuario no tiene sesión, el sistema pide ingresar.
4. Se abre directamente la hoja de vida del equipo, con sus acciones (trasladar, cambio de componente, diagnóstico, baja).

**Excepción:** quien escanee sin cuenta del sistema solo ve la pantalla de ingreso; no se muestra ningún dato del equipo.
**Alternativo:** el equipo está dado de baja; la hoja de vida se muestra igual, con su estado y su historial.

---

## 11. Reportes y salidas

El sistema produce dos formatos para firma (entrega y baja), que cubren casi todos los movimientos, y 15 reportes con filtros combinables y exportación a Excel y PDF; en diseño se priorizan con el jefe. Además, desde la versión 3 el sistema imprime etiquetas QR (sección 5.10), que no llevan firma.

### 11.1 Formatos para firma

| Formato | Cuándo se usa | Contenido | Firmas |
|---|---|---|---|
| **Formato de entrega** | Alta con entrega, asignación, traslado (lado de quien recibe) | Datos del equipo (serial, código de activo, tipo, marca, modelo), componentes, responsable que recibe, ubicación, fecha | Quien recibe y el área de sistemas |
| **Formato de baja** (v1.0 mejorado) | Traslado (lado de quien entrega), retiro del responsable, baja definitiva | Fecha de revisión, datos del equipo, responsable y cargo, diagnóstico, recomendaciones del área de sistemas | Ingeniero de soporte técnico y funcionario responsable |

La hoja de vida completa (ficha, componentes e historial) se exporta como reporte, sin firma.

### 11.2 Reportes

| Reporte | Pregunta que responde |
|---|---|
| Inventario general | ¿Qué equipos hay, con todos sus datos? |
| Equipos por dependencia | ¿Qué tiene cada dependencia? |
| Equipos por sede y piso | ¿Qué hay en cada edificio? |
| Equipos por funcionario | ¿Qué tiene a cargo cada persona (planta o contratista)? |
| Equipos por tipo, marca y modelo | ¿Cómo está compuesto el parque? |
| Equipos por estado | ¿Cuántos en servicio, sin asignar y dados de baja? |
| Equipos de terceros | ¿Qué equipos no son de la Gobernación y de quién son? |
| Equipos dados de baja | ¿Qué salió de servicio, cuándo y por qué? |
| Obsolescencia por sistema operativo | ¿Cuántos equipos siguen con Windows 7 u 8? |
| Equipos sin antivirus | ¿Qué equipos no están protegidos? |
| Historial por equipo | ¿Qué se le ha hecho a este equipo? |
| Cambios de componentes en un período | ¿Qué se agregó, cambió o quitó, y a qué equipos? |
| Traslados en un período | ¿Qué se movió y a dónde? |
| Pendientes de firma | ¿Qué movimientos aún no tienen sus documentos firmados? |
| Calidad del inventario | ¿Cuántos sin serial, sin código, sin responsable, con código repetido o pendientes de verificar? Incluye los equipos que aún no tienen etiqueta QR impresa (nuevo en v3). |

---

## 12. Carga inicial y depuración del inventario

El inventario 2026 se carga completo desde el primer día y se depura después en el sistema, no antes en Excel: así ningún equipo queda por fuera y cada corrección deja rastro. Como el inventario no trae el serial del equipo principal, todos los equipos entran «Pendiente de verificar» hasta que se tome en sitio.

1. Respaldar el archivo original sin modificarlo; es la evidencia del levantamiento de marzo de 2026.
2. Construir las tablas de equivalencias: 175 variantes de dependencia → lista oficial; sedes; pisos; marcas; sistemas operativos. Validar la lista oficial con Talento Humano o el organigrama.
3. Normalizar códigos de activo: quitar espacios y guiones, cambiar «L1» por «I1», marcar como vacíos los textos («N/A», «No se ve») y mover a serial los valores que lo sean.
4. Excluir los equipos marcados como personales y marcar los de terceros con su propietario.
5. Dividir filas en equipos: cada bloque con tipo crea un equipo; teclado, mouse y sonido se crean como componentes del equipo de cómputo.
6. Importar en un ambiente de pruebas, revisar el reporte y repetir hasta que no haya errores bloqueantes.
7. Importar en producción.
8. Verificar en sitio por sede, empezando por los 146 equipos sin código de activo, los 12 códigos repetidos y los 64 puestos marcados como malos o para baja; en cada visita se toma el serial, se completan RAM, disco y procesador, se confirma el responsable, se firma el formato de entrega y se pega la etiqueta QR impresa por lotes para esa sede, de modo que cada equipo verificado quede etiquetado en la misma visita.

### Criterios de aceptación de la carga

- Equipos creados = equipos del archivo menos los personales, sin pérdidas.
- Cada equipo conserva el número de fila de origen.
- Los conteos por tipo y por sede coinciden con el archivo.
- Ninguna dependencia, sede o piso queda como texto libre.
- El reporte de calidad lista todos los equipos pendientes de verificar.

---

## 13. Alcance por fases

La primera versión debe dejar el inventario cargado, la hoja de vida funcionando con sus dos formatos firmados y cada equipo con su QR; tablero y mejoras vienen después. Toda idea nueva durante una fase se anota para la siguiente.

| Fase | Alcance | Requerimientos | Resultado visible |
|---|---|---|---|
| **1 — Inventario, hoja de vida y QR** | Registro de equipos por serial y código de activo, carga y depuración, componentes y sus cambios, responsables, traslados con dos formatos, diagnóstico y baja, PDF y firmas, generación del QR, etiqueta individual y apertura por escaneo, reportes básicos, administrador y usuarios, auditoría | Los 35 esenciales | Todos los equipos consultables, con hoja de vida, formatos imprimibles y QR |
| **2 — Consolidación** | Puestos de trabajo, fotos, historial de componentes por fecha, destino de lo retirado, tipos de evento configurables, paz y salvo, anulación de baja, listado de bajas, pendientes de firma, hoja de vida completa, tablero, cargas posteriores, restablecer contraseña, etiquetas por lotes y reimpresión | RF-05, RF-06, RF-13, RF-16, RF-17, RF-23, RF-26, RF-27, RF-31, RF-33, RF-36, RF-42, RF-47, RF-49, RF-51 | Trazabilidad completa, control de firmas pendientes y etiquetado masivo |
| **3 — Mejoras** | Firma en pantalla | RF-32 | Trabajo en sitio sin papel |

La verificación en sitio del inventario corre en paralelo desde la fase 1, porque el sistema solo es útil si sus datos son confiables; en esas visitas se pegan las etiquetas QR.

---

## 14. Preguntas abiertas y próximos pasos

Quedan 12 preguntas por cerrar; las tres primeras cambian el modelo de datos o los formatos, y las tres últimas (nuevas en v3) definen cómo funciona el QR. Todas deben resolverse antes de empezar los diseños definitivos.

| # | Pregunta | Supuesto actual | Qué cambia si es distinto |
|---|---|---|---|
| 1 | ¿El traslado seguirá con dos documentos (baja y entrega) o se unirá en uno solo? | Dos documentos; el sistema acepta ambas opciones | Cambia el diseño de los formatos y la regla RN-05. |
| 2 | ¿Dónde está la plantilla actual del formato de entrega? Solo se tiene el formato de baja v1.0. | Se diseña a partir del formato de baja | Hay que conciliar campos y firmas con la plantilla real. |
| 3 | ¿Qué componentes son obligatorios al registrar un equipo de cómputo (RAM, disco, procesador, periféricos)? | RAM, disco y procesador obligatorios; periféricos opcionales | Cambia el formulario de registro y la verificación en sitio. |
| 4 | ¿Las series de código de 5 y 6 dígitos (I1-25646 e I1-025646) son la misma numeración? ¿Quién en Almacén valida los 146 sin código y los 12 repetidos? | Son distintas hasta que Almacén confirme | Afecta la normalización y la detección de duplicados. |
| 5 | ¿Cómo se registra la salida de un equipo de tercero cuando se devuelve a su propietario? | Como baja con motivo «devolución al propietario» | Podría necesitar un evento propio. |
| 6 | ¿Se cargan además hojas de vida antiguas? | Solo el inventario 2026 | Haría falta un formato de migración adicional. |
| 7 | ¿Firma manuscrita escaneada o firma digital? | Manuscrita escaneada | La firma digital exige certificados y otro flujo. |
| 8 | ¿Existe una lista oficial de dependencias y sedes? | Se construye desde el inventario | Cambia la tabla de equivalencias. |
| 9 | ¿Dónde se alojará y con qué tecnología? | Servidor propio o intranet | Define la arquitectura en la fase de diseño. |
| 10 | Al escanear el QR, ¿solo el personal TIC con sesión ve la hoja de vida, o se quiere una vista pública reducida (tipo, marca, serial, dependencia, sin cédulas ni historial)? | Solo con sesión iniciada (RN-18) | Una vista pública exige definir qué datos se muestran, revisión frente a la Ley 1581 y publicar parte del sistema fuera de la red interna. |
| 11 | ¿El QR se pone solo a los PC o a todos los equipos? | A todos, empezando por los PC | Cambia la cantidad de etiquetas y el plan de etiquetado. |
| 12 | ¿Hay impresora de etiquetas y qué material y tamaño se comprará? | Hojas adhesivas de poliéster tamaño carta en impresora láser | Cambia la plantilla de impresión por lotes y el costo. |

### Próximos pasos hacia el diseño

- [ ] Revisar esta versión con el jefe y cerrar las preguntas 1 a 3 y 10 a 12.
- [ ] Conseguir la plantilla del formato de entrega.
- [ ] Pedir a Almacén la base de códigos de activo para conciliar.
- [ ] Definir la lista oficial de dependencias y sedes.
- [ ] Cotizar el material de las etiquetas y hacer una prueba de lectura del QR en equipos reales.
- [ ] Diseñar el modelo físico de datos a partir de la sección 8.
- [ ] Diseñar las pantallas: búsqueda, hoja de vida, traslado, cambio de componente, baja, importación, usuarios, reportes, impresión de etiquetas y apertura por QR.
- [ ] Rediseñar los formatos de entrega y de baja (sección 11.1) y diseñar la etiqueta QR.
- [ ] Elegir la tecnología según dónde se aloje y quién lo sostendrá.

---

**Ver también:** [Plan de trabajo de dos semanas](./plan-2-semanas.md) · [Normas de trabajo](./normas-de-trabajo.md)
