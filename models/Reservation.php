<?php
// Modelo de reservas y pagos simulados.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Reservation
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function createWithPayment($usuarioId, $eventoId, $cantidad, $metodoPago = 'simulado')
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT * FROM eventos WHERE id = :id AND estado = 'activo' FOR UPDATE");
            $stmt->execute(['id' => $eventoId]);
            $evento = $stmt->fetch();

            if (!$evento) {
                $db->rollBack();
                return ['success' => false, 'message' => 'El evento no esta disponible.'];
            }

            $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad), 0) AS reservados
                                  FROM reservas
                                  WHERE evento_id = :evento_id AND estado = 'pagado'");
            $stmt->execute(['evento_id' => $eventoId]);
            $reserved = (int) $stmt->fetch()['reservados'];
            $available = (int) $evento['cupo_total'] - $reserved;

            if ($cantidad > $available) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No hay cupos suficientes disponibles.'];
            }

            $total = (float) $evento['precio'] * $cantidad;
            $transactionCode = generateTransactionCode();

            $stmt = $db->prepare("INSERT INTO reservas
                                  (usuario_id, evento_id, cantidad, monto_total, estado, metodo_pago, codigo_transaccion)
                                  VALUES
                                  (:usuario_id, :evento_id, :cantidad, :monto_total, 'pagado', :metodo_pago, :codigo_transaccion)");
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'evento_id' => $eventoId,
                'cantidad' => $cantidad,
                'monto_total' => $total,
                'metodo_pago' => $metodoPago,
                'codigo_transaccion' => $transactionCode,
            ]);

            $reservationId = $db->lastInsertId();
            $paymentReference = 'PAY-' . date('Ymd') . '-' . strtoupper(uniqid());

            $stmt = $db->prepare("INSERT INTO pagos
                                  (reserva_id, monto, moneda, metodo, estado, referencia, fecha_pago)
                                  VALUES
                                  (:reserva_id, :monto, 'PEN', :metodo, 'exitoso', :referencia, NOW())");
            $stmt->execute([
                'reserva_id' => $reservationId,
                'monto' => $total,
                'metodo' => $metodoPago,
                'referencia' => $paymentReference,
            ]);

            $db->commit();
            return ['success' => true, 'message' => 'Reserva registrada correctamente.', 'reservation_id' => $reservationId];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('[GoldenHourEvents] Error al crear reserva: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo completar la reserva.'];
        }
    }

    public static function byUser($usuarioId)
    {
        $sql = "SELECT r.*, e.titulo, e.fecha_inicio, e.lugar
                FROM reservas r
                INNER JOIN eventos e ON r.evento_id = e.id
                WHERE r.usuario_id = :usuario_id
                ORDER BY r.fecha_reserva DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public static function byOrganizer($organizadorId)
    {
        $sql = "SELECT r.*, c.nombre AS cliente, e.titulo, e.fecha_inicio, e.lugar
                FROM reservas r
                INNER JOIN eventos e ON r.evento_id = e.id
                INNER JOIN usuarios c ON r.usuario_id = c.id
                WHERE e.organizador_id = :organizador_id
                ORDER BY r.fecha_reserva DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['organizador_id' => $organizadorId]);
        return $stmt->fetchAll();
    }

    public static function all()
    {
        $sql = "SELECT r.*, u.nombre AS cliente, e.titulo AS evento
                FROM reservas r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN eventos e ON r.evento_id = e.id
                ORDER BY r.fecha_reserva DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM reservas');
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) $row['total'];
    }
}
