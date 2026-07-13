-- Completa idempotencia y motivos del flujo de reservas y pagos sin perder datos.
ALTER TABLE reservas
    ADD COLUMN idempotency_key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER codigo_reserva,
    ADD COLUMN motivo_estado VARCHAR(500) NULL AFTER fecha_expiracion,
    ADD UNIQUE INDEX uq_reservas_idempotency_key_hash (idempotency_key_hash),
    ADD INDEX idx_reservas_aforo (evento_id, estado, fecha_expiracion);

-- statement-break
ALTER TABLE pagos
    ADD COLUMN motivo_reembolso VARCHAR(500) NULL AFTER motivo_rechazo;

-- statement-break
CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW vista_eventos_disponibles AS
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
    COALESCE(SUM(CASE
        WHEN r.estado IN ('confirmada', 'pago_en_revision') THEN r.cantidad
        WHEN r.estado = 'pendiente_pago'
            AND r.fecha_expiracion IS NOT NULL
            AND r.fecha_expiracion > NOW() THEN r.cantidad
        ELSE 0
    END), 0) AS cupos_reservados,
    e.cupo_total - COALESCE(SUM(CASE
        WHEN r.estado IN ('confirmada', 'pago_en_revision') THEN r.cantidad
        WHEN r.estado = 'pendiente_pago'
            AND r.fecha_expiracion IS NOT NULL
            AND r.fecha_expiracion > NOW() THEN r.cantidad
        ELSE 0
    END), 0) AS cupos_disponibles
FROM eventos e
INNER JOIN usuarios u ON e.organizador_id = u.id
LEFT JOIN reservas r ON e.id = r.evento_id
GROUP BY
    e.id, e.titulo, e.descripcion, e.categoria, e.fecha_inicio, e.fecha_fin,
    e.lugar, e.direccion, e.cupo_total, e.precio, e.estado, u.nombre;
