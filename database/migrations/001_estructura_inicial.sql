-- GECOPRE · Estructura inicial para MySQL 8.0.30
-- Ejecutar una sola vez después de revisar las reglas de negocio.

CREATE DATABASE IF NOT EXISTS gecopre
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gecopre;

CREATE TABLE migraciones (
    archivo VARCHAR(180) PRIMARY KEY,
    aplicado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE permisos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_permisos_codigo (codigo)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(120) NOT NULL,
    apellidos VARCHAR(120) NOT NULL,
    correo VARCHAR(180) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB;

CREATE TABLE usuario_roles (
    usuario_id BIGINT UNSIGNED NOT NULL,
    rol_id BIGINT UNSIGNED NOT NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, rol_id),
    CONSTRAINT fk_usuario_roles_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_usuario_roles_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE rol_permisos (
    rol_id BIGINT UNSIGNED NOT NULL,
    permiso_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    CONSTRAINT fk_rol_permisos_rol FOREIGN KEY (rol_id) REFERENCES roles(id),
    CONSTRAINT fk_rol_permisos_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id)
) ENGINE=InnoDB;

CREATE TABLE localidades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_localidades_codigo (codigo)
) ENGINE=InnoDB;

CREATE TABLE zonas_geograficas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    localidad_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_zonas_geograficas_localidad_codigo (localidad_id, codigo),
    CONSTRAINT fk_zonas_geograficas_localidad FOREIGN KEY (localidad_id) REFERENCES localidades(id)
) ENGINE=InnoDB;

CREATE TABLE fuentes_abastecimiento (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    localidad_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_fuentes_localidad_codigo (localidad_id, codigo),
    CONSTRAINT fk_fuentes_localidad FOREIGN KEY (localidad_id) REFERENCES localidades(id)
) ENGINE=InnoDB;

CREATE TABLE sectores_operacionales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zona_geografica_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_sectores_zona_codigo (zona_geografica_id, codigo),
    CONSTRAINT fk_sectores_zona FOREIGN KEY (zona_geografica_id) REFERENCES zonas_geograficas(id)
) ENGINE=InnoDB;

CREATE TABLE zonas_presion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector_operacional_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_zonas_presion_sector_nombre (sector_operacional_id, nombre),
    CONSTRAINT fk_zonas_presion_sector FOREIGN KEY (sector_operacional_id) REFERENCES sectores_operacionales(id),
    CONSTRAINT ck_zonas_presion_nombre CHECK (nombre IN ('Alta', 'Media', 'Baja'))
) ENGINE=InnoDB;

CREATE TABLE puntos_control (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    zona_presion_id BIGINT UNSIGNED NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_puntos_control_codigo (codigo),
    KEY ix_puntos_control_zona_presion (zona_presion_id),
    CONSTRAINT fk_puntos_control_zona_presion FOREIGN KEY (zona_presion_id) REFERENCES zonas_presion(id)
) ENGINE=InnoDB;

CREATE TABLE fuentes_sectores (
    fuente_abastecimiento_id BIGINT UNSIGNED NOT NULL,
    sector_operacional_id BIGINT UNSIGNED NOT NULL,
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE NULL,
    PRIMARY KEY (fuente_abastecimiento_id, sector_operacional_id, vigente_desde),
    CONSTRAINT fk_fuentes_sectores_fuente FOREIGN KEY (fuente_abastecimiento_id) REFERENCES fuentes_abastecimiento(id),
    CONSTRAINT fk_fuentes_sectores_sector FOREIGN KEY (sector_operacional_id) REFERENCES sectores_operacionales(id),
    CONSTRAINT ck_fuentes_sectores_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
) ENGINE=InnoDB;

CREATE TABLE indicadores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    unidad VARCHAR(30) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_indicadores_codigo (codigo)
) ENGINE=InnoDB;

CREATE TABLE periodos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    localidad_id BIGINT UNSIGNED NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    mes TINYINT UNSIGNED NOT NULL,
    estado ENUM('borrador', 'en_revision', 'aprobado', 'cerrado') NOT NULL DEFAULT 'borrador',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_periodos_localidad_anio_mes (localidad_id, anio, mes),
    CONSTRAINT fk_periodos_localidad FOREIGN KEY (localidad_id) REFERENCES localidades(id),
    CONSTRAINT ck_periodos_mes CHECK (mes BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE plan_monitoreo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    periodo_id BIGINT UNSIGNED NOT NULL,
    punto_control_id BIGINT UNSIGNED NOT NULL,
    indicador_id BIGINT UNSIGNED NOT NULL,
    requerido TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_plan_monitoreo (periodo_id, punto_control_id, indicador_id),
    CONSTRAINT fk_plan_monitoreo_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id),
    CONSTRAINT fk_plan_monitoreo_punto FOREIGN KEY (punto_control_id) REFERENCES puntos_control(id),
    CONSTRAINT fk_plan_monitoreo_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id)
) ENGINE=InnoDB;

CREATE TABLE conexiones_mensuales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    periodo_id BIGINT UNSIGNED NOT NULL,
    zona_presion_id BIGINT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NULL,
    estado_dato ENUM('registrado', 'pendiente', 'no_disponible') NOT NULL DEFAULT 'pendiente',
    observacion VARCHAR(500) NULL,
    registrado_por BIGINT UNSIGNED NULL,
    registrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conexiones_periodo_zona (periodo_id, zona_presion_id),
    CONSTRAINT fk_conexiones_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id),
    CONSTRAINT fk_conexiones_zona FOREIGN KEY (zona_presion_id) REFERENCES zonas_presion(id),
    CONSTRAINT fk_conexiones_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    CONSTRAINT ck_conexiones_estado_valor CHECK (
        (estado_dato = 'registrado' AND cantidad IS NOT NULL)
        OR (estado_dato <> 'registrado' AND cantidad IS NULL)
    )
) ENGINE=InnoDB;

CREATE TABLE registros_mensuales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    periodo_id BIGINT UNSIGNED NOT NULL,
    punto_control_id BIGINT UNSIGNED NOT NULL,
    indicador_id BIGINT UNSIGNED NOT NULL,
    valor_decimal DECIMAL(10,4) NULL,
    estado_dato ENUM('registrado', 'pendiente', 'no_disponible') NOT NULL DEFAULT 'pendiente',
    observacion VARCHAR(500) NULL,
    registrado_por BIGINT UNSIGNED NULL,
    registrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_registros_periodo_punto_indicador (periodo_id, punto_control_id, indicador_id),
    KEY ix_registros_periodo_indicador (periodo_id, indicador_id),
    CONSTRAINT fk_registros_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id),
    CONSTRAINT fk_registros_punto FOREIGN KEY (punto_control_id) REFERENCES puntos_control(id),
    CONSTRAINT fk_registros_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id),
    CONSTRAINT fk_registros_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    CONSTRAINT ck_registros_estado_valor CHECK (
        (estado_dato = 'registrado' AND valor_decimal IS NOT NULL)
        OR (estado_dato <> 'registrado' AND valor_decimal IS NULL)
    ),
    CONSTRAINT ck_registros_valor_no_negativo CHECK (valor_decimal IS NULL OR valor_decimal >= 0)
) ENGINE=InnoDB;

CREATE TABLE metas_indicador (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    indicador_id BIGINT UNSIGNED NOT NULL,
    ambito ENUM('eps', 'localidad') NOT NULL,
    referencia VARCHAR(120) NOT NULL,
    valor DECIMAL(10,4) NOT NULL,
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_metas_vigencia (indicador_id, ambito, referencia, vigente_desde),
    CONSTRAINT fk_metas_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id),
    CONSTRAINT ck_metas_valor CHECK (valor > 0),
    CONSTRAINT ck_metas_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
) ENGINE=InnoDB;

CREATE TABLE versiones_calculo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    periodo_id BIGINT UNSIGNED NOT NULL,
    indicador_id BIGINT UNSIGNED NOT NULL,
    numero_version INT UNSIGNED NOT NULL,
    regla_version VARCHAR(50) NOT NULL,
    ejecutado_por BIGINT UNSIGNED NULL,
    ejecutado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_versiones_calculo (periodo_id, indicador_id, numero_version),
    CONSTRAINT fk_versiones_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id),
    CONSTRAINT fk_versiones_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id),
    CONSTRAINT fk_versiones_usuario FOREIGN KEY (ejecutado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE resultados_calculo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version_calculo_id BIGINT UNSIGNED NOT NULL,
    tipo_ambito ENUM('zona_presion', 'sector_operacional', 'zona_geografica', 'fuente_abastecimiento', 'localidad') NOT NULL,
    ambito_id BIGINT UNSIGNED NULL,
    valor DECIMAL(10,4) NULL,
    conexiones_utilizadas INT UNSIGNED NULL,
    cobertura_esperada INT UNSIGNED NOT NULL DEFAULT 0,
    cobertura_obtenida INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('completo', 'parcial', 'no_disponible') NOT NULL,
    KEY ix_resultados_version_ambito (version_calculo_id, tipo_ambito, ambito_id),
    CONSTRAINT fk_resultados_version FOREIGN KEY (version_calculo_id) REFERENCES versiones_calculo(id),
    CONSTRAINT ck_resultados_cobertura CHECK (cobertura_obtenida <= cobertura_esperada)
) ENGINE=InnoDB;

CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NULL,
    entidad VARCHAR(80) NOT NULL,
    entidad_id BIGINT UNSIGNED NOT NULL,
    accion VARCHAR(80) NOT NULL,
    detalle_anterior JSON NULL,
    detalle_nuevo JSON NULL,
    motivo VARCHAR(500) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_auditoria_entidad (entidad, entidad_id),
    KEY ix_auditoria_creado_en (creado_en),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

INSERT INTO indicadores (codigo, nombre, unidad) VALUES
    ('continuidad', 'Continuidad', 'horas por día'),
    ('presion', 'Presión', 'm.c.a.');

INSERT INTO metas_indicador (indicador_id, ambito, referencia, valor, vigente_desde) VALUES
    ((SELECT id FROM indicadores WHERE codigo = 'continuidad'), 'localidad', 'SUNASS 2026', 17.0000, '2026-01-01'),
    ((SELECT id FROM indicadores WHERE codigo = 'continuidad'), 'eps', 'EPS ILO 2026', 19.2000, '2026-01-01'),
    ((SELECT id FROM indicadores WHERE codigo = 'presion'), 'localidad', 'SUNASS 2026', 28.0000, '2026-01-01'),
    ((SELECT id FROM indicadores WHERE codigo = 'presion'), 'eps', 'EPS ILO 2026', 28.0000, '2026-01-01');

INSERT INTO roles (nombre, descripcion) VALUES
    ('Administrador', 'Administra catálogos, usuarios, reglas y cierres.'),
    ('Operador', 'Registra mediciones y conexiones en su ámbito asignado.'),
    ('Supervisor', 'Revisa, aprueba y cierra períodos.'),
    ('Consulta', 'Consulta y exporta información autorizada.');

INSERT INTO migraciones (archivo) VALUES ('001_estructura_inicial.sql');
