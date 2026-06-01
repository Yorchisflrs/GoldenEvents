CREATE DATABASE IF NOT EXISTS golden_hour_events
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE golden_hour_events;

CREATE TABLE IF NOT EXISTS categorias_servicio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(180) NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO categorias_servicio (nombre, slug, descripcion) VALUES
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

ALTER TABLE servicios
ADD COLUMN IF NOT EXISTS categoria_id INT UNSIGNED NULL AFTER proveedor_id,
ADD COLUMN IF NOT EXISTS capacidad INT UNSIGNED NULL AFTER precio,
ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(180) NULL AFTER capacidad,
ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL AFTER ubicacion,
ADD COLUMN IF NOT EXISTS estado ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo' AFTER disponibilidad;

SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'servicios'
      AND CONSTRAINT_NAME = 'fk_servicios_categoria'
);

SET @sql = IF(
    @fk_exists = 0,
    'ALTER TABLE servicios ADD CONSTRAINT fk_servicios_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_servicio(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS cotizaciones (
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

CREATE TABLE IF NOT EXISTS cotizacion_detalles (
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

INSERT INTO servicios (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado)
SELECT
    p.id,
    c.id,
    'Salon Titicaca Premium',
    'Local amplio para matrimonios, promociones y fiestas de 15 anios. Incluye iluminacion basica y espacio para pista de baile.',
    1500.00,
    200,
    'Puno',
    NULL,
    1,
    'activo'
FROM proveedores p
JOIN usuarios u ON p.usuario_id = u.id
JOIN categorias_servicio c ON c.slug = 'local'
WHERE u.email = 'proveedor@golden.com'
  AND NOT EXISTS (SELECT 1 FROM servicios s WHERE s.nombre = 'Salon Titicaca Premium')
LIMIT 1;

INSERT INTO servicios (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado)
SELECT
    p.id,
    c.id,
    'Decoracion Golden Premium',
    'Decoracion tematica con arco principal, centros de mesa, luces calidas y fondo fotografico.',
    850.00,
    NULL,
    'Puno',
    NULL,
    1,
    'activo'
FROM proveedores p
JOIN usuarios u ON p.usuario_id = u.id
JOIN categorias_servicio c ON c.slug = 'decoracion'
WHERE u.email = 'proveedor@golden.com'
  AND NOT EXISTS (SELECT 1 FROM servicios s WHERE s.nombre = 'Decoracion Golden Premium')
LIMIT 1;

INSERT INTO servicios (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado)
SELECT
    p.id,
    c.id,
    'DJ Andino Mix',
    'DJ profesional con consola, parlantes, luces basicas y animacion musical.',
    650.00,
    NULL,
    'Puno',
    NULL,
    1,
    'activo'
FROM proveedores p
JOIN usuarios u ON p.usuario_id = u.id
JOIN categorias_servicio c ON c.slug = 'dj_musica'
WHERE u.email = 'proveedor@golden.com'
  AND NOT EXISTS (SELECT 1 FROM servicios s WHERE s.nombre = 'DJ Andino Mix')
LIMIT 1;

INSERT INTO servicios (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado)
SELECT
    p.id,
    c.id,
    'Torta Personalizada 15 anios',
    'Torta tematica de tres niveles para eventos de 15 anios o cumpleanos especiales.',
    380.00,
    NULL,
    'Puno',
    NULL,
    1,
    'activo'
FROM proveedores p
JOIN usuarios u ON p.usuario_id = u.id
JOIN categorias_servicio c ON c.slug = 'torta'
WHERE u.email = 'proveedor@golden.com'
  AND NOT EXISTS (SELECT 1 FROM servicios s WHERE s.nombre = 'Torta Personalizada 15 anios')
LIMIT 1;
