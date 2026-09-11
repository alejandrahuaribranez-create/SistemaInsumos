-- ============================================================================
-- RedInsumos - Base de Datos Bolivia MVP v1.1 - OFICIAL
-- Motor objetivo: MySQL 8.0+
-- Fecha de consolidacion: 2026-08-20
-- Estado: BASE OFICIAL PARA EL DESARROLLO DEL SISTEMA
-- Reemplaza: db_redinsumos_bolivia_mvp_v1_0_OFICIAL.sql
-- Cambios v1.1: roles VENDEDOR/ALMACENERO y rutinas almacenadas del MVP
--
-- Alcance:
--   Autenticacion unificada, perfiles de cliente, catalogo, carrito, pedidos,
--   pagos manuales/conciliacion, proveedores, compras, inventario y auditoria.
--   Modelo adaptado al contexto comercial boliviano.
--
-- Fuera del MVP:
--   Multiples proveedores por producto, listas/promociones avanzadas,
--   turnos/cierre de caja, garantias/RMA completo e integracion fiscal SIN/SIAT.
--
-- Contexto del esquema:
--   Bolivia (CI/NIT, complemento de CI, departamento/municipio, BOB/Bs).
--   La integracion fiscal SIN/SIAT no forma parte del MVP; se conserva aparte
--   en schema_future_bolivia.sql para una fase posterior.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS red_insumos
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE red_insumos;

-- ============================================================================
-- CATALOGOS Y CONFIGURACION BASICA
-- ============================================================================

CREATE TABLE roles (
  id_rol INT NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(30) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_roles_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
  id_categoria INT NOT NULL AUTO_INCREMENT,
  id_padre INT DEFAULT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre),
  KEY idx_categorias_padre (id_padre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marcas (
  id_marca INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id_marca),
  UNIQUE KEY uq_marcas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE metodos_pago (
  id_metodo_pago INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_metodo_pago),
  UNIQUE KEY uq_metodos_pago_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE metodos_entrega (
  id_metodo_entrega INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  tipo ENUM('despacho','retiro_tienda') NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id_metodo_entrega),
  UNIQUE KEY uq_metodos_entrega_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tarifas_envio (
  id_tarifa INT NOT NULL AUTO_INCREMENT,
  id_metodo_entrega INT NOT NULL,
  departamento VARCHAR(100) NOT NULL,
  municipio VARCHAR(100) DEFAULT NULL,
  zona VARCHAR(100) DEFAULT NULL,
  precio DECIMAL(12,2) NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id_tarifa),
  KEY idx_tarifas_metodo (id_metodo_entrega),
  KEY idx_tarifas_ubicacion (departamento, municipio),
  CONSTRAINT chk_tarifa_precio CHECK (precio >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parametros_sistema (
  clave VARCHAR(80) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  descripcion VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- IDENTIDAD Y CLIENTES
-- ============================================================================
-- usuarios es la unica fuente de autenticacion para cualquier cuenta.
-- clientes representa el perfil comercial. Un cliente de mostrador puede no
-- tener id_usuario; un cliente web debe tenerlo asociado.
-- ============================================================================

CREATE TABLE usuarios (
  id_usuario INT NOT NULL AUTO_INCREMENT,
  id_rol INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_rol (id_rol),
  KEY idx_usuarios_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id_cliente INT NOT NULL AUTO_INCREMENT,
  id_usuario INT DEFAULT NULL,
  tipo_documento ENUM('CI','NIT','CE','PASAPORTE','OTRO') DEFAULT NULL,
  numero_documento VARCHAR(20) DEFAULT NULL,
  complemento VARCHAR(5) NOT NULL DEFAULT '',
  nombre VARCHAR(150) NOT NULL,
  razon_social VARCHAR(200) DEFAULT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  tipo ENUM('persona','empresa') NOT NULL DEFAULT 'persona',
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cliente),
  UNIQUE KEY uq_clientes_usuario (id_usuario),
  UNIQUE KEY uq_clientes_documento (tipo_documento, numero_documento, complemento),
  KEY idx_clientes_nombre (nombre),
  KEY idx_clientes_documento (numero_documento),
  KEY idx_clientes_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tokens_recuperacion (
  id_token INT NOT NULL AUTO_INCREMENT,
  id_usuario INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  usado_at DATETIME DEFAULT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_token),
  UNIQUE KEY uq_tokens_hash (token_hash),
  KEY idx_tokens_usuario (id_usuario),
  KEY idx_tokens_expiracion (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE direcciones (
  id_direccion INT NOT NULL AUTO_INCREMENT,
  id_cliente INT NOT NULL,
  alias VARCHAR(80) DEFAULT NULL,
  tipo ENUM('envio','facturacion','ambos') NOT NULL DEFAULT 'envio',
  direccion_linea VARCHAR(200) NOT NULL,
  zona VARCHAR(100) DEFAULT NULL,
  municipio VARCHAR(100) NOT NULL,
  departamento VARCHAR(100) NOT NULL,
  referencia VARCHAR(255) DEFAULT NULL,
  es_predeterminada_envio TINYINT(1) NOT NULL DEFAULT 0,
  es_predeterminada_facturacion TINYINT(1) NOT NULL DEFAULT 0,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_direccion),
  KEY idx_direcciones_cliente (id_cliente),
  KEY idx_direcciones_ubicacion (departamento, municipio),
  KEY idx_direcciones_estado (estado),
  CONSTRAINT chk_dir_pred_envio CHECK (es_predeterminada_envio IN (0,1)),
  CONSTRAINT chk_dir_pred_fact CHECK (es_predeterminada_facturacion IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PROVEEDORES Y CATALOGO DE PRODUCTOS
-- ============================================================================
-- MVP: un producto posee un unico proveedor principal y un unico precio de
-- venta vigente. No se crean producto_proveedor ni producto_precios.
-- ============================================================================

CREATE TABLE proveedores (
  id_proveedor INT NOT NULL AUTO_INCREMENT,
  nit VARCHAR(20) DEFAULT NULL,
  razon_social VARCHAR(150) NOT NULL,
  nombre_comercial VARCHAR(150) DEFAULT NULL,
  contacto VARCHAR(100) DEFAULT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  correo VARCHAR(150) DEFAULT NULL,
  direccion TEXT DEFAULT NULL,
  municipio VARCHAR(100) DEFAULT NULL,
  departamento VARCHAR(100) DEFAULT NULL,
  tiempo_entrega_dias INT DEFAULT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id_proveedor),
  UNIQUE KEY uq_proveedores_nit (nit),
  KEY idx_proveedores_razon_social (razon_social),
  KEY idx_proveedores_estado (estado),
  CONSTRAINT chk_proveedor_entrega CHECK (tiempo_entrega_dias IS NULL OR tiempo_entrega_dias >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE productos (
  id_producto INT NOT NULL AUTO_INCREMENT,
  id_categoria INT NOT NULL,
  id_marca INT DEFAULT NULL,
  id_proveedor INT DEFAULT NULL,
  codigo VARCHAR(50) NOT NULL,
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  modelo VARCHAR(100) DEFAULT NULL,
  unidad_medida VARCHAR(30) NOT NULL DEFAULT 'UNIDAD',
  especificaciones JSON DEFAULT NULL,
  imagenes JSON DEFAULT NULL,
  precio_compra DECIMAL(12,2) NOT NULL,
  precio_venta DECIMAL(12,2) NOT NULL,
  stock_actual INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 5,
  stock_maximo INT NOT NULL DEFAULT 100,
  ubicacion VARCHAR(100) DEFAULT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_producto),
  UNIQUE KEY uq_productos_codigo (codigo),
  KEY idx_productos_nombre (nombre),
  KEY idx_productos_categoria (id_categoria),
  KEY idx_productos_marca (id_marca),
  KEY idx_productos_proveedor (id_proveedor),
  KEY idx_productos_estado (estado),
  FULLTEXT KEY ft_productos (nombre, descripcion, codigo, modelo),
  CONSTRAINT chk_producto_precio_compra CHECK (precio_compra >= 0),
  CONSTRAINT chk_producto_precio_venta CHECK (precio_venta >= 0),
  CONSTRAINT chk_producto_stock_actual CHECK (stock_actual >= 0),
  CONSTRAINT chk_producto_stock_minimo CHECK (stock_minimo >= 0),
  CONSTRAINT chk_producto_stock_maximo CHECK (stock_maximo >= stock_minimo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE carrito_items (
  id_cliente INT NOT NULL,
  id_producto INT NOT NULL,
  cantidad INT NOT NULL,
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cliente, id_producto),
  CONSTRAINT chk_carrito_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VENTAS / PEDIDOS
-- ============================================================================
-- Regla MVP de stock:
--   Al confirmar un pedido, la aplicacion valida disponibilidad, actualiza
--   productos.stock_actual y registra movimientos_stock en una misma
--   transaccion. No existe una tabla de reservas temporales.
--   La venta conserva snapshots de direccion/datos de facturacion y el detalle
--   conserva codigo/nombre del producto para proteger el historial del pedido.
-- ============================================================================

CREATE TABLE ventas (
  id_venta INT NOT NULL AUTO_INCREMENT,
  codigo_pedido VARCHAR(30) NOT NULL,
  id_cliente INT NOT NULL,
  id_usuario INT DEFAULT NULL,
  id_metodo_entrega INT NOT NULL,
  origen ENUM('web','tienda','telefono','whatsapp') NOT NULL DEFAULT 'web',
  fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  costo_envio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  estado_pago ENUM('pendiente','pagada','fallida','reembolsada') NOT NULL DEFAULT 'pendiente',
  estado_logistico ENUM('pendiente','procesando','empacado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  id_direccion_envio INT DEFAULT NULL,
  id_direccion_facturacion INT DEFAULT NULL,
  direccion_envio_snapshot JSON DEFAULT NULL,
  datos_facturacion_snapshot JSON DEFAULT NULL,
  id_tarifa_envio INT DEFAULT NULL,
  observacion VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id_venta),
  UNIQUE KEY uq_ventas_codigo_pedido (codigo_pedido),
  KEY idx_ventas_cliente (id_cliente),
  KEY idx_ventas_usuario (id_usuario),
  KEY idx_ventas_metodo_entrega (id_metodo_entrega),
  KEY idx_ventas_fecha (fecha_venta),
  KEY idx_ventas_origen (origen),
  KEY idx_ventas_estado_pago (estado_pago),
  KEY idx_ventas_estado_logistico (estado_logistico),
  CONSTRAINT chk_venta_subtotal CHECK (subtotal >= 0),
  CONSTRAINT chk_venta_descuento CHECK (descuento >= 0),
  CONSTRAINT chk_venta_impuesto CHECK (impuesto >= 0),
  CONSTRAINT chk_venta_costo_envio CHECK (costo_envio >= 0),
  CONSTRAINT chk_venta_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE detalle_ventas (
  id_detalle_venta INT NOT NULL AUTO_INCREMENT,
  id_venta INT NOT NULL,
  id_producto INT NOT NULL,
  codigo_producto VARCHAR(50) NOT NULL,
  nombre_producto VARCHAR(200) NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  descuento_item DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(12,2)
    GENERATED ALWAYS AS ((cantidad * precio_unitario) - descuento_item) STORED,
  PRIMARY KEY (id_detalle_venta),
  UNIQUE KEY uq_detalle_venta_producto (id_venta, id_producto),
  KEY idx_detalle_ventas_venta (id_venta),
  KEY idx_detalle_ventas_producto (id_producto),
  CONSTRAINT chk_detalle_venta_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_detalle_venta_precio CHECK (precio_unitario >= 0),
  CONSTRAINT chk_detalle_venta_descuento CHECK (descuento_item >= 0),
  CONSTRAINT chk_detalle_venta_subtotal CHECK (subtotal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venta_pago (
  id_venta_pago INT NOT NULL AUTO_INCREMENT,
  id_venta INT NOT NULL,
  id_metodo_pago INT NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  referencia VARCHAR(100) DEFAULT NULL,
  fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado_conciliacion ENUM('pendiente','conciliado','rechazado') NOT NULL DEFAULT 'pendiente',
  fecha_conciliacion DATETIME DEFAULT NULL,
  observacion VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id_venta_pago),
  KEY idx_venta_pago_venta (id_venta),
  KEY idx_venta_pago_metodo (id_metodo_pago),
  KEY idx_venta_pago_estado (estado_conciliacion),
  CONSTRAINT chk_venta_pago_monto CHECK (monto > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- COMPRAS E INVENTARIO
-- ============================================================================

CREATE TABLE compras (
  id_compra INT NOT NULL AUTO_INCREMENT,
  id_proveedor INT NOT NULL,
  id_usuario INT NOT NULL,
  fecha_compra DATE NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  estado ENUM('pendiente','recibido','cancelado') NOT NULL DEFAULT 'pendiente',
  observacion VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id_compra),
  KEY idx_compras_proveedor (id_proveedor),
  KEY idx_compras_usuario (id_usuario),
  KEY idx_compras_estado (estado),
  CONSTRAINT chk_compra_subtotal CHECK (subtotal >= 0),
  CONSTRAINT chk_compra_impuesto CHECK (impuesto >= 0),
  CONSTRAINT chk_compra_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE detalle_compras (
  id_detalle_compra INT NOT NULL AUTO_INCREMENT,
  id_compra INT NOT NULL,
  id_producto INT NOT NULL,
  codigo_producto VARCHAR(50) NOT NULL,
  nombre_producto VARCHAR(200) NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2)
    GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
  PRIMARY KEY (id_detalle_compra),
  UNIQUE KEY uq_detalle_compra_producto (id_compra, id_producto),
  KEY idx_detalle_compras_compra (id_compra),
  KEY idx_detalle_compras_producto (id_producto),
  CONSTRAINT chk_detalle_compra_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_detalle_compra_precio CHECK (precio_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos_stock (
  id_movimiento INT NOT NULL AUTO_INCREMENT,
  id_producto INT NOT NULL,
  id_usuario INT NOT NULL,
  tipo_movimiento ENUM('ENTRADA','SALIDA','AJUSTE','DEVOLUCION') NOT NULL,
  cantidad INT NOT NULL,
  stock_anterior INT NOT NULL,
  stock_nuevo INT NOT NULL,
  referencia VARCHAR(100) DEFAULT NULL,
  observacion TEXT DEFAULT NULL,
  id_venta_referencia INT DEFAULT NULL,
  id_compra_referencia INT DEFAULT NULL,
  fecha_movimiento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_movimiento),
  KEY idx_movimientos_producto (id_producto),
  KEY idx_movimientos_usuario (id_usuario),
  KEY idx_movimientos_fecha (fecha_movimiento),
  KEY idx_movimientos_venta (id_venta_referencia),
  KEY idx_movimientos_compra (id_compra_referencia),
  CONSTRAINT chk_movimiento_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_movimiento_stock_anterior CHECK (stock_anterior >= 0),
  CONSTRAINT chk_movimiento_stock_nuevo CHECK (stock_nuevo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- AUDITORIA
-- ============================================================================

CREATE TABLE log_sistema (
  id_log INT NOT NULL AUTO_INCREMENT,
  id_usuario INT DEFAULT NULL,
  accion VARCHAR(100) NOT NULL,
  tabla_afectada VARCHAR(50) DEFAULT NULL,
  registro_id INT DEFAULT NULL,
  datos_anteriores JSON DEFAULT NULL,
  datos_nuevos JSON DEFAULT NULL,
  ip_usuario VARCHAR(45) DEFAULT NULL,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_log),
  KEY idx_log_usuario (id_usuario),
  KEY idx_log_fecha (fecha),
  KEY idx_log_tabla_registro (tabla_afectada, registro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- FOREIGN KEYS
-- Politica MVP: no eliminar fisicamente registros historicos. Por ello las
-- relaciones principales usan RESTRICT/NO ACTION; las bajas se manejan por
-- estado. Solo tokens y carrito se eliminan en cascada al eliminar la cuenta
-- en un entorno de desarrollo, aunque en produccion se recomienda inactivar.
-- ============================================================================

ALTER TABLE categorias
  ADD CONSTRAINT fk_categorias_padre
    FOREIGN KEY (id_padre) REFERENCES categorias (id_categoria)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE tarifas_envio
  ADD CONSTRAINT fk_tarifas_metodo
    FOREIGN KEY (id_metodo_entrega) REFERENCES metodos_entrega (id_metodo_entrega)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE usuarios
  ADD CONSTRAINT fk_usuarios_rol
    FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE clientes
  ADD CONSTRAINT fk_clientes_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE tokens_recuperacion
  ADD CONSTRAINT fk_tokens_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE direcciones
  ADD CONSTRAINT fk_direcciones_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE productos
  ADD CONSTRAINT fk_productos_categoria
    FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_productos_marca
    FOREIGN KEY (id_marca) REFERENCES marcas (id_marca)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_productos_proveedor
    FOREIGN KEY (id_proveedor) REFERENCES proveedores (id_proveedor)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE carrito_items
  ADD CONSTRAINT fk_carrito_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente)
    ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT fk_carrito_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ventas
  ADD CONSTRAINT fk_ventas_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ventas_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ventas_metodo_entrega
    FOREIGN KEY (id_metodo_entrega) REFERENCES metodos_entrega (id_metodo_entrega)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ventas_dir_envio
    FOREIGN KEY (id_direccion_envio) REFERENCES direcciones (id_direccion)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ventas_dir_facturacion
    FOREIGN KEY (id_direccion_facturacion) REFERENCES direcciones (id_direccion)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ventas_tarifa
    FOREIGN KEY (id_tarifa_envio) REFERENCES tarifas_envio (id_tarifa)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE detalle_ventas
  ADD CONSTRAINT fk_detalle_ventas_venta
    FOREIGN KEY (id_venta) REFERENCES ventas (id_venta)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_detalle_ventas_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE venta_pago
  ADD CONSTRAINT fk_venta_pago_venta
    FOREIGN KEY (id_venta) REFERENCES ventas (id_venta)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_venta_pago_metodo
    FOREIGN KEY (id_metodo_pago) REFERENCES metodos_pago (id_metodo_pago)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE compras
  ADD CONSTRAINT fk_compras_proveedor
    FOREIGN KEY (id_proveedor) REFERENCES proveedores (id_proveedor)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_compras_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE detalle_compras
  ADD CONSTRAINT fk_detalle_compras_compra
    FOREIGN KEY (id_compra) REFERENCES compras (id_compra)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_detalle_compras_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE movimientos_stock
  ADD CONSTRAINT fk_movimientos_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_movimientos_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_movimientos_venta
    FOREIGN KEY (id_venta_referencia) REFERENCES ventas (id_venta)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_movimientos_compra
    FOREIGN KEY (id_compra_referencia) REFERENCES compras (id_compra)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE log_sistema
  ADD CONSTRAINT fk_log_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- ============================================================================
-- DATOS SEMILLA DEL MVP
-- No se inserta ningun usuario administrador ni password conocido.
-- La cuenta inicial debe crearse mediante el seeder/procedimiento de instalacion
-- de la aplicacion usando una credencial temporal controlada.
-- ============================================================================

INSERT INTO roles (id_rol, codigo, nombre) VALUES
  (1, 'CLIENTE', 'Cliente'),
  (2, 'ADMIN', 'Administrador'),
  (3, 'VENDEDOR', 'Vendedor'),
  (4, 'ALMACENERO', 'Almacenero'),
  (5, 'CONTABLE', 'Contable');

INSERT INTO metodos_pago (id_metodo_pago, nombre, descripcion) VALUES
  (1, 'Efectivo', 'Pago en efectivo, utilizado principalmente en venta asistida'),
  (2, 'Transferencia bancaria', 'Transferencia bancaria sujeta a conciliacion manual'),
  (3, 'QR', 'Pago mediante QR sujeto a verificacion o conciliacion manual');

INSERT INTO metodos_entrega (id_metodo_entrega, nombre, tipo) VALUES
  (1, 'Envio a domicilio', 'despacho'),
  (2, 'Retiro en tienda', 'retiro_tienda');

INSERT INTO parametros_sistema (clave, valor, descripcion) VALUES
  ('pais', 'BO', 'Codigo de pais del sistema: Bolivia'),
  ('moneda', 'BOB', 'Moneda operativa del sistema: boliviano'),
  ('simbolo_moneda', 'Bs', 'Simbolo monetario mostrado por la aplicacion'),
  ('iva_porcentaje', '13', 'Tasa nominal de referencia del IVA; la regla fiscal efectiva debe validarse antes de habilitar facturacion SIN/SIAT'),
  ('zona_horaria', 'America/La_Paz', 'Zona horaria operativa del sistema');

INSERT INTO categorias (id_categoria, nombre, descripcion) VALUES
  (1, 'Cableado', 'Cables UTP, fibra optica y coaxial'),
  (2, 'Switches', 'Switches de red gestionados y no gestionados'),
  (3, 'Routers', 'Routers WiFi y VPN'),
  (4, 'Conectores', 'Conectores RJ45, LC, SC y BNC'),
  (5, 'Fibra Optica', 'Fibra monomodo, multimodo y pigtails'),
  (6, 'Accesorios', 'Faceplates, racks, canaletas y herramientas');

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================================
-- FUNCIONES Y PROCEDIMIENTOS ALMACENADOS DEL MVP
-- ============================================================================
-- Criterio:
--   Estas rutinas se limitan a operaciones que requieren consistencia entre
--   varias tablas. La validacion de permisos por rol, formularios y reglas de
--   presentacion permanecen en la aplicacion MVC (Controller/Service/Repository).
--   No se utilizan triggers para evitar ocultar logica y aumentar complejidad.
-- ============================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS fn_stock_disponible$$
CREATE FUNCTION fn_stock_disponible(
  p_id_producto INT,
  p_cantidad INT
)
RETURNS TINYINT
READS SQL DATA
BEGIN
  DECLARE v_disponible TINYINT DEFAULT 0;

  IF p_id_producto IS NULL OR p_cantidad IS NULL OR p_cantidad <= 0 THEN
    RETURN 0;
  END IF;

  SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END
    INTO v_disponible
  FROM productos
  WHERE id_producto = p_id_producto
    AND estado = 'activo'
    AND stock_actual >= p_cantidad;

  RETURN v_disponible;
END$$


DROP FUNCTION IF EXISTS fn_calcular_total_venta$$
CREATE FUNCTION fn_calcular_total_venta(
  p_subtotal DECIMAL(12,2),
  p_descuento DECIMAL(12,2),
  p_impuesto DECIMAL(12,2),
  p_costo_envio DECIMAL(12,2)
)
RETURNS DECIMAL(12,2)
DETERMINISTIC
NO SQL
BEGIN
  RETURN GREATEST(
    0.00,
    COALESCE(p_subtotal, 0.00)
    - COALESCE(p_descuento, 0.00)
    + COALESCE(p_impuesto, 0.00)
    + COALESCE(p_costo_envio, 0.00)
  );
END$$


DROP PROCEDURE IF EXISTS sp_confirmar_pedido$$
CREATE PROCEDURE sp_confirmar_pedido(
  IN p_id_cliente INT,
  IN p_id_usuario INT,
  IN p_id_metodo_entrega INT,
  IN p_origen VARCHAR(20),
  IN p_id_direccion_envio INT,
  IN p_id_direccion_facturacion INT,
  IN p_direccion_envio_snapshot JSON,
  IN p_datos_facturacion_snapshot JSON,
  IN p_id_tarifa_envio INT,
  IN p_costo_envio DECIMAL(12,2),
  IN p_descuento DECIMAL(12,2),
  IN p_impuesto DECIMAL(12,2),
  IN p_observacion VARCHAR(500)
)
BEGIN
  DECLARE v_done INT DEFAULT 0;
  DECLARE v_id_producto INT;
  DECLARE v_cantidad INT;
  DECLARE v_stock INT;
  DECLARE v_precio DECIMAL(12,2);
  DECLARE v_codigo_producto VARCHAR(50);
  DECLARE v_nombre_producto VARCHAR(200);
  DECLARE v_estado_producto VARCHAR(20);
  DECLARE v_id_usuario_stock INT;
  DECLARE v_cantidad_carrito INT DEFAULT 0;
  DECLARE v_metodo_valido INT DEFAULT 0;
  DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_total DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_id_venta INT;
  DECLARE v_codigo_pedido VARCHAR(30);

  DECLARE cur_carrito CURSOR FOR
    SELECT id_producto, cantidad
    FROM carrito_items
    WHERE id_cliente = p_id_cliente
    ORDER BY id_producto;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  IF p_id_cliente IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El cliente es obligatorio para confirmar el pedido.';
  END IF;

  IF p_origen NOT IN ('web','tienda','telefono','whatsapp') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Origen de venta no valido.';
  END IF;

  SELECT COUNT(*)
    INTO v_metodo_valido
  FROM metodos_entrega
  WHERE id_metodo_entrega = p_id_metodo_entrega
    AND estado = 'activo';

  IF v_metodo_valido = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El metodo de entrega no existe o esta inactivo.';
  END IF;

  SELECT COUNT(*)
    INTO v_cantidad_carrito
  FROM carrito_items
  WHERE id_cliente = p_id_cliente;

  IF v_cantidad_carrito = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El carrito del cliente esta vacio.';
  END IF;

  SET v_id_usuario_stock = p_id_usuario;

  IF v_id_usuario_stock IS NULL THEN
    SELECT MAX(id_usuario)
      INTO v_id_usuario_stock
    FROM clientes
    WHERE id_cliente = p_id_cliente;
  END IF;

  IF v_id_usuario_stock IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Se requiere un usuario responsable para registrar el movimiento de stock.';
  END IF;

  SELECT COALESCE(SUM(c.cantidad * p.precio_venta), 0.00)
    INTO v_subtotal
  FROM carrito_items c
  INNER JOIN productos p ON p.id_producto = c.id_producto
  WHERE c.id_cliente = p_id_cliente;

  SET v_total = fn_calcular_total_venta(
    v_subtotal,
    COALESCE(p_descuento, 0.00),
    COALESCE(p_impuesto, 0.00),
    COALESCE(p_costo_envio, 0.00)
  );

  SET v_codigo_pedido = CONCAT(
    'PED-',
    DATE_FORMAT(NOW(), '%Y%m%d'),
    '-',
    UPPER(SUBSTRING(REPLACE(UUID(), '-', ''), 1, 8))
  );

  INSERT INTO ventas (
    codigo_pedido,
    id_cliente,
    id_usuario,
    id_metodo_entrega,
    origen,
    subtotal,
    descuento,
    impuesto,
    costo_envio,
    total,
    estado_pago,
    estado_logistico,
    id_direccion_envio,
    id_direccion_facturacion,
    direccion_envio_snapshot,
    datos_facturacion_snapshot,
    id_tarifa_envio,
    observacion
  ) VALUES (
    v_codigo_pedido,
    p_id_cliente,
    v_id_usuario_stock,
    p_id_metodo_entrega,
    p_origen,
    v_subtotal,
    COALESCE(p_descuento, 0.00),
    COALESCE(p_impuesto, 0.00),
    COALESCE(p_costo_envio, 0.00),
    v_total,
    'pendiente',
    'pendiente',
    p_id_direccion_envio,
    p_id_direccion_facturacion,
    p_direccion_envio_snapshot,
    p_datos_facturacion_snapshot,
    p_id_tarifa_envio,
    p_observacion
  );

  SET v_id_venta = LAST_INSERT_ID();

  OPEN cur_carrito;

  confirmar_loop: LOOP
    FETCH cur_carrito INTO v_id_producto, v_cantidad;

    IF v_done = 1 THEN
      LEAVE confirmar_loop;
    END IF;

    SELECT stock_actual, precio_venta, codigo, nombre, estado
      INTO v_stock, v_precio, v_codigo_producto, v_nombre_producto, v_estado_producto
    FROM productos
    WHERE id_producto = v_id_producto
    FOR UPDATE;

    IF v_estado_producto <> 'activo' THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Uno de los productos del carrito esta inactivo.';
    END IF;

    IF v_stock < v_cantidad THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente para uno de los productos del carrito.';
    END IF;

    INSERT INTO detalle_ventas (
      id_venta,
      id_producto,
      codigo_producto,
      nombre_producto,
      cantidad,
      precio_unitario,
      descuento_item
    ) VALUES (
      v_id_venta,
      v_id_producto,
      v_codigo_producto,
      v_nombre_producto,
      v_cantidad,
      v_precio,
      0.00
    );

    INSERT INTO movimientos_stock (
      id_producto,
      id_usuario,
      tipo_movimiento,
      cantidad,
      stock_anterior,
      stock_nuevo,
      referencia,
      observacion,
      id_venta_referencia
    ) VALUES (
      v_id_producto,
      v_id_usuario_stock,
      'SALIDA',
      v_cantidad,
      v_stock,
      v_stock - v_cantidad,
      v_codigo_pedido,
      'Salida por confirmacion de pedido',
      v_id_venta
    );

    UPDATE productos
    SET stock_actual = v_stock - v_cantidad
    WHERE id_producto = v_id_producto;
  END LOOP;

  CLOSE cur_carrito;

  DELETE FROM carrito_items
  WHERE id_cliente = p_id_cliente;

  COMMIT;

  SELECT
    v_id_venta AS id_venta,
    v_codigo_pedido AS codigo_pedido,
    v_total AS total;
END$$


DROP PROCEDURE IF EXISTS sp_registrar_ingreso_stock$$
CREATE PROCEDURE sp_registrar_ingreso_stock(
  IN p_id_compra INT,
  IN p_id_usuario INT,
  IN p_observacion VARCHAR(500)
)
BEGIN
  DECLARE v_done INT DEFAULT 0;
  DECLARE v_existe INT DEFAULT 0;
  DECLARE v_detalles INT DEFAULT 0;
  DECLARE v_estado_compra VARCHAR(20);
  DECLARE v_id_producto INT;
  DECLARE v_cantidad INT;
  DECLARE v_stock INT;

  DECLARE cur_detalle CURSOR FOR
    SELECT id_producto, cantidad
    FROM detalle_compras
    WHERE id_compra = p_id_compra
    ORDER BY id_producto;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  SELECT COUNT(*), MAX(estado)
    INTO v_existe, v_estado_compra
  FROM compras
  WHERE id_compra = p_id_compra;

  IF v_existe = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La compra indicada no existe.';
  END IF;

  IF v_estado_compra <> 'pendiente' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Solo se puede recibir una compra en estado pendiente.';
  END IF;

  SELECT COUNT(*)
    INTO v_detalles
  FROM detalle_compras
  WHERE id_compra = p_id_compra;

  IF v_detalles = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La compra no contiene productos.';
  END IF;

  OPEN cur_detalle;

  ingreso_loop: LOOP
    FETCH cur_detalle INTO v_id_producto, v_cantidad;

    IF v_done = 1 THEN
      LEAVE ingreso_loop;
    END IF;

    SELECT stock_actual
      INTO v_stock
    FROM productos
    WHERE id_producto = v_id_producto
    FOR UPDATE;

    INSERT INTO movimientos_stock (
      id_producto,
      id_usuario,
      tipo_movimiento,
      cantidad,
      stock_anterior,
      stock_nuevo,
      referencia,
      observacion,
      id_compra_referencia
    ) VALUES (
      v_id_producto,
      p_id_usuario,
      'ENTRADA',
      v_cantidad,
      v_stock,
      v_stock + v_cantidad,
      CONCAT('COMPRA-', p_id_compra),
      COALESCE(p_observacion, 'Ingreso de mercaderia por compra'),
      p_id_compra
    );

    UPDATE productos
    SET stock_actual = v_stock + v_cantidad
    WHERE id_producto = v_id_producto;
  END LOOP;

  CLOSE cur_detalle;

  UPDATE compras
  SET estado = 'recibido'
  WHERE id_compra = p_id_compra;

  COMMIT;
END$$


DROP PROCEDURE IF EXISTS sp_ajustar_inventario$$
CREATE PROCEDURE sp_ajustar_inventario(
  IN p_id_producto INT,
  IN p_nuevo_stock INT,
  IN p_id_usuario INT,
  IN p_observacion VARCHAR(500)
)
BEGIN
  DECLARE v_existe INT DEFAULT 0;
  DECLARE v_stock_actual INT DEFAULT 0;
  DECLARE v_diferencia INT DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  IF p_nuevo_stock IS NULL OR p_nuevo_stock < 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El nuevo stock debe ser mayor o igual a cero.';
  END IF;

  SELECT COUNT(*)
    INTO v_existe
  FROM productos
  WHERE id_producto = p_id_producto;

  IF v_existe = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El producto indicado no existe.';
  END IF;

  SELECT stock_actual
    INTO v_stock_actual
  FROM productos
  WHERE id_producto = p_id_producto
  FOR UPDATE;

  SET v_diferencia = ABS(p_nuevo_stock - v_stock_actual);

  IF v_diferencia = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El nuevo stock es igual al stock actual; no existe ajuste que registrar.';
  END IF;

  INSERT INTO movimientos_stock (
    id_producto,
    id_usuario,
    tipo_movimiento,
    cantidad,
    stock_anterior,
    stock_nuevo,
    referencia,
    observacion
  ) VALUES (
    p_id_producto,
    p_id_usuario,
    'AJUSTE',
    v_diferencia,
    v_stock_actual,
    p_nuevo_stock,
    'AJUSTE-MANUAL',
    COALESCE(p_observacion, 'Ajuste manual de inventario')
  );

  UPDATE productos
  SET stock_actual = p_nuevo_stock
  WHERE id_producto = p_id_producto;

  COMMIT;
END$$


DROP PROCEDURE IF EXISTS sp_conciliar_pago$$
CREATE PROCEDURE sp_conciliar_pago(
  IN p_id_venta_pago INT,
  IN p_estado VARCHAR(20),
  IN p_observacion VARCHAR(500)
)
BEGIN
  DECLARE v_existe INT DEFAULT 0;
  DECLARE v_id_venta INT;
  DECLARE v_estado_actual VARCHAR(20);
  DECLARE v_total_venta DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_total_conciliado DECIMAL(12,2) DEFAULT 0.00;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  IF p_estado NOT IN ('conciliado','rechazado') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El estado de conciliacion debe ser conciliado o rechazado.';
  END IF;

  SELECT COUNT(*)
    INTO v_existe
  FROM venta_pago
  WHERE id_venta_pago = p_id_venta_pago;

  IF v_existe = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El pago indicado no existe.';
  END IF;

  SELECT id_venta, estado_conciliacion
    INTO v_id_venta, v_estado_actual
  FROM venta_pago
  WHERE id_venta_pago = p_id_venta_pago
  FOR UPDATE;

  IF v_estado_actual <> 'pendiente' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El pago ya fue conciliado o rechazado anteriormente.';
  END IF;

  UPDATE venta_pago
  SET estado_conciliacion = p_estado,
      fecha_conciliacion = NOW(),
      observacion = COALESCE(p_observacion, observacion)
  WHERE id_venta_pago = p_id_venta_pago;

  SELECT total
    INTO v_total_venta
  FROM ventas
  WHERE id_venta = v_id_venta
  FOR UPDATE;

  SELECT COALESCE(SUM(monto), 0.00)
    INTO v_total_conciliado
  FROM venta_pago
  WHERE id_venta = v_id_venta
    AND estado_conciliacion = 'conciliado';

  UPDATE ventas
  SET estado_pago =
    CASE
      WHEN v_total_venta > 0 AND v_total_conciliado >= v_total_venta THEN 'pagada'
      ELSE 'pendiente'
    END
  WHERE id_venta = v_id_venta;

  COMMIT;
END$$


DROP PROCEDURE IF EXISTS sp_cancelar_pedido$$
CREATE PROCEDURE sp_cancelar_pedido(
  IN p_id_venta INT,
  IN p_id_usuario INT,
  IN p_observacion VARCHAR(500)
)
BEGIN
  DECLARE v_done INT DEFAULT 0;
  DECLARE v_existe INT DEFAULT 0;
  DECLARE v_estado VARCHAR(20);
  DECLARE v_codigo_pedido VARCHAR(30);
  DECLARE v_id_producto INT;
  DECLARE v_cantidad INT;
  DECLARE v_stock INT;

  DECLARE cur_detalle CURSOR FOR
    SELECT id_producto, cantidad
    FROM detalle_ventas
    WHERE id_venta = p_id_venta
    ORDER BY id_producto;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  SELECT COUNT(*)
    INTO v_existe
  FROM ventas
  WHERE id_venta = p_id_venta;

  IF v_existe = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El pedido indicado no existe.';
  END IF;

  SELECT estado_logistico, codigo_pedido
    INTO v_estado, v_codigo_pedido
  FROM ventas
  WHERE id_venta = p_id_venta
  FOR UPDATE;

  IF v_estado = 'cancelado' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El pedido ya se encuentra cancelado.';
  END IF;

  IF v_estado IN ('enviado','entregado') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Un pedido enviado o entregado no puede cancelarse mediante este procedimiento.';
  END IF;

  OPEN cur_detalle;

  cancelar_loop: LOOP
    FETCH cur_detalle INTO v_id_producto, v_cantidad;

    IF v_done = 1 THEN
      LEAVE cancelar_loop;
    END IF;

    SELECT stock_actual
      INTO v_stock
    FROM productos
    WHERE id_producto = v_id_producto
    FOR UPDATE;

    INSERT INTO movimientos_stock (
      id_producto,
      id_usuario,
      tipo_movimiento,
      cantidad,
      stock_anterior,
      stock_nuevo,
      referencia,
      observacion,
      id_venta_referencia
    ) VALUES (
      v_id_producto,
      p_id_usuario,
      'DEVOLUCION',
      v_cantidad,
      v_stock,
      v_stock + v_cantidad,
      v_codigo_pedido,
      COALESCE(p_observacion, 'Reposicion de stock por cancelacion de pedido'),
      p_id_venta
    );

    UPDATE productos
    SET stock_actual = v_stock + v_cantidad
    WHERE id_producto = v_id_producto;
  END LOOP;

  CLOSE cur_detalle;

  UPDATE ventas
  SET estado_logistico = 'cancelado',
      observacion =
        CASE
          WHEN p_observacion IS NULL OR p_observacion = '' THEN observacion
          WHEN observacion IS NULL OR observacion = '' THEN p_observacion
          ELSE CONCAT(observacion, ' | ', p_observacion)
        END
  WHERE id_venta = p_id_venta;

  COMMIT;
END$$

DELIMITER ;


-- ============================================================================
-- FIN DEL ESQUEMA BOLIVIA MVP v1.1 - BASE OFICIAL
-- ============================================================================
