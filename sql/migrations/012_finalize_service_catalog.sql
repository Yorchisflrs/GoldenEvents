-- Catálogo profesional de servicios. Conserva servicios históricos y evita duplicados.
SET @catalog_provider_id = (
    SELECT p.id
    FROM proveedores p
    INNER JOIN usuarios u ON u.id = p.usuario_id
    INNER JOIN roles r ON r.id = u.rol_id
    WHERE p.estado = 'activo'
      AND u.estado = 'activo'
      AND r.nombre = 'proveedor'
    ORDER BY p.id ASC
    LIMIT 1
);

-- statement-break
SET @catalog_admin_id = (
    SELECT u.id
    FROM usuarios u
    INNER JOIN roles r ON r.id = u.rol_id
    WHERE r.nombre = 'admin' AND u.estado = 'activo'
    ORDER BY u.id ASC
    LIMIT 1
);

-- statement-break
UPDATE categorias_servicio
SET nombre = CASE slug
        WHEN 'animador' THEN 'Animador'
        WHEN 'catering' THEN 'Catering'
        WHEN 'decoracion' THEN 'Decoración'
        WHEN 'dj_musica' THEN 'DJ y Música'
        WHEN 'fotografia_video' THEN 'Fotografía y Video'
        WHEN 'seguridad' THEN 'Seguridad'
        WHEN 'torta' THEN 'Torta'
        ELSE nombre
    END,
    estado = 'activo'
WHERE slug IN (
    'animador', 'catering', 'decoracion', 'dj_musica',
    'fotografia_video', 'seguridad', 'torta'
);

-- statement-break
UPDATE servicios
SET categoria_id = (SELECT id FROM categorias_servicio WHERE slug = 'decoracion'),
    nombre = 'Decoración Golden Premium',
    descripcion = 'Arco principal, centros de mesa, luces cálidas y fondo fotográfico temático.',
    precio = 850.00,
    capacidad = NULL,
    ubicacion = 'Puno',
    imagen = 'public/img/services/catalog/decoracion-golden-premium.jpg',
    disponibilidad = 1,
    estado = 'activo',
    aprobado_por = @catalog_admin_id,
    aprobado_en = COALESCE(aprobado_en, NOW()),
    motivo_rechazo = NULL
WHERE proveedor_id = @catalog_provider_id
  AND nombre IN ('Decoracion Golden Premium', 'Decoración Golden Premium');

-- statement-break
UPDATE servicios
SET categoria_id = (SELECT id FROM categorias_servicio WHERE slug = 'dj_musica'),
    descripcion = 'DJ profesional con consola, parlantes, luces y animación musical.',
    precio = 650.00,
    capacidad = NULL,
    ubicacion = 'Puno',
    imagen = 'public/img/services/catalog/dj-andino-mix.jpg',
    disponibilidad = 1,
    estado = 'activo',
    aprobado_por = @catalog_admin_id,
    aprobado_en = COALESCE(aprobado_en, NOW()),
    motivo_rechazo = NULL
WHERE proveedor_id = @catalog_provider_id
  AND nombre = 'DJ Andino Mix';

-- statement-break
INSERT INTO servicios
    (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen,
     disponibilidad, estado, aprobado_por, aprobado_en, motivo_rechazo)
SELECT
    @catalog_provider_id,
    c.id,
    catalogo.nombre,
    catalogo.descripcion,
    catalogo.precio,
    catalogo.capacidad,
    'Puno',
    catalogo.imagen,
    1,
    'activo',
    @catalog_admin_id,
    NOW(),
    NULL
FROM (
    SELECT 'animador' AS categoria_slug, 'Animación Fiesta Total' AS nombre,
           'Animador profesional, conducción, concursos, música y dinámicas durante cuatro horas.' AS descripcion,
           480.00 AS precio, NULL AS capacidad,
           'public/img/services/catalog/animacion-fiesta-total.jpg' AS imagen
    UNION ALL
    SELECT 'animador', 'Show Infantil Mágico',
           'Animación infantil con juegos, personajes, música, globoflexia y mini show de magia.',
           420.00, NULL, 'public/img/services/catalog/show-infantil-magico.jpg'
    UNION ALL
    SELECT 'catering', 'Buffet Celebración para 50 personas',
           'Entrada, plato principal, bebidas, menaje y personal de atención para cincuenta invitados.',
           1850.00, 50, 'public/img/services/catalog/buffet-celebracion.jpg'
    UNION ALL
    SELECT 'catering', 'Catering Cóctel Premium para 40 personas',
           'Bocaditos dulces y salados, bebidas, menaje y atención para reuniones y recepciones.',
           1450.00, 40, 'public/img/services/catalog/catering-coctel-premium.jpg'
    UNION ALL
    SELECT 'decoracion', 'Decoración Golden Premium',
           'Arco principal, centros de mesa, luces cálidas y fondo fotográfico temático.',
           850.00, NULL, 'public/img/services/catalog/decoracion-golden-premium.jpg'
    UNION ALL
    SELECT 'decoracion', 'Decoración Elegancia Andina',
           'Decoración moderna con detalles andinos, panel principal, flores, iluminación y mesas temáticas.',
           980.00, NULL, 'public/img/services/catalog/decoracion-elegancia-andina.jpg'
    UNION ALL
    SELECT 'dj_musica', 'DJ Andino Mix',
           'DJ profesional con consola, parlantes, luces y animación musical.',
           650.00, NULL, 'public/img/services/catalog/dj-andino-mix.jpg'
    UNION ALL
    SELECT 'dj_musica', 'DJ y Luces Golden Party',
           'DJ, sonido profesional, iluminación dinámica, máquina de humo y conducción musical.',
           900.00, NULL, 'public/img/services/catalog/dj-luces-golden-party.jpg'
    UNION ALL
    SELECT 'fotografia_video', 'Cobertura Fotográfica Profesional',
           'Cobertura fotográfica del evento, selección editada y entrega digital en alta resolución.',
           700.00, NULL, 'public/img/services/catalog/cobertura-fotografica.jpg'
    UNION ALL
    SELECT 'fotografia_video', 'Fotografía y Video 4K',
           'Fotografía, grabación 4K, video resumen editado y entrega digital.',
           1250.00, NULL, 'public/img/services/catalog/fotografia-video-4k.jpg'
    UNION ALL
    SELECT 'seguridad', 'Seguridad para Eventos',
           'Dos agentes de seguridad durante cinco horas, control de ingreso y apoyo preventivo.',
           480.00, NULL, 'public/img/services/catalog/seguridad-eventos.jpg'
    UNION ALL
    SELECT 'seguridad', 'Control de Acceso y Recepción',
           'Registro de invitados, control de lista, orientación y supervisión del acceso.',
           380.00, NULL, 'public/img/services/catalog/control-acceso-recepcion.jpg'
    UNION ALL
    SELECT 'torta', 'Torta Temática Premium',
           'Torta personalizada de aproximadamente cuarenta porciones con decoración temática.',
           350.00, 40, 'public/img/services/catalog/torta-tematica-premium.jpg'
    UNION ALL
    SELECT 'torta', 'Torta de Matrimonio Elegance',
           'Torta elegante de aproximadamente ochenta porciones con acabado personalizado.',
           680.00, 80, 'public/img/services/catalog/torta-matrimonio-elegance.jpg'
) AS catalogo
INNER JOIN categorias_servicio c ON c.slug = catalogo.categoria_slug
LEFT JOIN servicios existente
    ON existente.proveedor_id = @catalog_provider_id
   AND existente.nombre = catalogo.nombre
WHERE @catalog_provider_id IS NOT NULL
  AND @catalog_admin_id IS NOT NULL
  AND existente.id IS NULL;
