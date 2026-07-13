-- Base para registrar futuras acciones administrativas.
CREATE TABLE IF NOT EXISTS auditoria_admin (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    administrador_id INT UNSIGNED NULL,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    entidad_id BIGINT UNSIGNED NULL,
    detalles TEXT NULL,
    direccion_ip VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_admin_usuario
        FOREIGN KEY (administrador_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_auditoria_admin_usuario (administrador_id),
    INDEX idx_auditoria_admin_entidad (entidad, entidad_id),
    INDEX idx_auditoria_admin_fecha (created_at)
) ENGINE=InnoDB;
