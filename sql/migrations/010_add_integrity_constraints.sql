-- Reglas de integridad soportadas y aplicadas por MariaDB 10.4.
ALTER TABLE eventos
    ADD CONSTRAINT chk_eventos_cupo_positivo CHECK (cupo_total > 0),
    ADD CONSTRAINT chk_eventos_precio_no_negativo CHECK (precio >= 0),
    ADD CONSTRAINT chk_eventos_fechas CHECK (fecha_fin IS NULL OR fecha_fin > fecha_inicio);

-- statement-break
ALTER TABLE servicios
    ADD CONSTRAINT chk_servicios_precio_no_negativo CHECK (precio >= 0);

-- statement-break
ALTER TABLE cotizaciones
    ADD CONSTRAINT chk_cotizaciones_invitados_positivos CHECK (cantidad_invitados > 0),
    ADD CONSTRAINT chk_cotizaciones_total_no_negativo CHECK (total_estimado >= 0);

-- statement-break
ALTER TABLE cotizacion_detalles
    ADD CONSTRAINT chk_cotizacion_detalles_cantidad_positiva CHECK (cantidad > 0),
    ADD CONSTRAINT chk_cotizacion_detalles_precio_no_negativo CHECK (precio_unitario >= 0),
    ADD CONSTRAINT chk_cotizacion_detalles_subtotal_no_negativo CHECK (subtotal >= 0);

-- statement-break
ALTER TABLE reservas
    ADD CONSTRAINT chk_reservas_cantidad_positiva CHECK (cantidad > 0),
    ADD CONSTRAINT chk_reservas_precio_no_negativo CHECK (precio_unitario >= 0),
    ADD CONSTRAINT chk_reservas_monto_no_negativo CHECK (monto_total >= 0);

-- statement-break
ALTER TABLE pagos
    ADD CONSTRAINT chk_pagos_monto_no_negativo CHECK (monto >= 0);
