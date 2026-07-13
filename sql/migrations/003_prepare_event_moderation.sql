-- Permite convertir el estado heredado sin perder eventos.
ALTER TABLE eventos
    MODIFY estado ENUM('borrador','activo','pendiente_aprobacion','publicado','rechazado','cancelado','finalizado','inactivo') NOT NULL DEFAULT 'borrador';

-- statement-break
UPDATE eventos SET estado = 'publicado' WHERE estado = 'activo';

-- statement-break
ALTER TABLE eventos
    MODIFY estado ENUM('borrador','pendiente_aprobacion','publicado','rechazado','cancelado','finalizado','inactivo') NOT NULL DEFAULT 'borrador',
    ADD COLUMN aprobado_por INT UNSIGNED NULL AFTER estado,
    ADD COLUMN aprobado_en DATETIME NULL AFTER aprobado_por,
    ADD COLUMN motivo_rechazo VARCHAR(500) NULL AFTER aprobado_en,
    ADD INDEX idx_eventos_aprobado_por (aprobado_por),
    ADD CONSTRAINT fk_eventos_aprobado_por
        FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL;
