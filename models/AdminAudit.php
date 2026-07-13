<?php
// Registro centralizado de acciones administrativas no sensibles.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class AdminAudit
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function record($adminId, $action, $entity, $entityId, $previousState = null, $newState = null, $extra = [])
    {
        $details = [
            'estado_anterior' => $previousState,
            'estado_nuevo' => $newState,
            'informacion' => is_array($extra) ? $extra : [],
        ];

        $sql = 'INSERT INTO auditoria_admin
                (administrador_id, accion, entidad, entidad_id, detalles, direccion_ip, user_agent)
                VALUES (:administrador_id, :accion, :entidad, :entidad_id, :detalles, :direccion_ip, :user_agent)';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([
            'administrador_id' => (int) $adminId,
            'accion' => substr((string) $action, 0, 100),
            'entidad' => substr((string) $entity, 0, 100),
            'entidad_id' => $entityId === null ? null : (int) $entityId,
            'detalles' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'direccion_ip' => requestIpAddress(),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    public static function paginated($page = 1, $perPage = 20)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT a.*, u.nombre AS administrador_nombre, u.email AS administrador_email
                FROM auditoria_admin a
                INNER JOIN usuarios u ON u.id = a.administrador_id
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM auditoria_admin');
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }
}
