-- =====================================================================
-- Sistema de Hoja de Vida de Activos Tecnológicos · Gobernación de Córdoba
-- Modelo de datos v4 · MySQL 8 · equivalente SQL de hoja-vida-activos.dbml
-- Uso: importarlo en dbdiagram.io (Import > MySQL) o en cualquier cliente MySQL.
-- Las migraciones de Laravel siguen siendo la fuente que corre la app.
-- El mantenimiento preventivo no forma parte del modelo (sigue fuera del alcance).
-- =====================================================================

-- Generado desde el DBML y ajustado a mano el 17/10/2026 (sin mantenimiento).

CREATE TABLE `procesos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(120) UNIQUE NOT NULL,
  `tipo` ENUM ('estrategico', 'misional', 'apoyo', 'evaluacion_control') NOT NULL,
  `activo` boolean NOT NULL DEFAULT true,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `dependencias` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `padre_id` bigint COMMENT 'Null solo para el Despacho del Gobernador',
  `tipo` ENUM ('despacho', 'secretaria', 'departamento_administrativo', 'oficina', 'direccion', 'area') NOT NULL,
  `nombre` varchar(160) NOT NULL COMMENT 'Nombre oficial según Decreto 609 de 2023',
  `nombre_corto` varchar(60) COMMENT 'Como se muestra en listas: "Dirección TIC"',
  `sigla` varchar(20),
  `proceso_id` bigint COMMENT 'Proceso al que pertenece (GT-M4 4.2.1)',
  `planta` ENUM ('central', 'educacion') NOT NULL DEFAULT 'central',
  `acto_administrativo` varchar(60) COMMENT 'Ej.: "Decreto 609 de 2023"',
  `activa` boolean NOT NULL DEFAULT true,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `dependencia_equivalencias` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `dependencia_id` bigint NOT NULL,
  `texto` varchar(200) UNIQUE NOT NULL COMMENT 'Variante tal como llega en un Excel: "DIRECION DE SISTEMAS", "Sec Educación"',
  `origen` varchar(60) COMMENT 'FOR.01, inventario 2026, PAMP, planta…',
  `created_at` timestamp
);

CREATE TABLE `municipios` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `codigo_dane` char(5) UNIQUE NOT NULL,
  `nombre` varchar(80) UNIQUE NOT NULL
);

CREATE TABLE `sedes` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(120) UNIQUE NOT NULL,
  `direccion` varchar(160) COMMENT 'Reservada en Casa Refugio: no se muestra en listados ni reportes',
  `barrio` varchar(80),
  `municipio_id` bigint NOT NULL,
  `tipo` ENUM ('administrativa', 'salud', 'social', 'cultural', 'institucion_educativa') NOT NULL DEFAULT 'administrativa',
  `tenencia` ENUM ('propia', 'arrendada', 'comodato', 'por_confirmar') NOT NULL DEFAULT 'por_confirmar',
  `direccion_reservada` boolean NOT NULL DEFAULT false,
  `activa` boolean NOT NULL DEFAULT true,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `pisos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `sede_id` bigint NOT NULL,
  `numero` smallint NOT NULL COMMENT '0 = planta baja o local único',
  `nombre` varchar(60) COMMENT 'Ej.: "Local 44"',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `ubicaciones_dependencia` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `dependencia_id` bigint NOT NULL,
  `piso_id` bigint NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `cargos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `codigo` char(3) COMMENT 'Primer dígito = nivel (Decreto 607, art. 16). Null para contratistas',
  `denominacion` varchar(120) NOT NULL,
  `nivel` ENUM ('directivo', 'asesor', 'profesional', 'tecnico', 'asistencial') NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `personas` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `tipo_documento` ENUM ('CC', 'CE', 'PPT', 'PA', 'TI') NOT NULL DEFAULT 'CC',
  `numero_documento` varchar(20) UNIQUE NOT NULL COMMENT 'Dato personal (Ley 1581): enmascarado en listados',
  `nombres` varchar(80) NOT NULL,
  `apellidos` varchar(80) NOT NULL,
  `correo_institucional` varchar(120),
  `cargo_id` bigint,
  `grado` char(2) COMMENT 'Grado salarial del empleo (01 a 10)',
  `encargo` boolean NOT NULL DEFAULT false COMMENT 'Cargo ejercido en encargo (E)',
  `dependencia_id` bigint COMMENT 'Ubicación real ("REAL UBICACIÓN" de la planta)',
  `jefe_inmediato_id` bigint,
  `planta` ENUM ('central', 'educacion'),
  `tipo_vinculacion` ENUM ('planta', 'contratista', 'pasante') NOT NULL,
  `tipo_provision` ENUM ('carrera_administrativa', 'libre_nombramiento', 'provisional', 'temporal', 'periodo', 'no_aplica') NOT NULL DEFAULT 'no_aplica',
  `numero_contrato` varchar(40) COMMENT 'Solo contratistas',
  `fecha_fin_vinculacion` date COMMENT 'Fin de contrato o retiro: dispara el paz y salvo (RF-23)',
  `activa` boolean NOT NULL DEFAULT true,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `tipos_equipo` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(80) UNIQUE NOT NULL,
  `familia` ENUM ('computo', 'video', 'impresion', 'digitalizacion', 'conectividad', 'seguridad_electronica', 'energia', 'climatizacion', 'proyeccion_audio', 'telefonia') NOT NULL,
  `tipo_activo` ENUM ('hardware', 'soporte') NOT NULL DEFAULT 'hardware',
  `usa_configuracion_computo` boolean NOT NULL DEFAULT false COMMENT 'Muestra SO, antivirus, red y componentes internos',
  `campos_aplicables` json COMMENT 'Claves de equipos.caracteristicas que aplican: pulgadas, VA, puertos, lúmenes…',
  `vida_util_meses` smallint COMMENT 'Valor sugerido para equipos nuevos',
  `activo` boolean NOT NULL DEFAULT true,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `marcas` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(60) UNIQUE NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `modelos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `marca_id` bigint NOT NULL,
  `tipo_equipo_id` bigint NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `sistemas_operativos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(60) UNIQUE NOT NULL,
  `fin_soporte` date COMMENT 'Fecha de fin de soporte del fabricante: alimenta el reporte de obsolescencia',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `tipos_componente` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(60) UNIQUE NOT NULL,
  `es_periferico` boolean NOT NULL DEFAULT false,
  `obligatorio_en_computo` boolean NOT NULL DEFAULT false COMMENT 'RAM, disco y procesador',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `motivos_baja` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(80) UNIQUE NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `proveedores` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `nit` varchar(20) UNIQUE,
  `razon_social` varchar(160) NOT NULL,
  `telefono` varchar(30),
  `correo` varchar(120),
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `equipos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `qr_uuid` char(36) UNIQUE NOT NULL COMMENT 'Identificador permanente del QR (RN-19)',
  `numero_activo` int UNIQUE NOT NULL COMMENT 'Consecutivo "ID ACTIVO" del FOR.01, lo asigna el sistema',
  `serial` varchar(80) UNIQUE NOT NULL COMMENT 'PENDIENTE-REG-###### si aún no se ha tomado (RN-02)',
  `codigo_activo` varchar(20) COMMENT 'Placa de Almacén I1-######. Índice no único: RN-03',
  `codigo_activo_justificacion` text,
  `tipo_equipo_id` bigint NOT NULL,
  `marca_id` bigint NOT NULL,
  `modelo_id` bigint,
  `descripcion` varchar(255) COMMENT '"Descripción Activo" del FOR.01',
  `caracteristicas` json COMMENT 'Valores según tipos_equipo.campos_aplicables',
  `proceso_id` bigint COMMENT 'Proceso al que da soporte (FOR.01)',
  `propiedad` ENUM ('gobernacion', 'tercero') NOT NULL DEFAULT 'gobernacion',
  `proveedor_tercero_id` bigint COMMENT 'Dueño si propiedad = tercero',
  `figura_tercero` ENUM ('comodato', 'convenio', 'proveedor', 'arrendamiento'),
  `forma_ingreso` ENUM ('compra', 'donacion', 'comodato', 'convenio', 'arrendamiento', 'carga_inicial') NOT NULL,
  `fecha_adquisicion` date,
  `valor_adquisicion` decimal(15,2) COMMENT 'COP',
  `proveedor_id` bigint COMMENT 'A quién se compró',
  `numero_contrato` varchar(40),
  `numero_factura` varchar(40),
  `fecha_fin_garantia` date,
  `vida_util_meses` smallint,
  `estado_funcionamiento` ENUM ('bueno', 'regular', 'malo', 'no_funcional') NOT NULL DEFAULT 'bueno',
  `estado_ciclo_vida` ENUM ('en_servicio', 'sin_asignar', 'dado_de_baja') NOT NULL DEFAULT 'sin_asignar',
  `verificacion` ENUM ('verificado', 'pendiente_de_verificar') NOT NULL DEFAULT 'pendiente_de_verificar',
  `puesto_trabajo_id` bigint,
  `importacion_id` bigint,
  `fila_origen_importacion` int,
  `observaciones` text,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `valoraciones_activo` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` bigint NOT NULL,
  `evento_id` bigint COMMENT 'Evento "valoracion" que la creó',
  `levantamiento_id` bigint,
  `contiene_datos_personales` ENUM ('si', 'no', 'no_aplica') NOT NULL,
  `clasificacion_datos` ENUM ('publico', 'privado', 'semiprivado', 'sensible', 'reservado', 'clasificado', 'no_aplica') NOT NULL DEFAULT 'no_aplica',
  `infraestructura_critica` ENUM ('si', 'no', 'no_aplica') NOT NULL,
  `continuidad_negocio` ENUM ('si', 'no', 'no_aplica') NOT NULL,
  `propietario_cargo` varchar(120) NOT NULL COMMENT 'Director o líder del proceso (GT-M4 4.2.3)',
  `custodio_cargo` varchar(120) NOT NULL COMMENT 'Cargo que administra el activo: "Ingeniero de Soporte"',
  `tipo_ubicacion` ENUM ('fisico', 'electronico', 'fisico_electronico') NOT NULL DEFAULT 'fisico',
  `tiempo_retencion` varchar(40) COMMENT '"Indeterminado", "6 meses", "1 año"',
  `confidencialidad` tinyint NOT NULL COMMENT '1 a 4 (GT-M4 tabla 3)',
  `integridad` tinyint NOT NULL COMMENT '1 a 4 (tabla 4)',
  `disponibilidad` tinyint NOT NULL COMMENT '1 a 4 (tabla 5)',
  `total` tinyint NOT NULL COMMENT 'Columna generada: C + I + D',
  `importancia` ENUM ('baja', 'media', 'alta', 'muy_alta') NOT NULL COMMENT 'Calculada con la tabla 6',
  `requiere_gestion_riesgo` boolean NOT NULL COMMENT 'Algún criterio = 4, o importancia alta/muy alta (GT-M4 4.2.6)',
  `controles_monitoreo` varchar(200) COMMENT 'Cámaras, sensor de humo, temperatura, humedad (hoja "Datos")',
  `vigente` boolean NOT NULL DEFAULT true COMMENT 'Solo una vigente por equipo',
  `fecha` date NOT NULL,
  `usuario_id` bigint NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `configuraciones_computo` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` bigint UNIQUE NOT NULL,
  `sistema_operativo_id` bigint,
  `licencia_so` varchar(80),
  `ofimatica` varchar(80) COMMENT 'Ej.: Office Home and Business',
  `licencia_ofimatica` varchar(80),
  `tiene_antivirus` boolean NOT NULL DEFAULT false,
  `antivirus_producto` varchar(80),
  `nombre_equipo` varchar(60) COMMENT 'Hostname',
  `dominio` varchar(60),
  `direccion_ip` varchar(45),
  `direccion_mac` varchar(17),
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `componentes` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` bigint NOT NULL,
  `tipo_componente_id` bigint NOT NULL,
  `capacidad_caracteristica` varchar(80),
  `marca` varchar(60),
  `modelo` varchar(80),
  `serial` varchar(80),
  `estado` ENUM ('bueno', 'regular', 'malo', 'no_funcional'),
  `fecha_instalacion` date,
  `fecha_retiro` date COMMENT 'Null = instalado',
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `puestos_trabajo` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `codigo` varchar(30) UNIQUE NOT NULL,
  `piso_id` bigint NOT NULL,
  `dependencia_id` bigint,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `asignaciones` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` bigint NOT NULL,
  `persona_id` bigint COMMENT 'Null = sin responsable (bodega)',
  `piso_id` bigint NOT NULL COMMENT 'El piso ya define la sede',
  `dependencia_id` bigint,
  `puesto_trabajo_id` bigint,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date COMMENT 'Null = asignación actual',
  `evento_origen_id` bigint,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `eventos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` bigint NOT NULL,
  `tipo` ENUM ('alta', 'traslado_responsable', 'cambio_componente', 'diagnostico', 'valoracion', 'baja', 'actualizacion_datos', 'anulacion_aclaracion') NOT NULL,
  `fecha` datetime NOT NULL,
  `es_historico` boolean NOT NULL DEFAULT false COMMENT 'Alta de carga inicial: exento de RN-13',
  `descripcion` text,
  `valores` json COMMENT '{antes: {...}, despues: {...}}',
  `usuario_id` bigint NOT NULL COMMENT 'Autor, nunca cambia (RF-11)',
  `estado_firma` ENUM ('no_aplica', 'pendiente_de_firma', 'completo') NOT NULL DEFAULT 'no_aplica',
  `evento_anulado_id` bigint,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `cambios_componente` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `evento_id` bigint UNIQUE NOT NULL,
  `accion` ENUM ('agregar', 'cambiar', 'quitar') NOT NULL,
  `componente_retirado_id` bigint,
  `serial_retirado` varchar(80),
  `componente_instalado_id` bigint,
  `serial_instalado` varchar(80),
  `motivo` text,
  `destino_retirado` ENUM ('bodega', 'otro_equipo', 'descarte'),
  `equipo_destino_id` bigint COMMENT 'Si destino = otro_equipo',
  `created_at` timestamp
);

CREATE TABLE `diagnosticos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `evento_id` bigint UNIQUE NOT NULL,
  `estado_encontrado` text NOT NULL,
  `causa` text,
  `recomendaciones` text,
  `es_baja` boolean NOT NULL DEFAULT false,
  `motivo_baja_id` bigint,
  `created_at` timestamp
);

CREATE TABLE `documentos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `evento_id` bigint NOT NULL,
  `tipo` ENUM ('formato_entrega', 'formato_baja', 'acta_aceptacion_activos', 'foto', 'otro') NOT NULL,
  `consecutivo` varchar(30),
  `archivo_path` varchar(255) NOT NULL,
  `firmado` boolean NOT NULL DEFAULT false,
  `fecha` date NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `etiquetas_qr` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` bigint NOT NULL,
  `fecha_impresion` datetime NOT NULL,
  `usuario_id` bigint NOT NULL,
  `motivo` ENUM ('primera_impresion', 'reposicion') NOT NULL,
  `lote` varchar(40),
  `created_at` timestamp
);

CREATE TABLE `levantamientos_activos` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `codigo` varchar(30) UNIQUE NOT NULL,
  `dependencia_id` bigint NOT NULL,
  `proceso_id` bigint,
  `sede_id` bigint NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_aceptacion` date,
  `propietario_persona_id` bigint COMMENT 'Director o líder del proceso',
  `custodio_persona_id` bigint,
  `estado` ENUM ('en_curso', 'pendiente_de_firma', 'aceptado') NOT NULL DEFAULT 'en_curso',
  `acta_path` varchar(255) COMMENT 'Acta de identificación y aceptación firmada',
  `usuario_id` bigint NOT NULL,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `users` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `persona_id` bigint COMMENT 'Funcionario o contratista TIC que usa el sistema',
  `perfil` ENUM ('administrador', 'usuario') NOT NULL DEFAULT 'usuario',
  `activo` boolean NOT NULL DEFAULT true,
  `debe_cambiar_contrasena` boolean NOT NULL DEFAULT true,
  `remember_token` varchar(100),
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `auditorias` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `usuario_id` bigint,
  `accion` varchar(60) NOT NULL,
  `entidad` varchar(60) NOT NULL,
  `entidad_id` bigint,
  `valor_anterior` json,
  `valor_nuevo` json,
  `ip` varchar(45),
  `created_at` timestamp NOT NULL
);

CREATE TABLE `importaciones` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL COMMENT 'equipos | personas | valoraciones',
  `archivo` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL,
  `usuario_id` bigint NOT NULL,
  `filas` int NOT NULL,
  `errores` json,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE UNIQUE INDEX `dependencias_index_0` ON `dependencias` (`padre_id`, `nombre`);

CREATE UNIQUE INDEX `pisos_index_1` ON `pisos` (`sede_id`, `numero`);

CREATE UNIQUE INDEX `ubicaciones_dependencia_index_2` ON `ubicaciones_dependencia` (`dependencia_id`, `piso_id`);

CREATE UNIQUE INDEX `cargos_index_3` ON `cargos` (`codigo`, `denominacion`);

CREATE UNIQUE INDEX `modelos_index_4` ON `modelos` (`marca_id`, `tipo_equipo_id`, `nombre`);

CREATE INDEX `equipos_index_5` ON `equipos` (`codigo_activo`);

CREATE INDEX `equipos_index_6` ON `equipos` (`estado_ciclo_vida`, `tipo_equipo_id`);

ALTER TABLE `procesos` COMMENT = 'Mapa de procesos del SIG. 17 procesos listados en la hoja "Datos" del formato FOR.01.';

ALTER TABLE `dependencias` COMMENT = 'Árbol: Despacho → Secretaría/Oficina/DAP → Dirección → Área. Reemplaza la lista plana de v3.';

ALTER TABLE `dependencia_equivalencias` COMMENT = 'Tabla de equivalencias editable (RF-39). Incluye los nombres anteriores al Decreto 609.';

ALTER TABLE `municipios` COMMENT = 'Los 30 municipios de Córdoba (DIVIPOLA).';

ALTER TABLE `sedes` COMMENT = 'GT-M4 lo llama "Sucursal". Valores reales en el PAMP 2026.';

ALTER TABLE `pisos` COMMENT = 'En v3 los pisos eran 1 a 8 para todas las sedes. Ahora cada sede tiene los suyos.';

ALTER TABLE `ubicaciones_dependencia` COMMENT = 'En qué sede y piso funciona cada dependencia (tablas del PAMP). Sirve para prellenar traslados y validar ubicaciones.';

ALTER TABLE `cargos` COMMENT = 'Nomenclatura oficial de empleos (Decreto 607 de 2023).';

ALTER TABLE `personas` COMMENT = 'Minimización de datos (Ley 1581): de las 77 columnas de la planta solo se guardan las necesarias para responder por un equipo. No se guardan salario, RH, estado civil, etnia, dirección, hijos ni salud.';

ALTER TABLE `modelos` COMMENT = 'Nuevo en v4: el modelo deja de ser texto libre.';

ALTER TABLE `proveedores` COMMENT = 'Quien vendió, donó o presta el equipo.';

ALTER TABLE `equipos` COMMENT = 'Centro del modelo. Ubicación y responsable actuales viven en asignaciones (la abierta).';

ALTER TABLE `valoraciones_activo` COMMENT = 'Valoración CID del Manual GT-M4. Histórica: un incidente o el líder del proceso pueden reclasificarla. Solo aplica a tipo_activo = hardware.';

ALTER TABLE `asignaciones` COMMENT = 'Una sola abierta por equipo (RN-04).';

ALTER TABLE `eventos` COMMENT = 'Solo se escribe vía HistorialService::registrar(). update()/delete() bloqueados.';

ALTER TABLE `levantamientos_activos` COMMENT = 'La matriz de activos por proceso y su acta firmada (GT-M4 4.1 c y 4.1.2). Reemplaza la "verificación en sitio" informal de v3.';

ALTER TABLE `auditorias` COMMENT = 'Inmutable, mínimo 5 años (RNF-10).';

ALTER TABLE `dependencias` ADD FOREIGN KEY (`padre_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `dependencias` ADD FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`);

ALTER TABLE `dependencia_equivalencias` ADD FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `sedes` ADD FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`);

ALTER TABLE `pisos` ADD FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

ALTER TABLE `ubicaciones_dependencia` ADD FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `ubicaciones_dependencia` ADD FOREIGN KEY (`piso_id`) REFERENCES `pisos` (`id`);

ALTER TABLE `personas` ADD FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`);

ALTER TABLE `personas` ADD FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `personas` ADD FOREIGN KEY (`jefe_inmediato_id`) REFERENCES `personas` (`id`);

ALTER TABLE `modelos` ADD FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`);

ALTER TABLE `modelos` ADD FOREIGN KEY (`tipo_equipo_id`) REFERENCES `tipos_equipo` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`tipo_equipo_id`) REFERENCES `tipos_equipo` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`proveedor_tercero_id`) REFERENCES `proveedores` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`puesto_trabajo_id`) REFERENCES `puestos_trabajo` (`id`);

ALTER TABLE `equipos` ADD FOREIGN KEY (`importacion_id`) REFERENCES `importaciones` (`id`);

ALTER TABLE `valoraciones_activo` ADD FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `valoraciones_activo` ADD FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`);

ALTER TABLE `valoraciones_activo` ADD FOREIGN KEY (`levantamiento_id`) REFERENCES `levantamientos_activos` (`id`);

ALTER TABLE `valoraciones_activo` ADD FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

ALTER TABLE `configuraciones_computo` ADD FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `configuraciones_computo` ADD FOREIGN KEY (`sistema_operativo_id`) REFERENCES `sistemas_operativos` (`id`);

ALTER TABLE `componentes` ADD FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `componentes` ADD FOREIGN KEY (`tipo_componente_id`) REFERENCES `tipos_componente` (`id`);

ALTER TABLE `puestos_trabajo` ADD FOREIGN KEY (`piso_id`) REFERENCES `pisos` (`id`);

ALTER TABLE `puestos_trabajo` ADD FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `asignaciones` ADD FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `asignaciones` ADD FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`);

ALTER TABLE `asignaciones` ADD FOREIGN KEY (`piso_id`) REFERENCES `pisos` (`id`);

ALTER TABLE `asignaciones` ADD FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `asignaciones` ADD FOREIGN KEY (`puesto_trabajo_id`) REFERENCES `puestos_trabajo` (`id`);

ALTER TABLE `asignaciones` ADD FOREIGN KEY (`evento_origen_id`) REFERENCES `eventos` (`id`);

ALTER TABLE `eventos` ADD FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `eventos` ADD FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

ALTER TABLE `eventos` ADD FOREIGN KEY (`evento_anulado_id`) REFERENCES `eventos` (`id`);

ALTER TABLE `cambios_componente` ADD FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`);

ALTER TABLE `cambios_componente` ADD FOREIGN KEY (`componente_retirado_id`) REFERENCES `componentes` (`id`);

ALTER TABLE `cambios_componente` ADD FOREIGN KEY (`componente_instalado_id`) REFERENCES `componentes` (`id`);

ALTER TABLE `cambios_componente` ADD FOREIGN KEY (`equipo_destino_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `diagnosticos` ADD FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`);

ALTER TABLE `diagnosticos` ADD FOREIGN KEY (`motivo_baja_id`) REFERENCES `motivos_baja` (`id`);

ALTER TABLE `documentos` ADD FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`);

ALTER TABLE `etiquetas_qr` ADD FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

ALTER TABLE `etiquetas_qr` ADD FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

ALTER TABLE `levantamientos_activos` ADD FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias` (`id`);

ALTER TABLE `levantamientos_activos` ADD FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`);

ALTER TABLE `levantamientos_activos` ADD FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

ALTER TABLE `levantamientos_activos` ADD FOREIGN KEY (`propietario_persona_id`) REFERENCES `personas` (`id`);

ALTER TABLE `levantamientos_activos` ADD FOREIGN KEY (`custodio_persona_id`) REFERENCES `personas` (`id`);

ALTER TABLE `levantamientos_activos` ADD FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`);

ALTER TABLE `auditorias` ADD FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

ALTER TABLE `importaciones` ADD FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);
