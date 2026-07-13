-- Estados definitivos y metadatos de moderacion de servicios.
ALTER TABLE servicios
    MODIFY estado ENUM('pendiente','activo','rechazado','inactivo') NOT NULL DEFAULT 'pendiente',
    ADD COLUMN aprobado_por INT UNSIGNED NULL AFTER estado,
    ADD COLUMN aprobado_en DATETIME NULL AFTER aprobado_por,
    ADD COLUMN motivo_rechazo VARCHAR(500) NULL AFTER aprobado_en,
    ADD INDEX idx_servicios_aprobado_por (aprobado_por),
    ADD CONSTRAINT fk_servicios_aprobado_por
        FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL;
