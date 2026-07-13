-- Estados definitivos y metadatos de moderacion de usuarios.
ALTER TABLE usuarios
    MODIFY estado ENUM('pendiente','activo','inactivo','bloqueado','rechazado') NOT NULL DEFAULT 'activo',
    ADD COLUMN aprobado_por INT UNSIGNED NULL AFTER estado,
    ADD COLUMN aprobado_en DATETIME NULL AFTER aprobado_por,
    ADD COLUMN motivo_rechazo VARCHAR(500) NULL AFTER aprobado_en,
    ADD INDEX idx_usuarios_estado (estado),
    ADD INDEX idx_usuarios_aprobado_por (aprobado_por),
    ADD CONSTRAINT fk_usuarios_aprobado_por
        FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL;
