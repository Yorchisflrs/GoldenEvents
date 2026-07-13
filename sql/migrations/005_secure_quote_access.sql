-- Solo se almacena el hash SHA-256 del token publico de invitados.
ALTER TABLE cotizaciones
    ADD COLUMN public_token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER usuario_id,
    ADD UNIQUE INDEX uq_cotizaciones_public_token_hash (public_token_hash);
