<?php
// Modelo de servicios para el marketplace, propiedad y moderación.

require_once __DIR__ . '/../config/database.php';

class Service
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    private static function publicSelect()
    {
        return "SELECT s.*, c.nombre AS categoria, c.slug AS categoria_slug,
                       u.nombre AS proveedor, u.id AS proveedor_usuario_id
                FROM servicios s
                INNER JOIN proveedores p ON p.id = s.proveedor_id
                INNER JOIN usuarios u ON u.id = p.usuario_id
                INNER JOIN categorias_servicio c ON c.id = s.categoria_id";
    }

    public static function allAvailable($categoryId = null)
    {
        $sql = self::publicSelect() . "
                WHERE s.disponibilidad = 1
                  AND s.estado = 'activo'
                  AND p.estado = 'activo'
                  AND u.estado = 'activo'
                  AND c.estado = 'activo'";
        $params = [];
        if ($categoryId !== null && (int) $categoryId > 0) {
            $sql .= ' AND s.categoria_id = :categoria_id';
            $params['categoria_id'] = (int) $categoryId;
        }
        $sql .= ' ORDER BY c.nombre ASC, s.nombre ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findAvailableById($id)
    {
        $sql = self::publicSelect() . "
                WHERE s.id = :id
                  AND s.disponibilidad = 1
                  AND s.estado = 'activo'
                  AND p.estado = 'activo'
                  AND u.estado = 'activo'
                  AND c.estado = 'activo'
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $sql = "SELECT s.*, c.nombre AS categoria, c.slug AS categoria_slug,
                       u.nombre AS proveedor, u.email AS proveedor_email,
                       u.id AS proveedor_usuario_id
                FROM servicios s
                INNER JOIN proveedores p ON p.id = s.proveedor_id
                INNER JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN categorias_servicio c ON c.id = s.categoria_id
                WHERE s.id = :id LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        return $stmt->fetch();
    }

    public static function findOwnedByUser($id, $userId)
    {
        $sql = "SELECT s.*, c.nombre AS categoria, p.usuario_id AS proveedor_usuario_id
                FROM servicios s
                INNER JOIN proveedores p ON p.id = s.proveedor_id
                LEFT JOIN categorias_servicio c ON c.id = s.categoria_id
                WHERE s.id = :id AND p.usuario_id = :usuario_id LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => (int) $id, 'usuario_id' => (int) $userId]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $sql = "INSERT INTO servicios
                (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado)
                VALUES (:proveedor_id, :categoria_id, :nombre, :descripcion, :precio,
                        :capacidad, :ubicacion, :imagen, :disponibilidad, 'pendiente')";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([
            'proveedor_id' => (int) $data['proveedor_id'],
            'categoria_id' => (int) $data['categoria_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'precio' => $data['precio'],
            'capacidad' => $data['capacidad'],
            'ubicacion' => $data['ubicacion'] ?: null,
            'imagen' => $data['imagen'] ?: null,
            'disponibilidad' => !empty($data['disponibilidad']) ? 1 : 0,
        ]);
    }

    public static function byProvider($providerId)
    {
        $sql = "SELECT s.*, c.nombre AS categoria
                FROM servicios s
                LEFT JOIN categorias_servicio c ON c.id = s.categoria_id
                WHERE s.proveedor_id = :proveedor_id
                ORDER BY s.created_at DESC, s.id DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['proveedor_id' => (int) $providerId]);
        return $stmt->fetchAll();
    }

    public static function updateOwned($id, $providerId, $data, $newState)
    {
        $sql = "UPDATE servicios
                SET categoria_id = :categoria_id, nombre = :nombre, descripcion = :descripcion,
                    precio = :precio, capacidad = :capacidad, ubicacion = :ubicacion,
                    imagen = :imagen, disponibilidad = :disponibilidad, estado = :estado,
                    aprobado_por = NULL, aprobado_en = NULL, motivo_rechazo = NULL
                WHERE id = :id AND proveedor_id = :proveedor_id";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([
            'categoria_id' => (int) $data['categoria_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'precio' => $data['precio'],
            'capacidad' => $data['capacidad'],
            'ubicacion' => $data['ubicacion'] ?: null,
            'imagen' => $data['imagen'] ?: null,
            'disponibilidad' => !empty($data['disponibilidad']) ? 1 : 0,
            'estado' => $newState,
            'id' => (int) $id,
            'proveedor_id' => (int) $providerId,
        ]);
    }

    public static function ownerAction($id, $providerId, $action, $availability = null)
    {
        if ($action === 'availability') {
            $stmt = self::db()->prepare('UPDATE servicios SET disponibilidad = :valor WHERE id = :id AND proveedor_id = :proveedor_id');
            $stmt->execute(['valor' => $availability ? 1 : 0, 'id' => (int) $id, 'proveedor_id' => (int) $providerId]);
        } elseif ($action === 'disable') {
            $stmt = self::db()->prepare("UPDATE servicios SET estado = 'inactivo', disponibilidad = 0 WHERE id = :id AND proveedor_id = :proveedor_id AND estado <> 'inactivo'");
            $stmt->execute(['id' => (int) $id, 'proveedor_id' => (int) $providerId]);
        } elseif ($action === 'review') {
            $stmt = self::db()->prepare("UPDATE servicios SET estado = 'pendiente', motivo_rechazo = NULL, aprobado_por = NULL, aprobado_en = NULL WHERE id = :id AND proveedor_id = :proveedor_id AND estado IN ('rechazado','inactivo')");
            $stmt->execute(['id' => (int) $id, 'proveedor_id' => (int) $providerId]);
        } else {
            return false;
        }
        return $stmt->rowCount() > 0;
    }

    public static function allForAdmin($filters = [])
    {
        $sql = "SELECT s.*, c.nombre AS categoria, u.nombre AS proveedor, u.email AS proveedor_email
                FROM servicios s
                INNER JOIN proveedores p ON p.id = s.proveedor_id
                INNER JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN categorias_servicio c ON c.id = s.categoria_id
                WHERE 1 = 1";
        $params = [];
        $states = ['pendiente', 'activo', 'rechazado', 'inactivo'];
        if (!empty($filters['estado']) && in_array($filters['estado'], $states, true)) {
            $sql .= ' AND s.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['categoria_id']) && (int) $filters['categoria_id'] > 0) {
            $sql .= ' AND s.categoria_id = :categoria_id';
            $params['categoria_id'] = (int) $filters['categoria_id'];
        }
        if (!empty($filters['proveedor_id']) && (int) $filters['proveedor_id'] > 0) {
            $sql .= ' AND s.proveedor_id = :proveedor_id';
            $params['proveedor_id'] = (int) $filters['proveedor_id'];
        }
        $sql .= ' ORDER BY s.created_at DESC, s.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function moderate($id, $status, $adminId, $reason = null)
    {
        if ($status === 'activo') {
            $sql = 'UPDATE servicios SET estado = :estado, motivo_rechazo = NULL,
                    aprobado_por = :admin_id, aprobado_en = :aprobado_en WHERE id = :id';
            $params = ['estado' => $status, 'admin_id' => (int) $adminId, 'aprobado_en' => date('Y-m-d H:i:s'), 'id' => (int) $id];
        } elseif (in_array($status, ['pendiente', 'rechazado'], true)) {
            $sql = 'UPDATE servicios SET estado = :estado, motivo_rechazo = :motivo,
                    aprobado_por = NULL, aprobado_en = NULL WHERE id = :id';
            $params = ['estado' => $status, 'motivo' => $reason ?: null, 'id' => (int) $id];
        } else {
            $sql = 'UPDATE servicios SET estado = :estado, motivo_rechazo = :motivo WHERE id = :id';
            $params = ['estado' => $status, 'motivo' => $reason ?: null, 'id' => (int) $id];
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM servicios');
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    public static function countByStatus($status)
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM servicios WHERE estado = :estado');
        $stmt->execute(['estado' => $status]);
        return (int) $stmt->fetch()['total'];
    }
}
