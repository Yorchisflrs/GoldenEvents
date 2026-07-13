<?php
declare(strict_types=1);
define('GHE_TESTING', true);
require_once __DIR__ . '/../controllers/ReservationController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';

global $pdo;
$prefix = 'p3_' . bin2hex(random_bytes(5));
$operationPrefix = strtoupper(str_replace('_', '-', $prefix));
$created = ['users' => [], 'events' => [], 'reservations' => [], 'payments' => [], 'proofs' => []];
$assertions = 0;

function p3Assert($condition, string $message): void
{
    global $assertions;
    $assertions++;
    if (!$condition) throw new RuntimeException('FALLO: ' . $message);
    echo '[OK] ' . $message . PHP_EOL;
}

function p3Hash(): string { return hash('sha256', random_bytes(32)); }

function p3Counts(PDO $db): array
{
    $tables = ['usuarios', 'eventos', 'reservas', 'pagos', 'auditoria_admin']; $result = [];
    foreach ($tables as $table) $result[$table] = (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    return $result;
}

function p3AutoIncrements(PDO $db): array
{
    $stmt = $db->query("SELECT TABLE_NAME, AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('usuarios','eventos','reservas','pagos','auditoria_admin')");
    $result = []; foreach ($stmt as $row) $result[$row['TABLE_NAME']] = (int) $row['AUTO_INCREMENT']; return $result;
}

$beforeCounts = p3Counts($pdo); $beforeAuto = p3AutoIncrements($pdo); $testFailure = null;
$legacyReservation = $pdo->query('SELECT codigo_reserva, estado, cantidad, monto_total FROM reservas WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
$legacyPayment = $pdo->query('SELECT codigo_operacion, estado, monto FROM pagos WHERE id = 1')->fetch(PDO::FETCH_ASSOC);

try {
    $roleIds = $pdo->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
    $insertUser = $pdo->prepare("INSERT INTO usuarios (rol_id,nombre,email,password,estado) VALUES (:rol,:nombre,:email,:password,'activo')");
    foreach (['client' => 'cliente', 'other' => 'cliente', 'organizer' => 'organizador', 'admin' => 'admin'] as $key => $role) {
        $insertUser->execute(['rol' => $roleIds[$role], 'nombre' => 'Test ' . $key, 'email' => $prefix . '_' . $key . '@test.local', 'password' => password_hash('Test1234!', PASSWORD_DEFAULT)]);
        $created['users'][$key] = (int) $pdo->lastInsertId();
    }
    $insertEvent = $pdo->prepare('INSERT INTO eventos (organizador_id,titulo,descripcion,categoria,fecha_inicio,fecha_fin,lugar,cupo_total,precio,estado) VALUES (:organizador,:titulo,:descripcion,:categoria,:inicio,:fin,:lugar,:cupo,:precio,:estado)');
    $eventSpecs = [
        'published' => ['publicado', '+7 days', '80.00', 20], 'draft' => ['borrador', '+7 days', '20.00', 5],
        'cancelled' => ['cancelado', '+7 days', '20.00', 5], 'finished' => ['finalizado', '+7 days', '20.00', 5],
        'past' => ['publicado', '-2 days', '20.00', 5], 'expensive' => ['publicado', '+7 days', '501.00', 5],
        'capacity' => ['publicado', '+8 days', '10.00', 100], 'payment' => ['publicado', '+9 days', '25.00', 20],
    ];
    foreach ($eventSpecs as $key => [$state, $when, $price, $capacity]) {
        $start = new DateTimeImmutable($when); $end = $start->modify('+3 hours');
        $insertEvent->execute(['organizador' => $created['users']['organizer'], 'titulo' => $prefix . ' ' . $key, 'descripcion' => 'Evento temporal Parte 3', 'categoria' => 'Otro', 'inicio' => $start->format('Y-m-d H:i:s'), 'fin' => $end->format('Y-m-d H:i:s'), 'lugar' => 'Test', 'cupo' => $capacity, 'precio' => $price, 'estado' => $state]);
        $created['events'][$key] = (int) $pdo->lastInsertId();
    }

    p3Assert(!Reservation::createPending(0, $created['events']['published'], 1, p3Hash())['success'], 'visitante sin cliente activo rechazado');

    $token = ReservationController::operationToken($created['events']['published']);
    $wrongRole = ReservationController::reserve($created['users']['organizer'], 'organizador', $created['events']['published'], '1', $token);
    p3Assert(!$wrongRole['success'], 'rol incorrecto rechazado');
    foreach (['0', '-1', (string) ((int) appConfig('reservations.max_tickets') + 1)] as $qty) {
        $bad = ReservationController::reserve($created['users']['client'], 'cliente', $created['events']['published'], $qty, ReservationController::operationToken($created['events']['published']));
        p3Assert(!$bad['success'], 'cantidad inválida rechazada: ' . $qty);
    }
    foreach (['draft', 'cancelled', 'finished', 'past', 'expensive'] as $key) {
        $bad = Reservation::createPending($created['users']['client'], $created['events'][$key], 1, p3Hash());
        p3Assert(!$bad['success'], 'evento no reservable rechazado: ' . $key);
    }

    $token = ReservationController::operationToken($created['events']['published']);
    $reservation = ReservationController::reserve($created['users']['client'], 'cliente', $created['events']['published'], '2', $token);
    p3Assert($reservation['success'], 'cliente activo crea reserva pendiente'); $created['reservations'][] = $reservation['reservation_id'];
    $row = Reservation::findById($reservation['reservation_id']);
    p3Assert($row['monto_total'] === '160.00', 'precio y total se recalculan en servidor');
    p3Assert(Reservation::occupiedQuantityForEvent($created['events']['published']) === 2, 'pendiente vigente retiene aforo');
    $repeat = ReservationController::reserve($created['users']['client'], 'cliente', $created['events']['published'], '2', $token);
    p3Assert(!$repeat['success'], 'token consumido no admite reenvío');
    $sameHash = p3Hash(); $first = Reservation::createPending($created['users']['client'], $created['events']['published'], 1, $sameHash); $second = Reservation::createPending($created['users']['client'], $created['events']['published'], 1, $sameHash);
    p3Assert($first['success'] && $second['success'] && !empty($second['duplicate']) && $first['reservation_id'] === $second['reservation_id'], 'idempotencia de base evita reserva duplicada'); $created['reservations'][] = $first['reservation_id'];
    p3Assert($row['codigo_reserva'] !== Reservation::findById($first['reservation_id'])['codigo_reserva'], 'códigos de reserva son únicos');

    $insertReservation = $pdo->prepare("INSERT INTO reservas (codigo_reserva,usuario_id,evento_id,cantidad,precio_unitario,monto_total,estado,metodo_pago,fecha_expiracion) VALUES (:codigo,:usuario,:evento,:cantidad,'10.00',:total,:estado,'yape',:expira)");
    $databaseTimes = $pdo->query("SELECT DATE_ADD(NOW(), INTERVAL 30 MINUTE) AS future_time, DATE_SUB(NOW(), INTERVAL 30 MINUTE) AS past_time")->fetch(PDO::FETCH_ASSOC);
    $capacityRows = [['confirmada', null], ['pago_en_revision', null], ['pendiente_pago', $databaseTimes['future_time']], ['pendiente_pago', $databaseTimes['past_time']], ['vencida', null], ['cancelada', null], ['rechazada', null]];
    foreach ($capacityRows as $index => [$state, $expiry]) { $insertReservation->execute(['codigo' => strtoupper($prefix) . '-CAP-' . $index, 'usuario' => $created['users']['client'], 'evento' => $created['events']['capacity'], 'cantidad' => 1, 'total' => '10.00', 'estado' => $state, 'expira' => $expiry]); $created['reservations'][] = (int) $pdo->lastInsertId(); }
    p3Assert(Reservation::occupiedQuantityForEvent($created['events']['capacity']) === 3, 'solo confirmada, en revisión y pendiente vigente ocupan aforo');
    $expiredId = $created['reservations'][count($created['reservations']) - 4];
    $expireResult = Reservation::expirePending(); $expireAgain = Reservation::expirePending();
    p3Assert($expireResult['expired'] >= 1 && $expireAgain['expired'] === 0, 'vencimiento es efectivo e idempotente');
    p3Assert(Reservation::findById($expiredId)['estado'] === 'vencida', 'pendiente vencida cambia de estado');

    $payReservation = Reservation::createPending($created['users']['client'], $created['events']['payment'], 2, p3Hash()); p3Assert($payReservation['success'], 'reserva de pago creada'); $created['reservations'][] = $payReservation['reservation_id'];
    $wrongOwner = PaymentController::registerPayment($created['users']['other'], $payReservation['reservation_id'], ['codigo_operacion' => 'TEST-OTHER-123'], []); p3Assert(!$wrongOwner['success'], 'otro cliente no registra pago ajeno');
    $invalidFile = tempnam(sys_get_temp_dir(), 'ghe'); file_put_contents($invalidFile, '<?php echo 1;');
    $invalidPay = PaymentController::registerPayment($created['users']['client'], $payReservation['reservation_id'], ['codigo_operacion' => 'TEST-INVALID-123'], ['comprobante' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($invalidFile), 'tmp_name' => $invalidFile, 'name' => 'attack.php']]);
    p3Assert(!$invalidPay['success'], 'ejecutable y MIME inválido rechazados'); unlink($invalidFile);
    $qr = __DIR__ . '/../public/img/payments/yape-qr-jorge-flores.jpg';
    $file = ['error' => UPLOAD_ERR_OK, 'size' => filesize($qr), 'tmp_name' => $qr, 'name' => 'comprobante.jpg'];
    $pay = PaymentController::registerPayment($created['users']['client'], $payReservation['reservation_id'], ['codigo_operacion' => $operationPrefix . '-PAY-1'], ['comprobante' => $file]);
    p3Assert($pay['success'], 'comprobante válido pasa a revisión' . (!empty($pay['message']) ? ' (' . $pay['message'] . ')' : '')); $created['payments'][] = $pay['payment_id'];
    $payRow = Payment::findById($pay['payment_id']); $created['proofs'][] = $payRow['comprobante'];
    p3Assert((bool) Payment::findAccessibleById($pay['payment_id'], $created['users']['client'], false), 'propietario autorizado a consultar comprobante');
    p3Assert(!Payment::findAccessibleById($pay['payment_id'], $created['users']['other'], false), 'otro cliente no puede consultar comprobante');
    p3Assert((bool) Payment::findAccessibleById($pay['payment_id'], $created['users']['admin'], true), 'administrador autorizado a consultar comprobante');
    p3Assert(!deletePrivatePaymentProof('../../config/database.php'), 'path traversal de comprobante rechazado');
    p3Assert($payRow['estado'] === 'en_revision' && $payRow['reserva_estado'] === 'pago_en_revision', 'pago y reserva usan estados en revisión');
    $double = PaymentController::registerPayment($created['users']['client'], $payReservation['reservation_id'], ['codigo_operacion' => $operationPrefix . '-PAY-2'], ['comprobante' => $file]); p3Assert(!$double['success'], 'doble pago rechazado');
    $approve = Payment::adminTransition($pay['payment_id'], 'approve', '', $created['users']['admin']); p3Assert($approve['success'], 'administrador aprueba y confirma');
    p3Assert(!Payment::adminTransition($pay['payment_id'], 'approve', '', $created['users']['admin'])['success'], 'acción administrativa repetida rechazada');
    p3Assert(!Payment::adminTransition($pay['payment_id'], 'refund', '', $created['users']['admin'])['success'], 'reembolso exige motivo');
    p3Assert(Payment::adminTransition($pay['payment_id'], 'refund', 'Prueba controlada', $created['users']['admin'])['success'], 'reembolso cancela reserva y libera cupo');

    $rejectReservation = Reservation::createPending($created['users']['client'], $created['events']['payment'], 1, p3Hash()); $created['reservations'][] = $rejectReservation['reservation_id'];
    $rejectPay = PaymentController::registerPayment($created['users']['client'], $rejectReservation['reservation_id'], ['codigo_operacion' => $operationPrefix . '-PAY-R'], ['comprobante' => $file]); $created['payments'][] = $rejectPay['payment_id']; $rejectRow = Payment::findById($rejectPay['payment_id']); $created['proofs'][] = $rejectRow['comprobante'];
    p3Assert(!Payment::adminTransition($rejectPay['payment_id'], 'reject', '', $created['users']['admin'])['success'], 'rechazo exige motivo');
    p3Assert(Payment::adminTransition($rejectPay['payment_id'], 'reject', 'Operación no válida', $created['users']['admin'])['success'], 'rechazo libera aforo y guarda estado');
    $auditCount = (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE (entidad='pago' AND entidad_id IN (" . implode(',', array_map('intval', $created['payments'])) . "))")->fetchColumn();
    p3Assert($auditCount >= 3, 'transiciones de pago generan auditoría');

    $cancelReservation = Reservation::createPending($created['users']['client'], $created['events']['payment'], 1, p3Hash()); $created['reservations'][] = $cancelReservation['reservation_id'];
    p3Assert(Reservation::cancelByClient($cancelReservation['reservation_id'], $created['users']['client'])['success'], 'cliente cancela pendiente mediante transición válida');
    p3Assert($legacyReservation === $pdo->query('SELECT codigo_reserva, estado, cantidad, monto_total FROM reservas WHERE id = 1')->fetch(PDO::FETCH_ASSOC), 'reserva heredada permanece intacta');
    p3Assert($legacyPayment === $pdo->query('SELECT codigo_operacion, estado, monto FROM pagos WHERE id = 1')->fetch(PDO::FETCH_ASSOC), 'pago heredado permanece intacto');
    echo 'INTEGRATION_OK assertions=' . $assertions . PHP_EOL;
} catch (Throwable $e) {
    $testFailure = $e;
} finally {
    foreach ($created['proofs'] as $proof) deletePrivatePaymentProof($proof);
    if ($created['payments']) { $ids = implode(',', array_map('intval', $created['payments'])); $pdo->exec("DELETE FROM auditoria_admin WHERE entidad='pago' AND entidad_id IN ({$ids})"); }
    if ($created['reservations']) { $ids = implode(',', array_map('intval', $created['reservations'])); $pdo->exec("DELETE FROM auditoria_admin WHERE entidad='reserva' AND entidad_id IN ({$ids})"); $pdo->exec("DELETE FROM pagos WHERE reserva_id IN ({$ids})"); $pdo->exec("DELETE FROM reservas WHERE id IN ({$ids})"); }
    if ($created['events']) { $ids = implode(',', array_map('intval', $created['events'])); $pdo->exec("DELETE FROM eventos WHERE id IN ({$ids})"); }
    if ($created['users']) { $ids = implode(',', array_map('intval', $created['users'])); $pdo->exec("DELETE FROM usuarios WHERE id IN ({$ids})"); }
    foreach ($beforeAuto as $table => $next) { $max = (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM {$table}")->fetchColumn(); if ($next > $max) $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = {$next}"); }
    $afterCounts = p3Counts($pdo);
    if ($beforeCounts !== $afterCounts) { fwrite(STDERR, 'Conteos distintos tras limpieza: ' . json_encode(['before' => $beforeCounts, 'after' => $afterCounts]) . PHP_EOL); exit(2); }
    $leftovers = glob(paymentProofDirectory() . DIRECTORY_SEPARATOR . 'proof_*') ?: [];
    echo 'CLEANUP_OK counts=' . json_encode($afterCounts) . ' proof_files=' . count($leftovers) . PHP_EOL;
}
if ($testFailure) { fwrite(STDERR, $testFailure->getMessage() . PHP_EOL); exit(1); }
