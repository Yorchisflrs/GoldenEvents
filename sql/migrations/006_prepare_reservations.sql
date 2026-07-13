-- Campos y estados para retencion temporal de aforo.
ALTER TABLE reservas
    MODIFY estado ENUM('pendiente','pagado','cancelado','rechazado','pendiente_pago','pago_en_revision','confirmada','cancelada','vencida','rechazada') NOT NULL DEFAULT 'pendiente_pago',
    ADD COLUMN codigo_reserva VARCHAR(64) NULL AFTER id,
    ADD COLUMN precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cantidad,
    ADD COLUMN fecha_expiracion DATETIME NULL AFTER fecha_reserva;

-- statement-break
UPDATE reservas
SET estado = CASE estado
        WHEN 'pendiente' THEN 'pendiente_pago'
        WHEN 'pagado' THEN 'confirmada'
        WHEN 'cancelado' THEN 'cancelada'
        WHEN 'rechazado' THEN 'rechazada'
        ELSE estado
    END,
    codigo_reserva = COALESCE(codigo_reserva, CONCAT('LEGACY-RES-', LPAD(id, 10, '0'))),
    precio_unitario = CASE WHEN cantidad > 0 THEN ROUND(monto_total / cantidad, 2) ELSE 0 END;

-- statement-break
ALTER TABLE reservas
    MODIFY estado ENUM('pendiente_pago','pago_en_revision','confirmada','cancelada','vencida','rechazada') NOT NULL DEFAULT 'pendiente_pago',
    MODIFY codigo_reserva VARCHAR(64) NOT NULL,
    ADD UNIQUE INDEX uq_reservas_codigo_reserva (codigo_reserva),
    ADD INDEX idx_reservas_expiracion (fecha_expiracion);
