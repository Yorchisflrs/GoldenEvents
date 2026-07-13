<?php
// Pruebas de integración de la Parte 2. Todos los datos temporales se revierten.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/ServiceController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/QuoteController.php';

$results = [];
$failures = [];
function part2Assert($name, $condition)
{
    global $results, $failures;
    $passed = (bool) $condition;
    $results[$name] = $passed ? 'OK' : 'FALLO';
    if (!$passed) {
        $failures[] = $name;
    }
}

function part2TableCounts(PDO $pdo)
{
    $tables = ['roles', 'usuarios', 'eventos', 'reservas', 'pagos', 'proveedores', 'categorias_servicio', 'servicios', 'cotizaciones', 'cotizacion_detalles', 'traducciones', 'schema_migrations', 'auditoria_admin'];
    $counts = [];
    foreach ($tables as $table) {
        $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
    return $counts;
}

$baseline = part2TableCounts($pdo);
$prefix = 'codex_part2_' . bin2hex(random_bytes(4));
$password = 'PruebaSegura2026!';

try {
    $pdo->beginTransaction();
    $admin = $pdo->query("SELECT u.* FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE r.nombre = 'admin' AND u.estado = 'activo' ORDER BY u.id LIMIT 1")->fetch();
    part2Assert('administrador_activo_disponible', (bool) $admin);
    $adminId = (int) $admin['id'];

    User::create('Cliente prueba', $prefix . '_cliente@example.test', $password, '900000001', 'cliente');
    User::create('Organizador prueba', $prefix . '_organizador@example.test', $password, '900000002', 'organizador');
    User::create('Proveedor prueba', $prefix . '_proveedor@example.test', $password, '900000003', 'proveedor');
    User::create('Organizador ajeno', $prefix . '_ajeno@example.test', $password, '900000004', 'organizador');
    User::create('Proveedor ajeno', $prefix . '_proveedor_ajeno@example.test', $password, '900000005', 'proveedor');

    $client = User::findByEmail($prefix . '_cliente@example.test');
    $organizer = User::findByEmail($prefix . '_organizador@example.test');
    $providerUser = User::findByEmail($prefix . '_proveedor@example.test');
    $otherOrganizer = User::findByEmail($prefix . '_ajeno@example.test');
    $otherProviderUser = User::findByEmail($prefix . '_proveedor_ajeno@example.test');
    part2Assert('cliente_nuevo_activo', $client && $client['estado'] === 'activo');
    part2Assert('organizador_nuevo_pendiente', $organizer && $organizer['estado'] === 'pendiente');
    part2Assert('proveedor_nuevo_pendiente', $providerUser && $providerUser['estado'] === 'pendiente');
    part2Assert('pendiente_no_inicia_sesion', !AuthController::login($organizer['email'], $password)['success']);

    $approvedOrganizer = AdminController::moderateUser($organizer['id'], 'approve', '', $adminId);
    part2Assert('admin_aprueba_organizador', $approvedOrganizer['success']);
    part2Assert('organizador_aprobado_inicia_sesion', AuthController::login($organizer['email'], $password)['success']);
    $blockedOrganizer = AdminController::moderateUser($organizer['id'], 'block', 'Prueba controlada', $adminId);
    part2Assert('admin_bloquea_organizador', $blockedOrganizer['success']);
    part2Assert('bloqueado_no_inicia_sesion', !AuthController::login($organizer['email'], $password)['success']);
    part2Assert('sesion_existente_bloqueada_se_invalida', currentUser() === null);
    part2Assert('admin_no_puede_bloquearse', !AdminController::moderateUser($adminId, 'block', '', $adminId)['success']);
    AdminController::moderateUser($organizer['id'], 'reactivate', '', $adminId);

    $rejected = AdminController::moderateUser($otherOrganizer['id'], 'reject', 'Documentación incompleta', $adminId);
    part2Assert('admin_rechaza_usuario_con_motivo', $rejected['success']);
    part2Assert('rechazado_no_inicia_sesion', !AuthController::login($otherOrganizer['email'], $password)['success']);
    AdminController::moderateUser($providerUser['id'], 'approve', '', $adminId);
    AdminController::moderateUser($otherProviderUser['id'], 'approve', '', $adminId);

    $category = Category::allActive()[0] ?? null;
    part2Assert('categoria_activa_disponible', (bool) $category);
    $servicePost = ['categoria_id' => $category['id'], 'nombre' => 'Servicio controlado', 'descripcion' => 'Descripción de integración', 'precio' => '125.50', 'capacidad' => '50', 'ubicacion' => 'Puno', 'disponibilidad' => '1'];
    $serviceCreated = ServiceController::createService($providerUser['id'], $servicePost, []);
    $provider = Provider::findByUser($providerUser['id']);
    $service = Service::byProvider($provider['id'])[0] ?? null;
    part2Assert('servicio_creado_pendiente', $serviceCreated['success'] && $service && $service['estado'] === 'pendiente');
    part2Assert('servicio_pendiente_no_publico', !Service::findAvailableById($service['id']));
    part2Assert('admin_aprueba_servicio', AdminController::moderateService($service['id'], 'approve', '', $adminId)['success']);
    part2Assert('servicio_aprobado_publico', (bool) Service::findAvailableById($service['id']));
    $updatedService = ServiceController::updateService($service['id'], $providerUser['id'], array_merge($servicePost, ['nombre' => 'Servicio editado']), []);
    $service = Service::findById($service['id']);
    part2Assert('servicio_editado_vuelve_pendiente', $updatedService['success'] && $service['estado'] === 'pendiente');
    part2Assert('proveedor_no_edita_servicio_ajeno', !ServiceController::updateService($service['id'], $otherProviderUser['id'], $servicePost, [])['success']);
    part2Assert('servicio_rechazado_con_motivo', AdminController::moderateService($service['id'], 'reject', 'Imagen poco clara', $adminId)['success'] && Service::findById($service['id'])['motivo_rechazo'] === 'Imagen poco clara');
    AdminController::moderateService($service['id'], 'review', '', $adminId);
    AdminController::moderateService($service['id'], 'approve', '', $adminId);
    part2Assert('servicio_desactivado_no_publico', AdminController::moderateService($service['id'], 'disable', '', $adminId)['success'] && !Service::findAvailableById($service['id']));
    $invalidUpload = ['imagen' => ['error' => UPLOAD_ERR_OK, 'size' => 20, 'tmp_name' => __FILE__, 'name' => 'payload.php']];
    part2Assert('upload_invalido_rechazado', !ServiceController::createService($providerUser['id'], $servicePost, $invalidUpload)['success']);

    $start = date('Y-m-d\TH:i', strtotime('+30 days'));
    $end = date('Y-m-d\TH:i', strtotime('+30 days +2 hours'));
    $eventPost = ['titulo' => 'Evento controlado', 'descripcion' => 'Descripción de integración', 'categoria' => EventController::categories()[0], 'fecha_inicio' => $start, 'fecha_fin' => $end, 'lugar' => 'Puno', 'direccion' => 'Centro', 'cupo_total' => '100', 'precio' => '20.50', 'estado' => 'publicado'];
    $eventCreated = EventController::createEvent($organizer['id'], $eventPost, []);
    $eventId = (int) $pdo->lastInsertId();
    $event = Event::findById($eventId);
    part2Assert('evento_creado_pendiente_sin_estado_cliente', $eventCreated['success'] && $event['estado'] === 'pendiente_aprobacion');
    part2Assert('evento_pendiente_no_publico', !Event::findAvailableById($eventId));
    part2Assert('admin_aprueba_evento', AdminController::moderateEvent($eventId, 'approve', '', $adminId)['success']);
    part2Assert('evento_aprobado_y_detalle_publico', (bool) Event::findAvailableById($eventId));
    part2Assert('organizador_no_edita_evento_ajeno', !EventController::updateEvent($eventId, $otherOrganizer['id'], $eventPost, [])['success']);
    part2Assert('evento_rechaza_fecha_pasada', !EventController::createEvent($organizer['id'], array_merge($eventPost, ['fecha_inicio' => date('Y-m-d\TH:i', strtotime('-1 day'))]), [])['success']);
    part2Assert('evento_rechaza_fecha_final_invalida', !EventController::createEvent($organizer['id'], array_merge($eventPost, ['fecha_fin' => $start]), [])['success']);
    part2Assert('evento_rechaza_cupo_cero', !EventController::createEvent($organizer['id'], array_merge($eventPost, ['cupo_total' => '0']), [])['success']);
    part2Assert('evento_publicado_editado_vuelve_pendiente', EventController::updateEvent($eventId, $organizer['id'], array_merge($eventPost, ['titulo' => 'Evento editado']), [])['success'] && Event::findById($eventId)['estado'] === 'pendiente_aprobacion');
    part2Assert('evento_rechazado_muestra_motivo', AdminController::moderateEvent($eventId, 'reject', 'Falta información', $adminId)['success'] && Event::findById($eventId)['motivo_rechazo'] === 'Falta información');
    AdminController::moderateEvent($eventId, 'review', '', $adminId);
    AdminController::moderateEvent($eventId, 'approve', '', $adminId);
    part2Assert('evento_cancelado_oculto', EventController::ownerAction($eventId, $organizer['id'], 'cancel')['success'] && !Event::findAvailableById($eventId));

    $pastEvent = $eventPost;
    $pastEvent['fecha_inicio'] = date('Y-m-d H:i:s', strtotime('-2 days'));
    $pastEvent['fecha_fin'] = date('Y-m-d H:i:s', strtotime('-1 day'));
    $pastEvent['organizador_id'] = $organizer['id'];
    $pastEvent['imagen'] = null;
    Event::create($pastEvent);
    $pastEventId = (int) $pdo->lastInsertId();
    Event::moderate($pastEventId, 'publicado', $adminId);
    Event::finalizeExpired();
    part2Assert('evento_pasado_finalizado_idempotente', Event::findById($pastEventId)['estado'] === 'finalizado' && Event::finalizeExpired() === 0);

    $quoteId = (int) $pdo->query('SELECT id FROM cotizaciones ORDER BY id LIMIT 1')->fetchColumn();
    $historicalQuoteId = (int) $pdo->query('SELECT id FROM cotizaciones WHERE id <> ' . $quoteId . ' ORDER BY id LIMIT 1')->fetchColumn();
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("UPDATE cotizaciones SET usuario_id = NULL, public_token_hash = :token, estado = 'pendiente' WHERE id = :id");
    $stmt->execute(['token' => hash('sha256', $token), 'id' => $quoteId]);
    part2Assert('cotizacion_token_valido', (bool) Quote::findByPublicToken($token));
    part2Assert('cotizacion_token_invalido', !Quote::findByPublicToken(str_repeat('0', 64)));
    $stmt = $pdo->prepare('UPDATE cotizaciones SET usuario_id = :usuario_id, public_token_hash = NULL WHERE id = :id');
    $stmt->execute(['usuario_id' => $client['id'], 'id' => $quoteId]);
    part2Assert('cliente_accede_cotizacion_propia', (bool) Quote::findAccessibleById($quoteId, $client['id'], false));
    part2Assert('cliente_no_accede_cotizacion_ajena', !Quote::findAccessibleById($quoteId, $otherOrganizer['id'], false));
    part2Assert('admin_accede_cotizacion', (bool) Quote::findAccessibleById($quoteId, $adminId, true));
    part2Assert('historica_sin_propietario_solo_admin', !Quote::findAccessibleById($historicalQuoteId, $client['id'], false) && (bool) Quote::findAccessibleById($historicalQuoteId, $adminId, true));
    $auditBeforeQuote = AdminAudit::countAll();
    part2Assert('estado_cotizacion_auditado', AdminController::changeQuoteStatus($quoteId, 'contactado', $adminId)['success'] && AdminAudit::countAll() === $auditBeforeQuote + 1);

    $root = dirname(__DIR__);
    $postFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/views'));
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $content = file_get_contents($file->getPathname());
            if (stripos($content, 'method="POST"') !== false) {
                $postFiles[] = strpos($content, 'csrfField()') !== false || strpos($content, 'requireValidCsrfToken()') !== false;
            }
        }
    }
    part2Assert('formularios_post_tienen_csrf', $postFiles && !in_array(false, $postFiles, true));
    part2Assert('logout_get_no_modifica', strpos(file_get_contents($root . '/views/auth/logout.php'), "REQUEST_METHOD'] !== 'POST'") !== false);
    part2Assert('acciones_propietario_solo_post', strpos(file_get_contents($root . '/views/provider/service_action.php'), "REQUEST_METHOD'] !== 'POST'") !== false && strpos(file_get_contents($root . '/views/organizer/event_action.php'), "REQUEST_METHOD'] !== 'POST'") !== false);
    $roleFiles = [
        'admin' => glob($root . '/views/admin/*.php'),
        'proveedor' => glob($root . '/views/provider/*.php'),
        'organizador' => glob($root . '/views/organizer/*.php'),
    ];
    $rolesProtected = true;
    foreach ($roleFiles as $role => $files) {
        foreach ($files as $file) {
            if (strpos(file_get_contents($file), "requireRole('{$role}')") === false) {
                $rolesProtected = false;
            }
        }
    }
    part2Assert('paginas_privadas_exigen_rol', $rolesProtected);
    part2Assert('login_regenera_identificador_sesion', strpos(file_get_contents($root . '/controllers/AuthController.php'), 'session_regenerate_id(true)') !== false);
    part2Assert('sesion_revalida_estado_en_mysql', strpos(file_get_contents($root . '/includes/auth.php'), "u.estado") !== false && strpos(file_get_contents($root . '/includes/auth.php'), "!== 'activo'") !== false);
    part2Assert('archivos_internos_bloqueados', is_file($root . '/storage/.htaccess') && is_file($root . '/public/uploads/.htaccess'));
} catch (Throwable $e) {
    $failures[] = 'excepcion: ' . $e->getMessage();
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

$afterRollback = part2TableCounts($pdo);
part2Assert('cantidades_restauradas_tras_pruebas', $baseline === $afterRollback);

echo json_encode([
    'ok' => count($failures) === 0,
    'pruebas' => $results,
    'fallos' => $failures,
    'cantidades_antes' => $baseline,
    'cantidades_despues' => $afterRollback,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(count($failures) === 0 ? 0 : 1);
