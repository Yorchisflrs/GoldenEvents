-- La vista considera reservas confirmadas y retenciones aun vigentes.
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
        WHEN r.estado = 'confirmada' THEN r.cantidad
        WHEN r.estado IN ('pendiente_pago', 'pago_en_revision')
            AND r.fecha_expiracion IS NOT NULL
            AND r.fecha_expiracion > NOW() THEN r.cantidad
        ELSE 0
    END), 0) AS cupos_reservados,
    e.cupo_total - COALESCE(SUM(CASE
        WHEN r.estado = 'confirmada' THEN r.cantidad
        WHEN r.estado IN ('pendiente_pago', 'pago_en_revision')
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
