<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/Reservation.php';
global $pdo;

if (($argv[1] ?? '') === '--worker') {
    $userId = (int) ($argv[2] ?? 0); $eventId = (int) ($argv[3] ?? 0); $hash = (string) ($argv[4] ?? ''); $gate = (string) ($argv[5] ?? '');
    $deadline = microtime(true) + 10;
    while (!is_file($gate) && microtime(true) < $deadline) usleep(10000);
    if (!is_file($gate)) { echo json_encode(['success' => false, 'message' => 'gate timeout']); exit(3); }
    echo json_encode(Reservation::createPending($userId, $eventId, 1, $hash), JSON_UNESCAPED_UNICODE);
    exit;
}

$prefix = 'p3c_' . bin2hex(random_bytes(5)); $users = []; $eventId = 0; $reservationIds = [];
$autoStmt = $pdo->query("SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('usuarios','eventos','reservas')"); $autos = []; foreach ($autoStmt as $row) $autos[$row['TABLE_NAME']] = (int) $row['AUTO_INCREMENT'];
$counts = ['usuarios' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(), 'eventos' => (int) $pdo->query('SELECT COUNT(*) FROM eventos')->fetchColumn(), 'reservas' => (int) $pdo->query('SELECT COUNT(*) FROM reservas')->fetchColumn()];
$gate = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . '.gate'; $testFailure = null;
try {
    $roles = $pdo->query('SELECT nombre,id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
    $insertUser = $pdo->prepare("INSERT INTO usuarios (rol_id,nombre,email,password,estado) VALUES (:rol,:nombre,:email,:password,'activo')");
    foreach (['organizer' => 'organizador', 'client1' => 'cliente', 'client2' => 'cliente'] as $key => $role) { $insertUser->execute(['rol' => $roles[$role], 'nombre' => $key, 'email' => $prefix . '_' . $key . '@test.local', 'password' => password_hash('Test1234!', PASSWORD_DEFAULT)]); $users[$key] = (int) $pdo->lastInsertId(); }
    $start = new DateTimeImmutable('+10 days'); $end = $start->modify('+2 hours');
    $stmt = $pdo->prepare("INSERT INTO eventos (organizador_id,titulo,descripcion,categoria,fecha_inicio,fecha_fin,lugar,cupo_total,precio,estado) VALUES (:organizer,:title,'Concurrencia real','Otro',:start,:end,'Test',1,'10.00','publicado')");
    $stmt->execute(['organizer' => $users['organizer'], 'title' => $prefix, 'start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')]); $eventId = (int) $pdo->lastInsertId();
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']]; $processes = [];
    foreach ([$users['client1'], $users['client2']] as $clientId) {
        $command = [PHP_BINARY, __FILE__, '--worker', (string) $clientId, (string) $eventId, hash('sha256', random_bytes(32)), $gate];
        $pipes = []; $process = proc_open($command, $descriptors, $pipes, __DIR__ . '/..'); if (!is_resource($process)) throw new RuntimeException('No se pudo iniciar proceso concurrente.'); fclose($pipes[0]); $processes[] = [$process, $pipes];
    }
    touch($gate); $results = [];
    foreach ($processes as [$process, $pipes]) { $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process); if ($code !== 0) throw new RuntimeException('Worker falló: ' . $stderr . ' / ' . $stdout); $decoded = json_decode(trim($stdout), true); if (!is_array($decoded)) throw new RuntimeException('Salida inválida del worker: ' . $stdout); $results[] = $decoded; }
    $successes = array_values(array_filter($results, fn($result) => !empty($result['success']))); $failures = array_values(array_filter($results, fn($result) => empty($result['success'])));
    $reservationIds = array_map(fn($result) => (int) $result['reservation_id'], $successes);
    $occupied = Reservation::occupiedQuantityForEvent($eventId); $count = (int) $pdo->query('SELECT COUNT(*) FROM reservas WHERE evento_id=' . $eventId)->fetchColumn();
    if (count($successes) !== 1 || count($failures) !== 1 || $occupied !== 1 || $count !== 1) throw new RuntimeException('Resultado de concurrencia inválido: ' . json_encode(['results' => $results, 'occupied' => $occupied, 'count' => $count]));
    echo '[OK] dos procesos/conexiones independientes compitieron por un cupo' . PHP_EOL;
    echo '[OK] una reserva exitosa, una rechazada, ocupación final 1, nunca 2' . PHP_EOL;
    echo 'CONCURRENCY_OK' . PHP_EOL;
} catch (Throwable $e) {
    $testFailure = $e;
} finally {
    if (is_file($gate)) unlink($gate);
    if ($eventId) $pdo->exec('DELETE FROM reservas WHERE evento_id=' . $eventId);
    if ($eventId) $pdo->exec('DELETE FROM eventos WHERE id=' . $eventId);
    if ($users) $pdo->exec('DELETE FROM usuarios WHERE id IN (' . implode(',', array_map('intval', $users)) . ')');
    foreach ($autos as $table => $next) { $max = (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM {$table}")->fetchColumn(); if ($next > $max) $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT={$next}"); }
    $after = ['usuarios' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(), 'eventos' => (int) $pdo->query('SELECT COUNT(*) FROM eventos')->fetchColumn(), 'reservas' => (int) $pdo->query('SELECT COUNT(*) FROM reservas')->fetchColumn()];
    if ($after !== $counts) { fwrite(STDERR, 'Limpieza de concurrencia incompleta: ' . json_encode(['before' => $counts, 'after' => $after]) . PHP_EOL); exit(2); }
    echo 'CONCURRENCY_CLEANUP_OK' . PHP_EOL;
}
if ($testFailure) { fwrite(STDERR, $testFailure->getMessage() . PHP_EOL); exit(1); }
