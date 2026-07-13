<?php
// Persistencia, aforo y transiciones de reservas.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/AdminAudit.php';

class Reservation
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function states()
    {
        return ['pendiente_pago', 'pago_en_revision', 'confirmada', 'cancelada', 'vencida', 'rechazada'];
    }

    private static function capacityExpression($alias = '')
    {
        $prefix = $alias === '' ? '' : rtrim((string) $alias, '.') . '.';
        return "CASE
                    WHEN {$prefix}estado IN ('confirmada', 'pago_en_revision') THEN {$prefix}cantidad
                    WHEN {$prefix}estado = 'pendiente_pago' AND {$prefix}fecha_expiracion IS NOT NULL AND {$prefix}fecha_expiracion > NOW() THEN {$prefix}cantidad
                    ELSE 0 END";
    }

    public static function occupiedQuantityForEvent($eventId)
    {
        $sql = 'SELECT COALESCE(SUM(' . self::capacityExpression() . '), 0) AS ocupados
                FROM reservas WHERE evento_id = :evento_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['evento_id' => (int) $eventId]);
        return (int) $stmt->fetch()['ocupados'];
    }

    public static function createPending($userId, $eventId, $quantity, $idempotencyHash)
    {
        $db = self::db();
        $ownsTransaction = !$db->inTransaction();
        $quantity = (int) $quantity;
        $maxTickets = (int) appConfig('reservations.max_tickets');
        $expirationMinutes = (int) appConfig('reservations.expiration_minutes');
        $maxAmountCents = moneyToCents(appConfig('payments.yape_max_amount'));

        if (!appConfig('payments.yape_enabled', true)) {
            return ['success' => false, 'message' => 'El método de pago no está disponible temporalmente.'];
        }
        if ($quantity < 1 || $quantity > $maxTickets) {
            return ['success' => false, 'message' => "La cantidad debe estar entre 1 y {$maxTickets}." ];
        }
        if (!is_string($idempotencyHash) || !preg_match('/^[a-f0-9]{64}$/', $idempotencyHash)) {
            return ['success' => false, 'message' => 'La operación de reserva no es válida.'];
        }

        try {
            if ($ownsTransaction) {
                $db->beginTransaction();
            }

            $userStmt = $db->prepare("SELECT u.id FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
                                      WHERE u.id = :id AND u.estado = 'activo' AND r.nombre = 'cliente' LIMIT 1");
            $userStmt->execute(['id' => (int) $userId]);
            if (!$userStmt->fetch()) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'Solo un cliente activo puede realizar reservas.'];
            }

            $eventLock = $db->prepare('SELECT id FROM eventos WHERE id = :id FOR UPDATE');
            $eventLock->execute(['id' => (int) $eventId]);
            if (!$eventLock->fetchColumn()) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'El evento no está disponible para reservas.'];
            }

            $eventStmt = $db->prepare("SELECT e.*, u.estado AS organizador_estado,
                                              (e.fecha_inicio > NOW()) AS evento_futuro
                                       FROM eventos e INNER JOIN usuarios u ON u.id = e.organizador_id
                                       WHERE e.id = :id");
            $eventStmt->execute(['id' => (int) $eventId]);
            $event = $eventStmt->fetch();
            if (!$event || $event['estado'] !== 'publicado' || $event['organizador_estado'] !== 'activo' || !(bool) $event['evento_futuro']) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'El evento no está disponible para reservas.'];
            }

            $existingStmt = $db->prepare('SELECT id FROM reservas WHERE idempotency_key_hash = :hash LIMIT 1');
            $existingStmt->execute(['hash' => $idempotencyHash]);
            $existingId = $existingStmt->fetchColumn();
            if ($existingId) {
                if ($ownsTransaction) $db->commit();
                return ['success' => true, 'duplicate' => true, 'reservation_id' => (int) $existingId, 'message' => 'La reserva ya había sido registrada.'];
            }

            $priceCents = moneyToCents($event['precio']);
            if ($priceCents === null || $maxAmountCents === null) {
                throw new RuntimeException('Importe de evento o límite Yape inválido.');
            }
            $totalCents = $priceCents * $quantity;
            if ($totalCents > $maxAmountCents) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'El total supera el máximo permitido por Yape. Reduce la cantidad de entradas.'];
            }

            // El bloqueo explícito del rango evita que otra transacción calcule aforo
            // sobre un conjunto desactualizado mientras se inserta una reserva.
            $capacityLock = $db->prepare('SELECT id, ' . self::capacityExpression() . ' AS cupos_ocupados FROM reservas WHERE evento_id = :evento_id FOR UPDATE');
            $capacityLock->execute(['evento_id' => (int) $eventId]);
            $occupied = 0;
            foreach ($capacityLock->fetchAll() as $capacityRow) {
                $occupied += (int) $capacityRow['cupos_ocupados'];
            }
            $available = max(0, (int) $event['cupo_total'] - $occupied);
            if ($quantity > $available) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'No hay cupos suficientes disponibles.'];
            }

            $insertSql = "INSERT INTO reservas
                (codigo_reserva, idempotency_key_hash, usuario_id, evento_id, cantidad,
                 precio_unitario, monto_total, estado, metodo_pago, fecha_expiracion)
                VALUES (:codigo, :hash, :usuario_id, :evento_id, :cantidad,
                        :precio, :total, 'pendiente_pago', 'yape',
                        DATE_ADD(NOW(), INTERVAL {$expirationMinutes} MINUTE))";
            $insert = $db->prepare($insertSql);
            $reservationId = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $insert->execute([
                        'codigo' => generateReservationCode(),
                        'hash' => $idempotencyHash,
                        'usuario_id' => (int) $userId,
                        'evento_id' => (int) $eventId,
                        'cantidad' => $quantity,
                        'precio' => centsToMoney($priceCents),
                        'total' => centsToMoney($totalCents),
                    ]);
                    $reservationId = (int) $db->lastInsertId();
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                    $existingStmt->execute(['hash' => $idempotencyHash]);
                    $existingId = $existingStmt->fetchColumn();
                    if ($existingId) {
                        if ($ownsTransaction) $db->commit();
                        return ['success' => true, 'duplicate' => true, 'reservation_id' => (int) $existingId, 'message' => 'La reserva ya había sido registrada.'];
                    }
                }
            }
            if (!$reservationId) {
                throw new RuntimeException('No se pudo generar un código de reserva único.');
            }

            if ($ownsTransaction) {
                $db->commit();
            }
            return ['success' => true, 'duplicate' => false, 'reservation_id' => $reservationId, 'message' => 'Reserva creada. Registra el pago antes de su vencimiento.'];
        } catch (Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[GoldenHourEvents] Error al crear reserva: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo completar la reserva.'];
        }
    }

    private static function detailSelect()
    {
        return "SELECT r.*, (r.fecha_expiracion IS NOT NULL AND r.fecha_expiracion > NOW()) AS plazo_vigente,
                       e.titulo AS evento, e.fecha_inicio, e.fecha_fin, e.lugar, e.direccion,
                       e.organizador_id, u.nombre AS cliente, u.email AS cliente_email,
                       p.id AS pago_id, p.estado AS pago_estado, p.codigo_operacion,
                       p.comprobante, p.fecha_pago, p.fecha_validacion,
                       p.motivo_rechazo, p.motivo_reembolso
                FROM reservas r
                INNER JOIN eventos e ON e.id = r.evento_id
                INNER JOIN usuarios u ON u.id = r.usuario_id
                LEFT JOIN pagos p ON p.reserva_id = r.id";
    }

    public static function findById($id)
    {
        $stmt = self::db()->prepare(self::detailSelect() . ' WHERE r.id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);
        return $stmt->fetch();
    }

    public static function findOwnedById($id, $userId)
    {
        $stmt = self::db()->prepare(self::detailSelect() . ' WHERE r.id = :id AND r.usuario_id = :usuario_id LIMIT 1');
        $stmt->execute(['id' => (int) $id, 'usuario_id' => (int) $userId]);
        return $stmt->fetch();
    }

    public static function byUser($userId)
    {
        $stmt = self::db()->prepare(self::detailSelect() . ' WHERE r.usuario_id = :usuario_id ORDER BY r.fecha_reserva DESC, r.id DESC');
        $stmt->execute(['usuario_id' => (int) $userId]);
        return $stmt->fetchAll();
    }

    public static function byOrganizer($organizerId)
    {
        $stmt = self::db()->prepare(self::detailSelect() . ' WHERE e.organizador_id = :organizador_id ORDER BY r.fecha_reserva DESC, r.id DESC');
        $stmt->execute(['organizador_id' => (int) $organizerId]);
        return $stmt->fetchAll();
    }

    public static function organizerEventStats($organizerId)
    {
        $sql = "SELECT e.id, e.titulo, e.cupo_total,
                    COALESCE(SUM(CASE WHEN r.estado = 'confirmada' THEN r.cantidad ELSE 0 END), 0) AS confirmadas,
                    COALESCE(SUM(CASE WHEN r.estado = 'pago_en_revision' OR
                        (r.estado = 'pendiente_pago' AND r.fecha_expiracion > NOW()) THEN r.cantidad ELSE 0 END), 0) AS retenidas,
                    COALESCE(SUM(CASE WHEN p.estado = 'aprobado' THEN p.monto ELSE 0 END), 0) AS ingresos_aprobados
                FROM eventos e
                LEFT JOIN reservas r ON r.evento_id = e.id
                LEFT JOIN pagos p ON p.reserva_id = r.id
                WHERE e.organizador_id = :organizador_id
                GROUP BY e.id, e.titulo, e.cupo_total
                ORDER BY e.fecha_inicio DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['organizador_id' => (int) $organizerId]);
        return $stmt->fetchAll();
    }

    public static function all($filters = [])
    {
        $sql = self::detailSelect() . ' WHERE 1 = 1';
        $params = [];
        if (!empty($filters['estado']) && in_array($filters['estado'], self::states(), true)) {
            $sql .= ' AND r.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['evento_id']) && (int) $filters['evento_id'] > 0) {
            $sql .= ' AND r.evento_id = :evento_id';
            $params['evento_id'] = (int) $filters['evento_id'];
        }
        if (!empty($filters['cliente_id']) && (int) $filters['cliente_id'] > 0) {
            $sql .= ' AND r.usuario_id = :cliente_id';
            $params['cliente_id'] = (int) $filters['cliente_id'];
        }
        $sql .= ' ORDER BY r.fecha_reserva DESC, r.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function expirePending()
    {
        $db = self::db();
        $count = $db->prepare("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente_pago'");
        $count->execute();
        $evaluated = (int) $count->fetchColumn();
        $stmt = $db->prepare("UPDATE reservas SET estado = 'vencida', motivo_estado = 'Plazo de pago vencido.'
                              WHERE estado = 'pendiente_pago' AND fecha_expiracion IS NOT NULL AND fecha_expiracion <= NOW()");
        $stmt->execute();
        return ['evaluated' => $evaluated, 'expired' => $stmt->rowCount()];
    }

    public static function cancelByClient($reservationId, $userId)
    {
        $db = self::db();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT *, (fecha_expiracion IS NOT NULL AND fecha_expiracion > NOW()) AS plazo_vigente FROM reservas WHERE id = :id AND usuario_id = :usuario_id FOR UPDATE');
            $stmt->execute(['id' => (int) $reservationId, 'usuario_id' => (int) $userId]);
            $reservation = $stmt->fetch();
            if (!$reservation) {
                $db->rollBack();
                return ['success' => false, 'message' => 'La reserva no existe o no te pertenece.'];
            }
            if ($reservation['estado'] !== 'pendiente_pago') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Esta reserva requiere intervención administrativa para cancelarse.'];
            }
            if (!(bool) $reservation['plazo_vigente']) {
                $update = $db->prepare("UPDATE reservas SET estado = 'vencida', motivo_estado = 'Plazo de pago vencido.' WHERE id = :id");
                $update->execute(['id' => (int) $reservationId]);
                $db->commit();
                return ['success' => false, 'message' => 'La reserva ya había vencido.'];
            }
            $update = $db->prepare("UPDATE reservas SET estado = 'cancelada', motivo_estado = 'Cancelada por el cliente.' WHERE id = :id");
            $update->execute(['id' => (int) $reservationId]);
            if (!AdminAudit::record(null, 'reserva_cancelada_cliente', 'reserva', $reservationId, $reservation['estado'], 'cancelada', ['usuario_id' => (int) $userId])) {
                throw new RuntimeException('No se pudo registrar la auditoría de cancelación.');
            }
            $db->commit();
            return ['success' => true, 'message' => 'Reserva cancelada correctamente.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[GoldenHourEvents] Error al cancelar reserva del cliente: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo cancelar la reserva.'];
        }
    }

    public static function adminCancel($reservationId, $adminId, $reason)
    {
        $reason = trim((string) $reason);
        $db = self::db();
        try {
            $db->beginTransaction();
            $admin = $db->prepare("SELECT u.id FROM usuarios u INNER JOIN roles ro ON ro.id = u.rol_id WHERE u.id = :id AND u.estado = 'activo' AND ro.nombre = 'admin'");
            $admin->execute(['id' => (int) $adminId]);
            if (!$admin->fetch()) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No tienes autorización administrativa.'];
            }
            $stmt = $db->prepare('SELECT r.*, p.estado AS pago_estado FROM reservas r LEFT JOIN pagos p ON p.reserva_id = r.id WHERE r.id = :id FOR UPDATE');
            $stmt->execute(['id' => (int) $reservationId]);
            $reservation = $stmt->fetch();
            if (!$reservation || !in_array($reservation['estado'], ['pendiente_pago', 'confirmada'], true)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'La reserva no admite cancelación administrativa en su estado actual.'];
            }
            if ($reservation['estado'] === 'confirmada' && $reason === '') {
                $db->rollBack();
                return ['success' => false, 'message' => 'El motivo es obligatorio para cancelar una reserva confirmada.'];
            }
            if ($reservation['estado'] === 'confirmada' && $reservation['pago_estado'] === 'aprobado') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Utiliza el reembolso del pago para cancelar esta reserva confirmada.'];
            }
            $reason = $reason ?: 'Cancelada por administración.';
            $update = $db->prepare("UPDATE reservas SET estado = 'cancelada', motivo_estado = :motivo WHERE id = :id");
            $update->execute(['motivo' => $reason, 'id' => (int) $reservationId]);
            if (!AdminAudit::record($adminId, 'reserva_cancelada_admin', 'reserva', $reservationId, $reservation['estado'], 'cancelada', ['motivo' => $reason])) {
                throw new RuntimeException('No se pudo registrar la auditoría de cancelación administrativa.');
            }
            $db->commit();
            return ['success' => true, 'message' => 'Reserva cancelada correctamente.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[GoldenHourEvents] Error en cancelación administrativa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo cancelar la reserva.'];
        }
    }

    public static function countAll()
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM reservas')->fetchColumn();
    }

    public static function countByStatus($status)
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM reservas WHERE estado = :estado');
        $stmt->execute(['estado' => $status]);
        return (int) $stmt->fetchColumn();
    }
}
