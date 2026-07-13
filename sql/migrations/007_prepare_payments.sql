-- Campos y estados para validacion manual de pagos.
ALTER TABLE pagos
    MODIFY estado ENUM('pendiente','exitoso','fallido','reembolsado','en_revision','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    ADD COLUMN codigo_operacion VARCHAR(120) NULL AFTER metodo,
    ADD COLUMN comprobante VARCHAR(255) NULL AFTER codigo_operacion,
    ADD COLUMN fecha_validacion DATETIME NULL AFTER fecha_pago,
    ADD COLUMN validado_por INT UNSIGNED NULL AFTER fecha_validacion,
    ADD COLUMN motivo_rechazo VARCHAR(500) NULL AFTER validado_por;

-- statement-break
UPDATE pagos
SET estado = CASE estado
        WHEN 'exitoso' THEN 'aprobado'
        WHEN 'fallido' THEN 'rechazado'
        ELSE estado
    END,
    codigo_operacion = COALESCE(NULLIF(codigo_operacion, ''), NULLIF(referencia, ''), CONCAT('LEGACY-PAY-', LPAD(id, 10, '0'))),
    fecha_validacion = CASE
        WHEN estado IN ('exitoso', 'aprobado') THEN COALESCE(fecha_validacion, fecha_pago, created_at)
        ELSE fecha_validacion
    END;

-- statement-break
ALTER TABLE pagos
    MODIFY estado ENUM('pendiente','en_revision','aprobado','rechazado','reembolsado') NOT NULL DEFAULT 'pendiente',
    MODIFY codigo_operacion VARCHAR(120) NOT NULL,
    ADD UNIQUE INDEX uq_pagos_codigo_operacion (codigo_operacion),
    ADD INDEX idx_pagos_validado_por (validado_por),
    ADD CONSTRAINT fk_pagos_validado_por
        FOREIGN KEY (validado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL;
