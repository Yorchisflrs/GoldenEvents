CREATE DATABASE IF NOT EXISTS golden_hour_events
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE golden_hour_events;

SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS vista_eventos_disponibles;

DROP TABLE IF EXISTS cotizacion_detalles;
DROP TABLE IF EXISTS cotizaciones;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS categorias_servicio;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS traducciones;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NULL,
    idioma ENUM('es','en','qu','ay') NOT NULL DEFAULT 'es',
    estado ENUM('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (rol_id)
        REFERENCES roles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizador_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria VARCHAR(80) NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    lugar VARCHAR(180) NOT NULL,
    direccion VARCHAR(220) NULL,
    cupo_total INT UNSIGNED NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    imagen VARCHAR(255) NULL,
    estado ENUM('borrador','activo','cancelado','finalizado') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_eventos_organizador
        FOREIGN KEY (organizador_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_eventos_fecha (fecha_inicio),
    INDEX idx_eventos_estado (estado),
    INDEX idx_eventos_organizador (organizador_id)
) ENGINE=InnoDB;

CREATE TABLE reservas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    evento_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    monto_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('pendiente','pagado','cancelado','rechazado') NOT NULL DEFAULT 'pendiente',
    metodo_pago VARCHAR(40) NULL,
    codigo_transaccion VARCHAR(120) NULL UNIQUE,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_reservas_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_reservas_evento
        FOREIGN KEY (evento_id)
        REFERENCES eventos(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_reservas_usuario (usuario_id),
    INDEX idx_reservas_evento (evento_id),
    INDEX idx_reservas_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE pagos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT UNSIGNED NOT NULL UNIQUE,
    monto DECIMAL(10,2) NOT NULL,
    moneda CHAR(3) NOT NULL DEFAULT 'PEN',
    metodo ENUM('yape','tarjeta','efectivo','simulado') NOT NULL DEFAULT 'simulado',
    estado ENUM('pendiente','exitoso','fallido','reembolsado') NOT NULL DEFAULT 'pendiente',
    referencia VARCHAR(120) NULL UNIQUE,
    fecha_pago DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pagos_reserva
        FOREIGN KEY (reserva_id)
        REFERENCES reservas(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    INDEX idx_pagos_estado (estado),
    INDEX idx_pagos_metodo (metodo)
) ENGINE=InnoDB;

CREATE TABLE proveedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL UNIQUE,
    tipo_servicio VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_proveedores_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categorias_servicio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(180) NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE servicios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    capacidad INT UNSIGNED NULL,
    ubicacion VARCHAR(180) NULL,
    imagen VARCHAR(255) NULL,
    disponibilidad TINYINT(1) NOT NULL DEFAULT 1,
    estado ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_servicios_proveedor
        FOREIGN KEY (proveedor_id)
        REFERENCES proveedores(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_servicios_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias_servicio(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_servicios_proveedor (proveedor_id),
    INDEX idx_servicios_categoria (categoria_id),
    INDEX idx_servicios_disponibilidad (disponibilidad),
    INDEX idx_servicios_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE cotizaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    nombre_cliente VARCHAR(120) NOT NULL,
    telefono_cliente VARCHAR(25) NOT NULL,
    email_cliente VARCHAR(150) NULL,
    tipo_evento ENUM('15_anios','promocion','matrimonio','cumpleanos','grado','fiesta_escolar','otro') NOT NULL,
    fecha_evento DATE NULL,
    cantidad_invitados INT UNSIGNED NOT NULL DEFAULT 1,
    total_estimado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    mensaje TEXT NULL,
    estado ENUM('pendiente','contactado','aprobado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_cotizaciones_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_cotizaciones_usuario (usuario_id),
    INDEX idx_cotizaciones_estado (estado),
    INDEX idx_cotizaciones_fecha (fecha_evento)
) ENGINE=InnoDB;

CREATE TABLE cotizacion_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT UNSIGNED NOT NULL,
    servicio_id INT UNSIGNED NOT NULL,
    categoria_nombre VARCHAR(80) NOT NULL,
    servicio_nombre VARCHAR(120) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cotizacion_detalles_cotizacion
        FOREIGN KEY (cotizacion_id)
        REFERENCES cotizaciones(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_cotizacion_detalles_servicio
        FOREIGN KEY (servicio_id)
        REFERENCES servicios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_cotizacion_detalles_cotizacion (cotizacion_id),
    INDEX idx_cotizacion_detalles_servicio (servicio_id)
) ENGINE=InnoDB;

CREATE TABLE traducciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idioma ENUM('es','en','qu','ay') NOT NULL,
    clave VARCHAR(120) NOT NULL,
    valor TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_traduccion (idioma, clave)
) ENGINE=InnoDB;

INSERT INTO roles (nombre, descripcion) VALUES
('cliente', 'Usuario que explora servicios y solicita cotizaciones'),
('organizador', 'Usuario que puede crear y administrar eventos internos'),
('proveedor', 'Usuario que ofrece servicios complementarios para eventos'),
('admin', 'Usuario administrador con control general del sistema');

INSERT INTO usuarios (rol_id, nombre, email, password, telefono, idioma, estado) VALUES
((SELECT id FROM roles WHERE nombre = 'admin'), 'Administrador General', 'admin@golden.com', '$2y$10$vnpVUQSkI5sm71v/ZVwsAupMUinJiJgtxweKmilnuwo1LA8HJWKey', '999111222', 'es', 'activo'),
((SELECT id FROM roles WHERE nombre = 'organizador'), 'Organizador Demo', 'organizador@golden.com', '$2y$10$vnpVUQSkI5sm71v/ZVwsAupMUinJiJgtxweKmilnuwo1LA8HJWKey', '999333444', 'es', 'activo'),
((SELECT id FROM roles WHERE nombre = 'cliente'), 'Cliente Demo', 'cliente@golden.com', '$2y$10$vnpVUQSkI5sm71v/ZVwsAupMUinJiJgtxweKmilnuwo1LA8HJWKey', '999555666', 'es', 'activo'),
((SELECT id FROM roles WHERE nombre = 'proveedor'), 'Proveedor Demo', 'proveedor@golden.com', '$2y$10$vnpVUQSkI5sm71v/ZVwsAupMUinJiJgtxweKmilnuwo1LA8HJWKey', '999777888', 'es', 'activo');

INSERT INTO categorias_servicio (nombre, slug, descripcion) VALUES
('Local', 'local', 'Locales y salones para realizar eventos'),
('Decoracion', 'decoracion', 'Decoracion tematica y ambientacion del evento'),
('DJ y Musica', 'dj_musica', 'DJ, sonido, luces y musica para eventos'),
('Animador', 'animador', 'Animadores, maestros de ceremonia y conduccion'),
('Torta', 'torta', 'Tortas personalizadas para eventos'),
('Catering', 'catering', 'Comida, bocaditos, bebidas y atencion'),
('Fotografia y Video', 'fotografia_video', 'Cobertura audiovisual del evento'),
('Mesas y Sillas', 'mesas_sillas', 'Alquiler de mesas, sillas y mobiliario'),
('Seguridad', 'seguridad', 'Personal de seguridad para eventos'),
('Otros', 'otro', 'Otros servicios complementarios');

INSERT INTO eventos (
    organizador_id, titulo, descripcion, categoria, fecha_inicio, fecha_fin,
    lugar, direccion, cupo_total, precio, estado
) VALUES
(
    (SELECT id FROM usuarios WHERE email = 'organizador@golden.com'),
    'Planificacion de matrimonio en Puno',
    'Evento interno de referencia para organizacion matrimonial.',
    'Matrimonio',
    DATE_ADD(NOW(), INTERVAL 15 DAY),
    DATE_ADD(NOW(), INTERVAL 15 DAY) + INTERVAL 5 HOUR,
    'Salon de Eventos Golden Puno',
    'Av. El Sol 123 - Puno',
    120,
    80.00,
    'activo'
);

INSERT INTO proveedores (usuario_id, tipo_servicio, descripcion, estado)
VALUES (
    (SELECT id FROM usuarios WHERE email = 'proveedor@golden.com'),
    'Servicios para eventos',
    'Proveedor especializado en locales, decoracion, sonido, catering y servicios complementarios.',
    'activo'
);

INSERT INTO servicios (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado) VALUES
((SELECT id FROM proveedores WHERE usuario_id = (SELECT id FROM usuarios WHERE email = 'proveedor@golden.com')), (SELECT id FROM categorias_servicio WHERE slug = 'local'), 'Salon Titicaca Premium', 'Local amplio para matrimonios, promociones y fiestas de 15 anios. Incluye iluminacion basica y espacio para pista de baile.', 1500.00, 200, 'Puno', NULL, 1, 'activo'),
((SELECT id FROM proveedores WHERE usuario_id = (SELECT id FROM usuarios WHERE email = 'proveedor@golden.com')), (SELECT id FROM categorias_servicio WHERE slug = 'decoracion'), 'Decoracion Golden Premium', 'Decoracion tematica con arco principal, centros de mesa, luces calidas y fondo fotografico.', 850.00, NULL, 'Puno', NULL, 1, 'activo'),
((SELECT id FROM proveedores WHERE usuario_id = (SELECT id FROM usuarios WHERE email = 'proveedor@golden.com')), (SELECT id FROM categorias_servicio WHERE slug = 'dj_musica'), 'DJ Andino Mix', 'DJ profesional con consola, parlantes, luces basicas y animacion musical.', 650.00, NULL, 'Puno', NULL, 1, 'activo'),
((SELECT id FROM proveedores WHERE usuario_id = (SELECT id FROM usuarios WHERE email = 'proveedor@golden.com')), (SELECT id FROM categorias_servicio WHERE slug = 'torta'), 'Torta Personalizada 15 anios', 'Torta tematica de tres niveles para eventos de 15 anios o cumpleanos especiales.', 380.00, NULL, 'Puno', NULL, 1, 'activo'),
((SELECT id FROM proveedores WHERE usuario_id = (SELECT id FROM usuarios WHERE email = 'proveedor@golden.com')), (SELECT id FROM categorias_servicio WHERE slug = 'catering'), 'Catering Basico', 'Servicio de bocaditos, bebidas y atencion para invitados.', 700.00, NULL, 'Puno', NULL, 1, 'activo');

INSERT INTO traducciones (idioma, clave, valor) VALUES
('es', 'welcome', 'Bienvenido a Golden Hour Events'),
('en', 'welcome', 'Welcome to Golden Hour Events'),
('qu', 'welcome', 'Golden Hour Events nisqaman allin hamusqayki'),
('ay', 'welcome', 'Golden Hour Events ukarux wali jutapxta');

CREATE VIEW vista_eventos_disponibles AS
SELECT
    e.id,
    e.titulo,
    e.descripcion,
    e.categoria,
    e.fecha_inicio,
    e.fecha_fin,
    e.lugar,
    e.direccion,
    e.cupo_total,
    e.precio,
    e.estado,
    u.nombre AS organizador,
    COALESCE(SUM(CASE WHEN r.estado = 'pagado' THEN r.cantidad ELSE 0 END), 0) AS cupos_reservados,
    (e.cupo_total - COALESCE(SUM(CASE WHEN r.estado = 'pagado' THEN r.cantidad ELSE 0 END), 0)) AS cupos_disponibles
FROM eventos e
INNER JOIN usuarios u ON e.organizador_id = u.id
LEFT JOIN reservas r ON e.id = r.evento_id
GROUP BY
    e.id, e.titulo, e.descripcion, e.categoria, e.fecha_inicio, e.fecha_fin,
    e.lugar, e.direccion, e.cupo_total, e.precio, e.estado, u.nombre;
