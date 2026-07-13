<?php
// Persistencia y transiciones del pago Yape manual.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/AdminAudit.php';

class Payment
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function states()
    {
        return ['pendiente', 'en_revision', 'aprobado', 'rechazado', 'reembolsado'];
    }

    private static function detailSelect()
    {
        return "SELECT p.*, r.codigo_reserva, r.usuario_id, r.evento_id, r.cantidad,
                       r.monto_total AS reserva_total, r.estado AS reserva_estado,
                       u.nombre AS cliente, u.email AS cliente_email,
                       e.titulo AS evento, e.fecha_inicio, e.organizador_id
                FROM pagos p
                INNER JOIN reservas r ON r.id = p.reserva_id
                INNER JOIN usuarios u ON u.id = r.usuario_id
                INNER JOIN eventos e ON e.id = r.evento_id";
    }

    public static function findById($id)
    {
        $stmt = self::db()->prepare(self::detailSelect() . ' WHERE p.id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);
        return $stmt->fetch();
    }

    public static function findByReservation($reservationId)
    {
        $stmt = self::db()->prepare(self::detailSelect() . ' WHERE p.reserva_id = :reserva_id LIMIT 1');
        $stmt->execute(['reserva_id' => (int) $reservationId]);
        return $stmt->fetch();
    }

    public static function findAccessibleById($id, $userId, $isAdmin = false)
    {
        $sql = self::detailSelect() . ' WHERE p.id = :id';
        $params = ['id' => (int) $id];
        if (!$isAdmin) {
            $sql .= ' AND r.usuario_id = :usuario_id';
            $params['usuario_id'] = (int) $userId;
        }
        $stmt = self::db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public static function operationCodeExists($code)
    {
        $stmt = self::db()->prepare('SELECT id FROM pagos WHERE codigo_operacion = :codigo LIMIT 1');
        $stmt->execute(['codigo' => $code]);
        return (bool) $stmt->fetchColumn();
    }

    public static function registerForReservation($userId, $reservationId, $operationCode, $proofPath)
    {
        $db = self::db();
        $ownsTransaction = !$db->inTransaction();
        try {
            if ($ownsTransaction) $db->beginTransaction();

            $userStmt = $db->prepare("SELECT u.id FROM usuarios u INNER JOIN roles ro ON ro.id = u.rol_id
                                      WHERE u.id = :id AND u.estado = 'activo' AND ro.nombre = 'cliente'");
            $userStmt->execute(['id' => (int) $userId]);
            if (!$userStmt->fetch()) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'Solo el cliente propietario puede registrar el pago.'];
            }

            $stmt = $db->prepare('SELECT *, (fecha_expiracion IS NOT NULL AND fecha_expiracion > NOW()) AS plazo_vigente FROM reservas WHERE id = :id AND usuario_id = :usuario_id FOR UPDATE');
            $stmt->execute(['id' => (int) $reservationId, 'usuario_id' => (int) $userId]);
            $reservation = $stmt->fetch();
            if (!$reservation) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'La reserva no existe o no te pertenece.'];
            }
            if ($reservation['estado'] !== 'pendiente_pago') {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'La reserva ya no admite un nuevo pago.'];
            }
            if (!(bool) $reservation['plazo_vigente']) {
                $expire = $db->prepare("UPDATE reservas SET estado = 'vencida', motivo_estado = 'Plazo de pago vencido.' WHERE id = :id");
                $expire->execute(['id' => (int) $reservationId]);
                if ($ownsTransaction) $db->commit();
                return ['success' => false, 'message' => 'La reserva venció y ya no admite pagos.'];
            }
            $maxCents = moneyToCents(appConfig('payments.yape_max_amount'));
            $totalCents = moneyToCents($reservation['monto_total']);
            if ($maxCents === null || $totalCents === null || $totalCents > $maxCents) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'El monto no puede procesarse mediante Yape.'];
            }
            $existing = $db->prepare('SELECT id FROM pagos WHERE reserva_id = :reserva_id LIMIT 1');
            $existing->execute(['reserva_id' => (int) $reservationId]);
            if ($existing->fetch()) {
                if ($ownsTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'La reserva ya tiene un pago registrado.'];
            }

            $insert = $db->prepare("INSERT INTO pagos
                (reserva_id, monto, moneda, metodo, codigo_operacion, comprobante, estado, fecha_pago)
                VALUES (:reserva_id, :monto, 'PEN', 'yape', :codigo, :comprobante, 'en_revision', NOW())");
            $insert->execute([
                'reserva_id' => (int) $reservationId,
                'monto' => centsToMoney($totalCents),
                'codigo' => $operationCode,
                'comprobante' => $proofPath,
            ]);
            $paymentId = (int) $db->lastInsertId();
            $update = $db->prepare("UPDATE reservas SET estado = 'pago_en_revision', metodo_pago = 'yape', codigo_transaccion = :codigo WHERE id = :id");
            $update->execute(['codigo' => $operationCode, 'id' => (int) $reservationId]);
            if ($ownsTransaction) $db->commit();
            return ['success' => true, 'payment_id' => $paymentId, 'message' => 'Comprobante registrado. El pago quedó en revisión.'];
        } catch (PDOException $e) {
            if ($ownsTransaction && $db->inTransaction()) $db->rollBack();
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'El código de operación ya fue registrado.'];
            }
            error_log('[GoldenHourEvents] Error SQL al registrar pago: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo registrar el pago.'];
        } catch (Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) $db->rollBack();
            error_log('[GoldenHourEvents] Error al registrar pago: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo registrar el pago.'];
        }
    }

    public static function adminTransition($paymentId, $action, $reason, $adminId)
    {
        $reason = trim((string) $reason);
        $db = self::db();
        try {
            $db->beginTransaction();
            $admin = $db->prepare("SELECT u.id FROM usuarios u INNER JOIN roles ro ON ro.id = u.rol_id
                                   WHERE u.id = :id AND u.estado = 'activo' AND ro.nombre = 'admin'");
            $admin->execute(['id' => (int) $adminId]);
            if (!$admin->fetch()) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No tienes autorización administrativa.'];
            }
            $paymentStmt = $db->prepare('SELECT * FROM pagos WHERE id = :id FOR UPDATE');
            $paymentStmt->execute(['id' => (int) $paymentId]);
            $payment = $paymentStmt->fetch();
            if (!$payment) {
                $db->rollBack();
                return ['success' => false, 'message' => 'El pago no existe.'];
            }
            $reservationStmt = $db->prepare('SELECT * FROM reservas WHERE id = :id FOR UPDATE');
            $reservationStmt->execute(['id' => (int) $payment['reserva_id']]);
            $reservation = $reservationStmt->fetch();
            if (!$reservation || moneyToCents($payment['monto']) !== moneyToCents($reservation['monto_total'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'El monto del pago no coincide con la reserva.'];
            }

            if ($action === 'approve') {
                if ($payment['estado'] !== 'en_revision' || $reservation['estado'] !== 'pago_en_revision') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'El pago ya fue procesado o no puede aprobarse.'];
                }
                $payState = 'aprobado';
                $reservationState = 'confirmada';
                $paySql = "UPDATE pagos SET estado = 'aprobado', validado_por = :admin, fecha_validacion = NOW(), motivo_rechazo = NULL, motivo_reembolso = NULL WHERE id = :id";
                $payParams = ['admin' => (int) $adminId, 'id' => (int) $paymentId];
            } elseif ($action === 'reject') {
                if ($reason === '') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'El motivo de rechazo es obligatorio.'];
                }
                if ($payment['estado'] !== 'en_revision' || $reservation['estado'] !== 'pago_en_revision') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'El pago ya fue procesado o no puede rechazarse.'];
                }
                $payState = 'rechazado';
                $reservationState = 'rechazada';
                $paySql = "UPDATE pagos SET estado = 'rechazado', validado_por = :admin, fecha_validacion = NOW(), motivo_rechazo = :motivo WHERE id = :id";
                $payParams = ['admin' => (int) $adminId, 'motivo' => $reason, 'id' => (int) $paymentId];
            } elseif ($action === 'refund') {
                if ($reason === '') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'El motivo de reembolso es obligatorio.'];
                }
                if ($payment['estado'] !== 'aprobado' || $reservation['estado'] !== 'confirmada') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Solo un pago aprobado y confirmado puede reembolsarse.'];
                }
                $payState = 'reembolsado';
                $reservationState = 'cancelada';
                $paySql = "UPDATE pagos SET estado = 'reembolsado', validado_por = :admin, fecha_validacion = NOW(), motivo_reembolso = :motivo WHERE id = :id";
                $payParams = ['admin' => (int) $adminId, 'motivo' => $reason, 'id' => (int) $paymentId];
            } else {
                $db->rollBack();
                return ['success' => false, 'message' => 'La acción de pago no está permitida.'];
            }

            $payUpdate = $db->prepare($paySql);
            $payUpdate->execute($payParams);
            $reservationUpdate = $db->prepare('UPDATE reservas SET estado = :estado, motivo_estado = :motivo WHERE id = :id');
            $reservationUpdate->execute([
                'estado' => $reservationState,
                'motivo' => in_array($action, ['reject', 'refund'], true) ? $reason : null,
                'id' => (int) $reservation['id'],
            ]);
            if (!AdminAudit::record($adminId, 'pago_' . $action, 'pago', $paymentId, $payment['estado'], $payState, [
                'reserva_id' => (int) $reservation['id'],
                'reserva_estado_anterior' => $reservation['estado'],
                'reserva_estado_nuevo' => $reservationState,
                'motivo' => $reason ?: null,
            ])) {
                throw new RuntimeException('No se pudo registrar la auditoría del pago.');
            }
            $db->commit();
            return ['success' => true, 'message' => 'El pago y la reserva fueron actualizados correctamente.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[GoldenHourEvents] Error en validación administrativa de pago: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo procesar el pago.'];
        }
    }

    public static function all($filters = [])
    {
        $sql = self::detailSelect() . ' WHERE 1 = 1';
        $params = [];
        if (!empty($filters['estado']) && in_array($filters['estado'], self::states(), true)) {
            $sql .= ' AND p.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['evento_id']) && (int) $filters['evento_id'] > 0) {
            $sql .= ' AND r.evento_id = :evento_id';
            $params['evento_id'] = (int) $filters['evento_id'];
        }
        if (!empty($filters['fecha'])) {
            $sql .= ' AND DATE(p.fecha_pago) = :fecha';
            $params['fecha'] = $filters['fecha'];
        }
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countSuccessful()
    {
        return self::countByStatus('aprobado');
    }

    public static function countByStatus($status)
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM pagos WHERE estado = :estado');
        $stmt->execute(['estado' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public static function totalRevenue()
    {
        return (string) self::db()->query("SELECT COALESCE(SUM(monto), 0.00) FROM pagos WHERE estado = 'aprobado'")->fetchColumn();
    }
}
