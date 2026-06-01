<?php
// Modelo de pagos.

require_once __DIR__ . '/../config/database.php';

class Payment
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function all()
    {
        $sql = "SELECT p.*, r.codigo_transaccion, u.nombre AS cliente, e.titulo AS evento
                FROM pagos p
                INNER JOIN reservas r ON p.reserva_id = r.id
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN eventos e ON r.evento_id = e.id
                ORDER BY p.created_at DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countSuccessful()
    {
        $stmt = self::db()->prepare("SELECT COUNT(*) AS total FROM pagos WHERE estado = 'exitoso'");
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) $row['total'];
    }

    public static function totalRevenue()
    {
        $stmt = self::db()->prepare("SELECT COALESCE(SUM(monto), 0) AS total FROM pagos WHERE estado = 'exitoso'");
        $stmt->execute();
        $row = $stmt->fetch();
        return (float) $row['total'];
    }
}
