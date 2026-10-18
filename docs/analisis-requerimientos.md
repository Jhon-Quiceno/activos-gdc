# Análisis de requerimientos — Sistema de Hoja de Vida de Activos Tecnológicos

**Gobernación de Córdoba · Dirección TIC · Versión 4**
17 de octubre de 2026 · Jhon (líder) · @JWRCompany

> Documento de referencia permanente del proyecto. La versión 4 cambia la **fuente** del análisis: deja de construirse a partir del Excel del inventario 2026 y pasa a apoyarse en la documentación oficial de la Gobernación (sección 2). El modelo de datos completo está en [base-de-datos.md](./base-de-datos.md).
> Hay una versión Word de este mismo documento en [analisis-requerimientos-v4.docx](./analisis-requerimientos-v4.docx).

---

## 1. Introducción

El sistema es una aplicación web que lleva la **hoja de vida de cada activo tecnológico** de la Gobernación de Córdoba, desde su ingreso hasta su baja. Su ficha sigue la estructura oficial del inventario de activos (Manual GT-M4 y formato FOR.01). Ubica cada activo en la estructura organizacional vigente (Decreto 609 de 2023) y en las sedes reales, asigna sus responsables con el catálogo oficial de cargos (Decreto 607 de 2023) y registra el mantenimiento preventivo según el Programa Anual de Mantenimiento.

### 1.1 Propósito

Responder, con evidencia y en un solo lugar:

1. Qué activos tecnológicos tiene la entidad y cuánto valen para ella (valoración CID).
2. Dónde están (sede, piso, dependencia) y quién responde por ellos (responsable, propietario, custodio).
3. Qué se les ha hecho: traslados, cambios de componentes, mantenimientos, diagnósticos.
4. Cuáles salieron de servicio y por qué.

### 1.2 Alcance

**Incluido:**

- Registro y ficha de cada activo de tipo **hardware** y **soporte** (GT-M4, tabla 2): computadores, portátiles, servidores, monitores, impresoras, escáneres, equipos de red, NAS, videograbadores, cámaras, control de acceso, video beam, telefonía, UPS, plantas eléctricas y aires de cuartos técnicos.
- Datos de adquisición, garantía y propiedad (Gobernación o tercero).
- **Clasificación y valoración del activo** según GT-M4: datos personales, infraestructura crítica, continuidad del negocio, propietario, custodio, retención y calificación de confidencialidad, integridad y disponibilidad.
- Historial de eventos inmutable: alta, traslado, cambio de componente, **mantenimiento**, diagnóstico, **valoración**, baja.
- **Mantenimiento preventivo y correctivo**: plan anual, programación por dependencia y mes, ejecución y cumplimiento. *(Cambio frente a v3, que lo excluía.)*
- Levantamiento de activos por proceso con acta de aceptación firmada (GT-M4 4.1).
- Formatos firmados (entrega, baja y mantenimiento), código QR y etiquetas.
- Estructura organizacional oficial, sedes con sus pisos y personas responsables con datos mínimos.
- Carga de datos desde Excel con plantilla oficial: equipos, personas y valoraciones.
- Consultas, reportes y exportación de la matriz de activos en el formato FOR.01.

**Excluido:**

- Activos de información que no son equipos (software, bases de datos, servicios, información física o electrónica, personas, instalaciones e intangibles). El GT-M4 los incluye, pero no tienen hoja de vida de equipo; ver pregunta abierta 3.
- Equipos de las instituciones educativas (166 IE, 201 sedes), hasta que se decida lo contrario (pregunta 2).
- Equipos personales de los funcionarios.
- Gestión de riesgos e incidentes de seguridad: el sistema solo **marca** los activos que deben pasar a la matriz de riesgos (GT-M4 4.2.6), no la administra.
- Contabilidad del Almacén, mesa de ayuda y monitoreo de red.

### 1.3 Control de versiones

| Versión | Cambios principales |
|---|---|
| 1 | Análisis inicial a partir del inventario 2026 y la primera reunión con el jefe. |
| 2 | Administrador y usuarios, dos formatos, traslado con dos firmas, mantenimiento fuera del alcance, componentes, serial y código de activo, terceros. |
| 3 | Códigos QR (RF-08 esencial, RF-48 a RF-51). |
| **4** | **Cambio de fuente:** documentación oficial en lugar del Excel 2026, que solo se usa para cargar datos. Estructura organizacional jerárquica, sedes reales con sus pisos, catálogo oficial de cargos, valoración GT-M4, datos de adquisición, mantenimiento preventivo dentro del alcance, levantamiento con acta. RF-52 a RF-65, RN-20 a RN-29, modelo de datos v4. |

### 1.4 Definiciones

| Término | Significado |
|---|---|
| **Activo de información** | Lo que tiene valor para la entidad y contiene o manipula información (GT-M4). En este sistema: los de tipo hardware y soporte. |
| **Equipo** | Activo tecnológico individual con hoja de vida propia. |
| **Activo de soporte** | Componente físico que no contiene información pero sostiene su disponibilidad: UPS, aire acondicionado, planta eléctrica. No se clasifica por información (GT-M4). |
| **Propietario del activo** | Director o líder del proceso donde está el activo. Identifica, actualiza y mantiene sus activos (GT-M4). |
| **Custodio del activo** | Cargo que administra el activo y aplica los controles que define el propietario (GT-M4). |
| **Responsable** | Funcionario o contratista que tiene el equipo a cargo y firma su entrega. |
| **Valoración CID** | Calificación de 1 a 4 de confidencialidad, integridad y disponibilidad; su suma (3 a 12) da la importancia (GT-M4, tablas 3 a 6). |
| **Levantamiento** | Identificación y valoración de los activos de un proceso, cerrada con un acta firmada por el propietario y los custodios (GT-M4 4.1). |
| **PAMP** | Programa Anual de Mantenimiento Preventivo de la Dirección TIC. |
| **Dependencia** | Unidad de la estructura del Decreto 609: despacho, secretaría, departamento administrativo, oficina, dirección o área. |
| **Sede** | Edificio donde funciona la entidad («sucursal» en el GT-M4). Cada sede tiene sus pisos. |
| **Serial**, **código de activo**, **QR**, **evento**, **formato de entrega / de baja** | Igual que en v3. |

---

## 2. Fuentes del análisis

### 2.1 Documentos usados

| Documento | Qué aporta al sistema |
|---|---|
| **GT-M4** Manual para elaboración del inventario, definición y clasificación de los activos de información (v01, 28/09/2023, proceso Gestión Tecnológica) | Campos oficiales del activo (identificación, atributo, propiedad, ubicación, valoración), tipos de activo, escalas CID e importancia, criterios para pasar a gestión de riesgos, levantamiento con acta de aceptación. Marco: ISO/IEC 27001:2013, Ley 1581 de 2012, Ley 1712 de 2014, MSPI de MinTIC. |
| **FOR.01** Formato de inventario de activos de información v00 | Las columnas exactas de la matriz y sus listas (hoja «Datos»): sedes, tipo de sede, dependencias, 17 procesos, tipos de activo, datos personales, tipo de ubicación, controles de monitoreo y la matriz de impacto. Trae 29 activos de ejemplo de la Dirección de Sistemas. |
| **Programa Anual de Mantenimiento Preventivo** (v01, 21/10/2024, vigencia 2026) | Objetivo, alcance y recursos del mantenimiento; programación mensual por dependencia con impacto alto, medio o bajo; sedes con dirección, pisos y dependencias por piso; conteos de equipos e impresoras; 2,5 h por dispositivo. |
| **Decreto 609 de 2023** (organigrama) | Estructura organizacional vigente, creación de la Secretaría de Minas y Energía y cambios de nombre de dependencias (la Dirección Técnica de Sistemas pasa a ser la Dirección TICS). |
| **Decreto 607 de 2023** (planta global y nomenclatura) | Niveles jerárquicos y códigos oficiales de cargos (001 a 477), grados. |
| **Decreto 608 de 2023** (manual de funciones, 2 copias) | Función común a todo empleo: usar bien los equipos e informar los cambios para mantener actualizado el inventario del área. *Solo se pudieron extraer las primeras 80 de 639 páginas* (ver pregunta 12). |
| **Planta Central** (2-mar-2026) y **Planta Educación** (13-may-2026) | Dependencias reales y ubicación de cada funcionario, cargo con código y grado, nivel, tipo de provisión, jefe inmediato. La de Educación muestra 686 cargos en 166 instituciones educativas de 71 municipios. |
| **Organigrama_GobCordoba.webp** | Imagen del organigrama del Decreto 609 (el texto del decreto ya lo transcribe). |

### 2.2 Documentos descartados

Descartados por indicación del profesor, porque no tienen relación con los activos:

- *106. Reporte a la CNSC Auxiliar Administrativo 407-7 – Renuncia* (trámite de personal).
- *Necesidad_DTH.pdf*.
- *reporteEvaluacion_2914065.pdf*.
- *Resolución 0148 de 2026 – Renuncia* (acto de personal).

### 2.3 El inventario 2026 (Excel)

Hasta v3, el modelo se construyó con las 53 columnas del inventario de marzo de 2026 (Microsoft Forms, 461 filas, 930 equipos). **Desde v4 ese archivo no define la estructura del sistema.** Queda solo como **fuente de datos para la carga**: sus columnas se mapean a la plantilla oficial de carga (sección 12). Su diagnóstico de calidad (sin serial, códigos repetidos, 175 variantes de dependencia) sigue sirviendo para diseñar la depuración, pero ya no condiciona los campos.

### 2.4 Inconsistencias encontradas en las fuentes

| # | Inconsistencia | Tratamiento |
|---|---|---|
| I-01 | La tabla 1 del GT-M4 (procesos y propietarios) viene de una plantilla de empresa privada: «Comercial», «Postventa», «SAGRILAFT», «Gerente General». El FOR.01 sí lista los 17 procesos de la Gobernación. | Se usan los procesos del FOR.01. |
| I-02 | El FOR.01 y sus ejemplos dicen «Empresa» y usan nombres anteriores al Decreto 609 («DIRECION DE SISTEMAS»). | Tabla de equivalencias hacia los nombres oficiales. |
| I-03 | La tabla 6 del GT-M4 pone la importancia baja «de 0 a 3», aunque la suma mínima es 3. | Se acepta 3 como mínimo real. |
| I-04 | El Decreto 607 escaneado asigna el código 430 al Auxiliar de Servicios Generales; el artículo 15 y la planta usan 470. | Se usa 470. |
| I-05 | Fondo Territorial de Pensiones aparece bajo Hacienda en el PAMP y bajo Gestión Administrativa en la planta. | Planta (oficial de personal); por confirmar. |
| I-06 | El PAMP se titula «Plan anual de Mantenimiento 2024», pero programa la vigencia 2026. | Se registra como plan de la vigencia 2026. |
| I-07 | En el FOR.01 hay activos con «Contiene datos personales = NO» y clasificación «Reservado» o «Sensible». | El sistema exige `no_aplica` cuando no hay datos personales (RN-24). |

---

## 3. Diagnóstico de la situación con las fuentes oficiales

1. **Hoy la entidad tiene tres inventarios que no se cruzan:** la matriz de activos de información (FOR.01, valoración de seguridad), el programa de mantenimiento (conteos por dependencia) y el inventario 2026 (equipos por puesto). El sistema los une en una sola ficha por equipo.
2. **La estructura organizacional es jerárquica y cambió en 2023.** Una lista plana de dependencias no permite consultar «todo lo de la Secretaría de Hacienda» (con sus seis direcciones), ni reconocer nombres viejos.
3. **Las sedes tienen pisos distintos.** Palacio de Naín tiene 8 pisos ocupados; Places Mall, un local en el piso 2; el Laboratorio, uno solo. El PAMP dice qué dependencia está en cada piso.
4. **El responsable es un empleo oficial,** con código, grado y jefe inmediato. La planta trae mucho más (salario, RH, salud, etnia, hijos, dirección); la Ley 1581 obliga a no copiarlo.
5. **La valoración de seguridad es parte de la ficha del activo** según el GT-M4, y define qué activos pasan a gestión de riesgos.
6. **El mantenimiento preventivo existe como programa oficial** con meta mensual: 507 equipos y 64 impresoras, a 2,5 h por dispositivo.

---

## 4. Usuarios y actores

| Actor | ¿Ingresa? | Qué hace |
|---|---|---|
| **Administrador** | Sí | Usuarios, contraseñas, catálogos (árbol de dependencias, sedes y pisos, cargos, procesos, tipos, modelos, proveedores). Además, todo lo de un usuario. |
| **Usuario** (ingeniero, técnico o pasante de soporte TIC) | Sí | Registra equipos, valoraciones, traslados, componentes, mantenimientos, diagnósticos y bajas; genera formatos; sube documentos firmados; carga datos; consulta reportes. |
| **Responsable del equipo** (funcionario o contratista) | No | Firma la entrega y la devolución; informa los cambios del equipo a su cargo (Decreto 608, función común). |
| **Propietario del activo** (director o líder del proceso) | No | Valida la valoración y firma el acta de aceptación del levantamiento. |
| **Custodio del activo** | No | Firma el acta del levantamiento; aplica los controles. |
| **Responsable del SGSI** | No | Orienta el levantamiento y dirime dudas de clasificación; recibe el reporte de activos críticos. |
| **Director TIC** | No | Aprueba el PAMP, recibe reportes y cumplimiento. |
| **Almacén General** | No | Fuente del código de activo y de los datos de adquisición. |
| **Dirección de Talento Humano** | No | Fuente de la planta de personal. |

---

## 5. Requerimientos funcionales

**65 requerimientos en 13 módulos.** Los RF-01 a RF-51 conservan su número de v3: los que cambian se marcan **(mod. v4)**. Los nuevos son RF-52 a RF-65 **(nuevo v4)**. Prioridades: E = esencial, I = importante, D = deseable.

### 5.1 Inventario maestro de equipos

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-01 | Registrar cualquier activo de tipo hardware o soporte, de la Gobernación o de terceros, con los campos de la sección 8, aunque aún no tenga responsable ni eventos. **(mod. v4)** | E |
| RF-02 | Identificar cada equipo por serial (obligatorio y único), código de activo de Almacén (I1-######, cuando lo tiene) y un consecutivo de activo (`ID ACTIVO` del FOR.01) que asigna el sistema. **(mod. v4)** | E |
| RF-03 | Mostrar campos distintos según el tipo de equipo. | E |
| RF-04 | Registrar la propiedad: Gobernación o tercero (comodato, convenio, proveedor, arrendamiento), con el propietario tomado del catálogo de proveedores. **(mod. v4)** | E |
| RF-05 | Agrupar equipos en un puesto de trabajo y trasladar el puesto completo. | I |
| RF-06 | Adjuntar fotografías al equipo. | I |
| RF-07 | Buscar por serial, código de activo, número de activo, responsable, documento, dependencia (incluidas sus subdependencias) o sede, tolerando espacios y guiones. **(mod. v4)** | E |
| RF-52 | Registrar los datos de adquisición: forma de ingreso, fecha, valor en COP, proveedor, contrato, factura, fin de garantía y vida útil; avisar las garantías por vencer y los equipos que superan su vida útil. **(nuevo v4)** | I |

### 5.2 Hoja de vida e historial

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-09 | Mostrar en una sola vista la ficha, la valoración vigente, la configuración y los componentes, el responsable y la ubicación, los datos de adquisición y el historial completo, incluidos los mantenimientos. **(mod. v4)** | E |
| RF-10 | Registrar eventos con tipo, fecha, descripción y adjuntos. Tipos: alta, traslado, cambio de componente, mantenimiento, diagnóstico, valoración, baja, actualización de datos, anulación o aclaración. **(mod. v4)** | E |
| RF-11 | Asignar el autor del evento automáticamente. | E |
| RF-12 | Eventos inmutables; un cambio de responsable, ubicación, estado o valoración genera un evento con valor anterior y nuevo. **(mod. v4)** | E |
| RF-13 | Configurar nuevos tipos de evento sin desarrollo. | I |

### 5.3 Componentes y configuración

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-14 | Registrar los componentes de un equipo de cómputo; RAM, disco y procesador son obligatorios para darlo por verificado. **(mod. v4)** | E |
| RF-15 | Registrar un cambio de componente (agregar, cambiar, quitar) como evento. | E |
| RF-16 | Consultar la configuración en una fecha dada. | I |
| RF-17 | Registrar el destino del componente retirado (bodega, otro equipo con su referencia, descarte). | I |

### 5.4 Responsables, ubicación y traslados

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-18 | Registrar la persona responsable con tipo y número de documento, nombres, apellidos, correo institucional, cargo oficial (código, denominación, grado, encargo), dependencia real, jefe inmediato, planta, tipo de vinculación (planta, contratista, pasante) y provisión. **(mod. v4)** | E |
| RF-19 | Registrar la ubicación como sede → piso de esa sede → dependencia del árbol oficial, proponiendo las dependencias que el PAMP ubica en ese piso. **(mod. v4)** | E |
| RF-20 | Traslado o cambio de responsable con los dos formatos firmados (o documento único). | E |
| RF-21 | Equipo sin responsable (bodega) como estado válido. | E |
| RF-22 | Consultar los equipos a cargo de una persona y su formato de entrega consolidado. | E |
| RF-23 | Paz y salvo cuando un funcionario se retira; el sistema avisa de las personas con `fecha_fin_vinculacion` próxima que aún tienen equipos. **(mod. v4)** | I |

### 5.5 Diagnóstico y baja

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-24 | Registrar un diagnóstico técnico. | E |
| RF-25 | Registrar la baja con diagnóstico, motivo y formato firmado. | E |
| RF-26 | Anular una baja con evento justificado. | I |
| RF-27 | Listado de bajas por período. | I |

### 5.6 Formatos y firmas

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-28 | Formato de entrega en PDF. | E |
| RF-29 | Formato de baja en PDF. | E |
| RF-30 | Subir documentos firmados; «Pendiente de firma» hasta tenerlos. | E |
| RF-31 | Listar pendientes de firma. | I |
| RF-32 | Firma en pantalla. | D |
| RF-33 | Exportar la hoja de vida completa en PDF (incluye valoración y mantenimientos). **(mod. v4)** | I |

### 5.7 Consultas y reportes

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-34 | Catálogo de reportes de la sección 11, con filtros por sede, piso, dependencia (con o sin subdependencias), proceso, tipo, marca, estado, propiedad, importancia, responsable, vinculación y fechas. **(mod. v4)** | E |
| RF-35 | Exportar a Excel y PDF. | E |
| RF-36 | Tablero de totales. | I |
| RF-37 | Indicadores de calidad: sin serial, sin código, sin responsable, código repetido, pendientes de verificar, **sin valoración**, **sin datos de adquisición**. **(mod. v4)** | E |
| RF-53 | Exportar la matriz de inventario de activos con las columnas y el encabezado del formato FOR.01, por proceso o dependencia. **(nuevo v4)** | E |
| RF-54 | Reporte de activos que requieren gestión de riesgos (algún criterio CID = 4, o importancia alta o muy alta). **(nuevo v4)** | E |

### 5.8 Carga de datos desde Excel

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-38 | Importar equipos desde la **plantilla oficial de carga** (columnas del modelo v4 y del FOR.01), con vista previa, número de fila de origen y modo simulación. El inventario 2026 se carga mapeando sus columnas a esa plantilla. **(mod. v4)** | E |
| RF-39 | Normalizar con la tabla de equivalencias editable (dependencias con nombres anteriores al Decreto 609, sedes, marcas, modelos, sistemas operativos). **(mod. v4)** | E |
| RF-40 | Vista previa con errores y advertencias por fila. | E |
| RF-41 | Marcar «Pendiente de verificar» lo importado sin serial o con datos dudosos. | E |
| RF-42 | Cargas posteriores sin duplicar equipos existentes. | I |
| RF-55 | Importar personas desde la planta de Talento Humano (Central y Educación), tomando **solo** las columnas permitidas y descartando el resto sin guardarlo. **(nuevo v4)** | E |

### 5.9 Usuarios y administración

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-43 | Dos perfiles: Administrador y Usuario. | E |
| RF-44 | Crear, editar y desactivar usuarios, con cambio de contraseña en el primer ingreso; enlazar el usuario con su persona. **(mod. v4)** | E |
| RF-45 | Mantener los catálogos: procesos, árbol de dependencias y equivalencias, municipios, sedes y pisos, ubicación de dependencias, cargos, tipos de equipo, marcas, modelos, proveedores, sistemas operativos, tipos de componente, motivos de baja. **(mod. v4)** | E |
| RF-46 | Bitácora de auditoría. | E |
| RF-47 | Restablecer contraseñas. | I |

### 5.10 Códigos QR y etiquetas

RF-08 (E), RF-48 (E), RF-49 (I), RF-50 (E) y RF-51 (I) quedan **sin cambios** respecto a v3. RF-49 filtra ahora por sede y piso reales.

### 5.11 Clasificación y valoración del activo — GT-M4 (nuevo v4)

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-56 | Registrar la valoración de un activo de hardware: datos personales y su clasificación, infraestructura crítica, continuidad del negocio, cargo del propietario, cargo del custodio, tipo de ubicación, tiempo de retención, controles de monitoreo y calificación de 1 a 4 de confidencialidad, integridad y disponibilidad, mostrando junto a cada nivel el texto de la escala del GT-M4. | E |
| RF-57 | Calcular automáticamente el total, la importancia y la marca «requiere gestión de riesgos». | E |
| RF-58 | Reclasificar un activo (por incidente o a pedido del propietario) como evento `valoracion`, conservando la historia de valoraciones. | E |
| RF-59 | Llevar un levantamiento de activos por dependencia y proceso: lista de sus equipos con su valoración, acta de identificación y aceptación prellenada, firma del propietario y los custodios, y carga del acta firmada. | I |

### 5.12 Mantenimiento preventivo y correctivo (nuevo v4)

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-60 | Registrar el plan anual de mantenimiento por vigencia (código, versión, objetivo, alcance, horas por dispositivo) y su programación por dependencia, sede y mes, con impacto, responsable, equipo ejecutor y equipos estimados. Debe poder cargarse desde el Excel del PAMP. | E |
| RF-61 | Registrar el mantenimiento de un equipo (preventivo, enlazado a su programación, o correctivo) con checklist de actividades (inspección, limpieza, actualización, revisión), hallazgos, insumos, duración, estado resultante y ejecutor (propio o tercero). Si cambia una pieza, ofrecer el cambio de componente en el mismo flujo. | E |
| RF-62 | Generar la lista de trabajo de una programación: los equipos asignados hoy a esa dependencia y sede, con su estado. | E |
| RF-63 | Mostrar el cumplimiento del plan: equipos mantenidos frente a estimados, por mes, dependencia y sede, y los atrasados. | I |
| RF-64 | Generar el formato de mantenimiento en PDF por equipo o por programación, con espacio para la firma del responsable de la dependencia. | I |

### 5.13 Estructura organizacional (nuevo v4)

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-65 | Mostrar y filtrar por el árbol de dependencias: al elegir una secretaría se incluyen sus direcciones y áreas; las dependencias suprimidas se desactivan, sin borrarse, y se pueden fusionar con equivalencias. | E |

---

## 6. Requerimientos no funcionales

Se mantienen **RNF-01 a RNF-18 de v3** (web responsive, español, rendimiento con 5.000 equipos, servidor propio, integridad referencial, contraseñas, HTTPS, Ley 1581, bitácora 5 años, respaldos, adjuntos, PDF carta, formatos colombianos, código en repositorio, manuales, etiquetas QR resistentes, ruta corta del QR). Se agregan:

| ID | Requerimiento | Criterio de verificación |
|---|---|---|
| RNF-19 | **Minimización de datos personales:** el sistema solo almacena de cada persona los datos de RF-18. Las cargas desde la planta descartan en memoria el resto de columnas. | Revisión del esquema y de una importación de prueba. |
| RNF-20 | El propio sistema es un activo de información (tipo «Información electrónica», datos semiprivados) y debe quedar registrado en la matriz FOR.01 de la Dirección TIC. | Registro en la matriz. |
| RNF-21 | Los PDF y las exportaciones llevan el encabezado del Sistema Integrado de Gestión (proceso, código, versión, fecha) cuando la Gobernación asigne el código del formato. | Revisión de un PDF. |
| RNF-22 | Las direcciones reservadas (Casa Refugio) nunca aparecen en listados, exportaciones, PDF ni etiquetas. | Prueba funcional sobre todas las salidas. |

---

## 7. Reglas de negocio

Se mantienen **RN-01 a RN-19 de v3**, salvo **RN-08**, que se reemplaza, y **RN-11**, que se amplía:

| ID | Regla |
|---|---|
| RN-08 **(reemplazada)** | El mantenimiento preventivo y correctivo se registra como evento `mantenimiento`. Si cambia una pieza, ese cambio se registra además como cambio de componente (RN-07). |
| RN-11 **(ampliada)** | Sede, piso, dependencia, proceso, cargo, tipo, marca, modelo, proveedor, tipo de componente y sistema operativo solo se eligen de listas. |
| RN-20 | Las dependencias usan los nombres oficiales del Decreto 609 y forman un árbol sin ciclos. Nunca se borran: se desactivan. |
| RN-21 | Un piso pertenece a una sola sede; la ubicación de un equipo es un piso válido de esa sede. |
| RN-22 | Cada equipo de hardware tiene como máximo una valoración vigente. El total, la importancia y la marca de gestión de riesgos los calcula el sistema; nunca se digitan. |
| RN-23 | Un activo con algún criterio CID igual a 4, o con importancia alta o muy alta, queda marcado «requiere gestión de riesgos» (GT-M4 4.2.6). |
| RN-24 | Si un activo no contiene datos personales, su clasificación de datos personales es «no aplica». |
| RN-25 | Los activos de tipo soporte no tienen valoración de información. |
| RN-26 | De la planta de personal solo se guardan los datos de RF-18 (RNF-19). |
| RN-27 | El número de activo (consecutivo) se asigna al crear el equipo y nunca se reutiliza ni cambia. |
| RN-28 | Un mantenimiento preventivo se enlaza a una programación de la vigencia; si el equipo no está en la dependencia y sede programadas, el sistema pide confirmación. |
| RN-29 | La dirección de una sede reservada no se muestra en ninguna salida del sistema. |

---

## 8. Modelo de dominio

El modelo físico completo, con diccionario, valores oficiales de los catálogos y plan de migración, está en **[base-de-datos.md](./base-de-datos.md)**. El DBML para dbdiagram.io está en [database/dbml/hoja-vida-activos.dbml](../database/dbml/hoja-vida-activos.dbml) y el SQL equivalente en [hoja-vida-activos.sql](../database/dbml/hoja-vida-activos.sql).

| Bloque | Entidades | Fuente |
|---|---|---|
| Estructura organizacional | Proceso, Dependencia (árbol), Equivalencia, Municipio, Sede, Piso, UbicaciónDependencia, Cargo, Persona | Decretos 607 y 609, plantas, PAMP, FOR.01 |
| Catálogos del activo | TipoEquipo, Marca, Modelo, SistemaOperativo, TipoComponente, MotivoBaja, Proveedor | FOR.01, GT-M4 |
| Equipo | Equipo, ValoraciónActivo, ConfiguraciónCómputo, Componente, PuestoTrabajo, Asignación | GT-M4, FOR.01 |
| Eventos | Evento, CambioComponente, Diagnóstico, Documento, EtiquetaQR | v3 |
| Mantenimiento | PlanMantenimiento, ProgramaciónMantenimiento, Mantenimiento | PAMP |
| Levantamiento | LevantamientoActivos | GT-M4 4.1 |
| Sistema | Usuario, Auditoría, Importación | v3 |

### 8.1 Ficha del equipo frente al formato FOR.01

| Columna del FOR.01 | Dónde queda en el sistema |
|---|---|
| ID ACTIVO | `equipos.numero_activo` |
| Sucursal | Sede de la asignación actual |
| Proceso | `equipos.proceso_id` |
| Área o Dependencia | Dependencia de la asignación actual |
| Nombre Activo | Tipo de equipo + marca + modelo |
| Descripción Activo | `equipos.descripcion` |
| Tipo de Activo | `tipos_equipo.tipo_activo` (hardware o soporte) |
| Contiene datos personales / Clasificación | `valoraciones_activo` |
| Infraestructura crítica / Continuidad del negocio | `valoraciones_activo` |
| Propietario del activo (cargo) / Custodio técnico (cargo) | `valoraciones_activo` |
| Tipo ubicación del activo | `valoraciones_activo.tipo_ubicacion` |
| Tiempo de retención de los datos | `valoraciones_activo.tiempo_retencion` |
| Confidencialidad / Integridad / Disponibilidad (escenario y respuesta) | Valores 1 a 4; la respuesta es el texto de la escala |
| Total / Importancia | Calculados |

---

## 9. Estados del equipo y tipos de evento

Estados del ciclo de vida: **En servicio**, **Sin asignar** y **Dado de baja**, más la marca **Verificado / Pendiente de verificar**. El estado de funcionamiento pasa a lista cerrada: bueno, regular (requiere mantenimiento), malo (requiere componentes), no funcional (requiere baja).

| Tipo de evento | Efecto | Firmas | Datos propios |
|---|---|---|---|
| Alta | Crea el equipo | Entrega, si se entrega | Forma de ingreso, adquisición |
| Traslado / cambio de responsable | Puede cambiar entre «en servicio» y «sin asignar» | Baja y entrega | Origen, destino, motivo |
| Cambio de componente | Actualiza la configuración | No | Acción, seriales, destino |
| **Mantenimiento** (nuevo) | Actualiza el estado de funcionamiento | Formato de mantenimiento (si aplica) | Tipo, programación, actividades, hallazgos, duración |
| Diagnóstico | Ninguno | Según el caso | Estado, causa, recomendaciones |
| **Valoración** (nuevo) | Reemplaza la valoración vigente | No (el acta va en el levantamiento) | CID, atributos, propietario, custodio |
| Baja | → Dado de baja | Formato de baja | Motivo, diagnóstico |
| Actualización de datos | Ninguno | No | Antes y después |
| Anulación / aclaración | Revierte el evento anulado | No | Evento y motivo |

---

## 10. Casos de uso

Se mantienen CU-01 a CU-15 de v3 (CU-01 cambia de fuente). Se agregan:

| ID | Caso de uso | Actor | RF |
|---|---|---|---|
| CU-01 **(mod.)** | Cargar datos desde la plantilla oficial (equipos, personas, valoraciones) | Usuario | RF-38 a RF-42, RF-55 |
| CU-16 | Valorar un activo | Usuario | RF-56, RF-57 |
| CU-17 | Reclasificar un activo | Usuario | RF-58 |
| CU-18 | Hacer el levantamiento de activos de una dependencia | Usuario | RF-59, RF-53 |
| CU-19 | Registrar el plan anual de mantenimiento | Usuario | RF-60 |
| CU-20 | Ejecutar un mantenimiento programado | Usuario | RF-61, RF-62, RF-64 |
| CU-21 | Consultar el cumplimiento del plan | Usuario | RF-63 |
| CU-22 | Administrar la estructura organizacional | Administrador | RF-45, RF-65 |

### 10.1 CU-16 · Valorar un activo

**Precondición:** el equipo existe, es de tipo hardware y no está dado de baja.

1. Desde la hoja de vida, elegir «Valorar activo».
2. Responder datos personales y su clasificación, infraestructura crítica, continuidad del negocio, tipo de ubicación y retención.
3. Indicar los cargos del propietario (sugerido: el director de la dependencia) y del custodio.
4. Elegir, para C, I y D, la respuesta de la escala del GT-M4; el sistema muestra el valor de 1 a 4.
5. El sistema calcula el total y la importancia, y marca si requiere gestión de riesgos.
6. Al guardar se crea el evento `valoracion` y la valoración anterior deja de estar vigente.

### 10.2 CU-18 · Levantamiento de activos de una dependencia

1. Crear el levantamiento eligiendo dependencia, proceso y sede.
2. El sistema lista los equipos asignados a esa dependencia (y sus subdependencias) y señala los que no tienen valoración.
3. El técnico verifica cada equipo en sitio: serial, componentes, valoración y etiqueta QR.
4. El sistema genera la matriz FOR.01 y el acta de aceptación prellenada.
5. Firman el propietario y los custodios; se sube el acta y el levantamiento queda «Aceptado».

### 10.3 CU-20 · Ejecutar un mantenimiento programado

**Precondición:** existe un plan aprobado para la vigencia.

1. Elegir la programación del mes (dependencia y sede).
2. El sistema muestra la lista de trabajo con los equipos asignados ahí.
3. Por cada equipo: marcar las actividades hechas, anotar hallazgos, insumos y duración, y elegir el estado resultante.
4. Si se cambió una pieza, registrar en el mismo flujo el cambio de componente.
5. Cada equipo queda con su evento `mantenimiento`; opcionalmente se genera el formato para firma.

**Alternativo:** mantenimiento correctivo, sin programación, desde la hoja de vida.

---

## 11. Reportes y salidas

**Formatos para firma:** entrega, baja, **mantenimiento** (nuevo) y **acta de aceptación de activos** (nueva).

**Reportes:** se mantienen los 15 de v3 y se agregan:

| Reporte | Pregunta |
|---|---|
| Matriz de inventario de activos (FOR.01) | ¿Cómo queda la matriz oficial de un proceso o dependencia? |
| Activos que requieren gestión de riesgos | ¿Qué activos deben pasar a la matriz de riesgos y por qué criterio? |
| Activos por importancia | ¿Cuántos activos hay de importancia muy alta, alta, media y baja, por dependencia? |
| Cumplimiento del plan de mantenimiento | ¿Qué se ha mantenido frente a lo programado, por mes? |
| Historial de mantenimientos | ¿Qué mantenimientos tuvo un equipo o una dependencia? |
| Garantías y vida útil | ¿Qué garantías vencen y qué equipos superaron su vida útil? |
| Personas por retirarse con equipos a cargo | ¿Quién termina contrato y aún tiene equipos? |
| Equipos por estructura | ¿Qué tiene cada secretaría, incluidas sus direcciones y áreas? |

---

## 12. Carga de datos

Los Excel **no definen el modelo**; solo alimentan datos. Se hacen tres cargas, todas con vista previa, modo simulación y fila de origen:

1. **Catálogos oficiales** (seeders, no Excel): procesos, árbol de dependencias con equivalencias, municipios, sedes y pisos, ubicación de dependencias, cargos y tipos de equipo, con los valores de [base-de-datos.md](./base-de-datos.md#5-valores-oficiales-de-los-catálogos-para-los-seeders).
2. **Personas** desde las plantas de Talento Humano, Central y Educación, con solo las columnas permitidas (RNF-19).
3. **Equipos** desde la plantilla oficial de carga. Para el inventario 2026 se publica la tabla de correspondencia de sus columnas con la plantilla; sus 175 variantes de dependencia se resuelven con la tabla de equivalencias.
4. **Valoraciones** desde la matriz FOR.01 diligenciada por cada proceso (opcional; también se valora en pantalla).
5. **Plan de mantenimiento** desde el Excel del PAMP.

Todo lo cargado sin serial queda «Pendiente de verificar» y se verifica dentro de un levantamiento (CU-18), que es cuando se pega la etiqueta QR.

**Criterios de aceptación:** ningún catálogo como texto libre; cada equipo con su fila de origen; conteos por sede y piso comparables con los del PAMP; ninguna columna sensible de la planta guardada.

---

## 13. Alcance por fases

| Fase | Alcance | RF |
|---|---|---|
| **1 — Cerrada** (oct-2026) | Inventario, hoja de vida, movimientos, formatos, QR, reportes, administración | 35 esenciales de v3 |
| **2 — Reestructuración con fuentes oficiales** | Modelo v4, catálogos oficiales, personas desde la planta, ficha ampliada, valoración GT-M4, mantenimiento preventivo, matriz FOR.01, cargas nuevas, y los «importantes» de v3 que más pesan (RF-23, RF-47, RF-49, RF-51) | RF-01, 02, 04, 07, 09, 10, 12, 18, 19, 34, 37, 38, 39, 44, 45, 53 a 58, 60 a 62, 65 (esenciales), más RF-23, 47, 49, 51, 52, 59, 63, 64 |
| **3 — Consolidación** | Puestos de trabajo, fotos, historial por fecha, tipos de evento configurables, tablero, anular baja | RF-05, 06, 13, 16, 17, 26, 27, 31, 33, 36, 42 |
| **4 — Mejoras** | Firma en pantalla | RF-32 |

El reparto de la Fase 2 está en [plan-fase2.md](./plan-fase2.md).

---

## 14. Preguntas abiertas

| # | Pregunta | Supuesto actual | Qué cambia si es distinto |
|---|---|---|---|
| 1 | ¿Se confirma que el mantenimiento preventivo entra al alcance? | Sí (PAMP oficial) | Se quitan RF-60 a RF-64 y sus tablas. |
| 2 | ¿Entran los equipos de las instituciones educativas? | No por ahora | Cientos de sedes nuevas en 71 municipios; carga y verificación mucho más grandes. |
| 3 | ¿Solo hardware y soporte, o todos los tipos de activo del GT-M4? | Solo hardware y soporte | Haría falta una ficha de activo sin serial ni componentes. |
| 4 | ¿El propietario y el custodio se registran como cargo o como persona? | Cargo en la valoración, persona en el acta | Cambia `valoraciones_activo`. |
| 5 | Tipo de cada proceso según el mapa del SIG. | Tabla 5.1 de base-de-datos.md | Solo cambia el seeder. |
| 6 | Tenencia de Morindó, Victoria Char, Places Mall; ¿Suricentro sigue en uso? | Por confirmar | Solo cambia el seeder. |
| 7 | ¿De quién dependen Pensiones, Biblioparque y el Grupo Anticontrabando? | Ver base-de-datos.md 5.2 | Solo cambia el árbol. |
| 8 | ¿Qué código del SIG llevarán los formatos (entrega, baja, mantenimiento, acta)? | Sin código | Encabezado de los PDF (RNF-21). |
| 9 | ¿Quién suministra los datos de adquisición (Almacén) y en qué formato? | Excel de Almacén | Plantilla de carga. |
| 10 | ¿El traslado sigue con dos documentos o uno? | Ambos | (Igual que en v3.) |
| 11 | ¿Vista pública reducida al escanear el QR? | Solo con sesión | (Igual que en v3.) |
| 12 | Funciones de la Dirección TICS y del Almacén General en el Decreto 608 (págs. 334 a 353). | No leídas: la herramienta solo extrajo las 80 primeras páginas de 639 | Podrían asignar responsabilidades del inventario y de la baja. |

### Próximos pasos

- [ ] Revisar v4 y el modelo de datos con el profesor y cerrar las preguntas 1 a 4.
- [ ] Aprobar el modelo v4 y crear las migraciones (líder).
- [ ] Pedir a Talento Humano el procedimiento de entrega de la planta (con autorización de tratamiento de datos).
- [ ] Pedir a Almacén los datos de adquisición y la base de códigos de activo.
- [ ] Leer las páginas 334 a 353 del Decreto 608.
- [ ] Arrancar la Fase 2 según [plan-fase2.md](./plan-fase2.md).

---

**Ver también:** [Base de datos v4](./base-de-datos.md) · [Plan de Fase 2](./plan-fase2.md) · [Normas de trabajo](./normas-de-trabajo.md) · [Plan de la Fase 1](./plan-2-semanas.md)
