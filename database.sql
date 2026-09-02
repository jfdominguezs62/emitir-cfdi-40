-- ============================================================
-- Base de datos: emitircfdi_db
-- Sistema de Emisión de CFDI 4.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS emitircfdi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE emitircfdi_db;

-- ============================================================
-- Tabla: users (Usuarios del sistema)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  user VARCHAR(50) NOT NULL UNIQUE,
  pasw1 VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'operador',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuario admin por defecto (password: admin123)
INSERT INTO users (username, user, pasw1, rol) VALUES
('Administrador', 'admin', '$2y$10$YF1J4qKjGGrEHofLhOCiJeqHj0Z1m5qH3Mz2Gp5e7YbK9v8L2x6Qe', 'admin');

-- ============================================================
-- Tabla: emisor_config (Configuración del Emisor)
-- ============================================================
CREATE TABLE IF NOT EXISTS emisor_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rfc VARCHAR(13) NOT NULL,
  razon_social VARCHAR(250) NOT NULL,
  nombre_comercial VARCHAR(250) DEFAULT '',
  regimen_fiscal VARCHAR(5) NOT NULL DEFAULT '601',
  codigo_postal VARCHAR(5) NOT NULL DEFAULT '00000',
  no_certificado VARCHAR(20) DEFAULT '',
  csd_path VARCHAR(250) DEFAULT '',
  csd_key_path VARCHAR(250) DEFAULT '',
  csd_password VARCHAR(250) DEFAULT '',
  pac_username VARCHAR(100) DEFAULT '',
  pac_password VARCHAR(250) DEFAULT '',
  pac_url_pruebas VARCHAR(250) DEFAULT 'https://facturaciontest.sw.com.mx/services/timbrado.asmx',
  pac_url_produccion VARCHAR(250) DEFAULT 'https://facturacion.sw.com.mx/services/timbrado.asmx',
  modo ENUM('pruebas','produccion') DEFAULT 'pruebas',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Configuración por defecto (pruebas)
INSERT INTO emisor_config (rfc, razon_social, nombre_comercial, regimen_fiscal, codigo_postal, modo) VALUES
('EKU9003173C9', 'ESCUELA KEMPER URGATE', 'EKU', '601', '37170', 'pruebas');

-- ============================================================
-- Tabla: clientes (Catálogo de Receptores)
-- ============================================================
CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rfc VARCHAR(13) NOT NULL,
  razon_social VARCHAR(250) NOT NULL,
  regimen_fiscal_receptor VARCHAR(5) NOT NULL DEFAULT '601',
  uso_cfdi VARCHAR(5) NOT NULL DEFAULT 'G03',
  email VARCHAR(150) DEFAULT '',
  telefono VARCHAR(20) DEFAULT '',
  calle VARCHAR(100) DEFAULT '',
  numero_exterior VARCHAR(20) DEFAULT '',
  numero_interior VARCHAR(20) DEFAULT '',
  colonia VARCHAR(100) DEFAULT '',
  codigo_postal VARCHAR(5) DEFAULT '',
  localidad VARCHAR(100) DEFAULT '',
  municipio VARCHAR(100) DEFAULT '',
  estado VARCHAR(100) DEFAULT '',
  pais VARCHAR(5) DEFAULT 'MEX',
  estatus ENUM('activo','inactivo') DEFAULT 'activo',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_rfc (rfc)
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: series (Control de folios)
-- ============================================================
CREATE TABLE IF NOT EXISTS series (
  id INT AUTO_INCREMENT PRIMARY KEY,
  serie VARCHAR(10) NOT NULL UNIQUE,
  folio_actual INT DEFAULT 1,
  activa TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO series (serie, folio_actual, activa) VALUES ('A', 1, 1);

-- ============================================================
-- Tabla: facturas (Encabezado de CFDI)
-- ============================================================
CREATE TABLE IF NOT EXISTS facturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  serie VARCHAR(10) NOT NULL DEFAULT 'A',
  folio INT NOT NULL,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  cliente_id INT NOT NULL,
  forma_pago VARCHAR(5) NOT NULL DEFAULT '01',
  metodo_pago VARCHAR(5) NOT NULL DEFAULT 'PUE',
  exportacion VARCHAR(5) DEFAULT '01',
  subtotal DECIMAL(12,2) DEFAULT 0.00,
  descuento DECIMAL(12,2) DEFAULT 0.00,
  total_iva DECIMAL(12,2) DEFAULT 0.00,
  total DECIMAL(12,2) DEFAULT 0.00,
  uuid VARCHAR(50) DEFAULT NULL,
  fecha_timbrado DATETIME DEFAULT NULL,
  no_certificado VARCHAR(20) DEFAULT '',
  sello_digital TEXT,
  xml_timbrado LONGBLOB,
  estado ENUM('borrador','timbrada','cancelada') DEFAULT 'borrador',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cliente (cliente_id),
  KEY idx_estado (estado),
  KEY idx_uuid (uuid),
  CONSTRAINT fk_factura_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: factura_conceptos (Conceptos del CFDI)
-- ============================================================
CREATE TABLE IF NOT EXISTS factura_conceptos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  factura_id INT NOT NULL,
  clave_prod_serv VARCHAR(10) NOT NULL DEFAULT '84111506',
  clave_unidad VARCHAR(5) NOT NULL DEFAULT 'H87',
  descripcion VARCHAR(500) NOT NULL,
  cantidad DECIMAL(12,4) NOT NULL DEFAULT 1,
  valor_unitario DECIMAL(12,6) NOT NULL DEFAULT 0.00,
  importe DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  descuento DECIMAL(12,2) DEFAULT 0.00,
  base_iva DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tasa_iva DECIMAL(5,4) DEFAULT 0.1600,
  importe_iva DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  KEY idx_factura (factura_id),
  CONSTRAINT fk_concepto_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: xml_importados (CFDI importados de carpetas)
-- ============================================================
CREATE TABLE IF NOT EXISTS xml_importados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  archivo VARCHAR(255) DEFAULT '',
  uuid VARCHAR(50) DEFAULT '',
  version VARCHAR(10) DEFAULT '',
  serie VARCHAR(10) DEFAULT '',
  folio VARCHAR(20) DEFAULT '',
  fecha DATETIME DEFAULT NULL,
  forma_pago VARCHAR(5) DEFAULT '',
  metodo_pago VARCHAR(5) DEFAULT '',
  lugar_expedicion VARCHAR(5) DEFAULT '',
  no_certificado VARCHAR(20) DEFAULT '',
  subtotal DECIMAL(12,2) DEFAULT 0.00,
  descuento DECIMAL(12,2) DEFAULT 0.00,
  total DECIMAL(12,2) DEFAULT 0.00,
  moneda VARCHAR(5) DEFAULT '',
  tipo_comprobante VARCHAR(2) DEFAULT '',
  es_global TINYINT(1) DEFAULT 0,
  exportacion VARCHAR(5) DEFAULT '',
  emisor_rfc VARCHAR(13) DEFAULT '',
  emisor_nombre VARCHAR(250) DEFAULT '',
  emisor_regimen VARCHAR(5) DEFAULT '',
  receptor_rfc VARCHAR(13) DEFAULT '',
  receptor_nombre VARCHAR(250) DEFAULT '',
  receptor_regimen VARCHAR(5) DEFAULT '',
  receptor_uso_cfdi VARCHAR(5) DEFAULT '',
  receptor_cp VARCHAR(5) DEFAULT '',
  impuestos_traslados DECIMAL(12,2) DEFAULT 0.00,
  impuestos_retenidos DECIMAL(12,2) DEFAULT 0.00,
  timbre_uuid VARCHAR(50) DEFAULT '',
  timbre_fecha DATETIME DEFAULT NULL,
  carpeta_origen VARCHAR(500) DEFAULT '',
  fecha_importacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_uuid (uuid),
  KEY idx_rfc_emisor (emisor_rfc),
  KEY idx_rfc_receptor (receptor_rfc),
  KEY idx_fecha (fecha),
  KEY idx_tipo (tipo_comprobante),
  KEY idx_carpeta (carpeta_origen)
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: xml_importados_conceptos (Conceptos de CFDI importados)
-- ============================================================
CREATE TABLE IF NOT EXISTS xml_importados_conceptos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  xml_importado_id INT NOT NULL,
  clave_prod_serv VARCHAR(10) DEFAULT '',
  cantidad DECIMAL(12,4) DEFAULT 1,
  clave_unidad VARCHAR(5) DEFAULT '',
  descripcion VARCHAR(500) DEFAULT '',
  valor_unitario DECIMAL(12,6) DEFAULT 0.00,
  importe DECIMAL(12,2) DEFAULT 0.00,
  descuento DECIMAL(12,2) DEFAULT 0.00,
  objeto_imp VARCHAR(5) DEFAULT '',
  KEY idx_xml_importado (xml_importado_id),
  CONSTRAINT fk_concepto_xml FOREIGN KEY (xml_importado_id) REFERENCES xml_importados(id) ON DELETE CASCADE
) ENGINE=InnoDB;
