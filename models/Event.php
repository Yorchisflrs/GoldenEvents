<?php
// Modelo de eventos con propiedad, catálogo y moderación.

require_once __DIR__ . '/../config/database.php';

class Event
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function finalizeExpired()
    {
        $sql = "UPDATE eventos
                SET estado = 'finalizado'
                WHERE COALESCE(fecha_fin, fecha_inicio) < NOW()
                  AND estado IN ('borrador','pendiente_aprobacion','publicado','rechazado','inactivo')";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function allAvailable($filters = [])
    {
        $sql = "SELECT v.*, e.imagen
                FROM vista_eventos_disponibles v
                INNER JOIN eventos e ON e.id = v.id
                INNER JOIN usuarios u ON u.id = e.organizador_id
                WHERE v.estado = 'publicado'
                  AND v.fecha_inicio > NOW()
                  AND v.cupos_disponibles > 0
                  AND u.estado = 'activo'";
        $params = [];

        if (!empty($filters['categoria'])) {
            $sql .= ' AND v.categoria = :categoria';
            $params['categoria'] = $filters['categoria'];
        }
        if (!empty($filters['fecha'])) {
            $sql .= ' AND DATE(v.fecha_inicio) = :fecha';
            $params['fecha'] = $filters['fecha'];
        }
        if (!empty($filters['ubicacion'])) {
            $sql .= ' AND (v.lugar LIKE :ubicacion OR v.direccion LIKE :ubicacion)';
            $params['ubicacion'] = '%' . $filters['ubicacion'] . '%';
        }
        if (($filters['precio'] ?? '') === 'gratuito') {
            $sql .= ' AND v.precio = 0.00';
        } elseif (($filters['precio'] ?? '') === 'pago') {
            $sql .= ' AND v.precio > 0.00';
        }
        if (!empty($filters['buscar'])) {
            $sql .= ' AND v.titulo LIKE :buscar';
            $params['buscar'] = '%' . $filters['buscar'] . '%';
        }
        $sql .= ' ORDER BY v.fecha_inicio ASC, v.id ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $sql = "SELECT e.*, u.nombre AS organizador, u.email AS organizador_email,
                       aprobador.nombre AS aprobador_nombre
                FROM eventos e
                INNER JOIN usuarios u ON u.id = e.organizador_id
                LEFT JOIN usuarios aprobador ON aprobador.id = e.aprobado_por
                WHERE e.id = :id LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        return $stmt->fetch();
    }

    public static function findOwnedById($id, $organizerId)
    {
        $stmt = self::db()->prepare('SELECT * FROM eventos WHERE id = :id AND organizador_id = :organizador_id LIMIT 1');
        $stmt->execute(['id' => (int) $id, 'organizador_id' => (int) $organizerId]);
        return $stmt->fetch();
    }

    public static function findAvailableById($id)
    {
        $sql = "SELECT v.*, e.imagen
                FROM vista_eventos_disponibles v
                INNER JOIN eventos e ON e.id = v.id
                INNER JOIN usuarios u ON u.id = e.organizador_id
                WHERE v.id = :id
                  AND v.estado = 'publicado'
                  AND v.fecha_inicio > NOW()
                  AND v.cupos_disponibles > 0
                  AND u.estado = 'activo'
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $sql = "INSERT INTO eventos
                (organizador_id, titulo, descripcion, categoria, fecha_inicio, fecha_fin,
                 lugar, direccion, cupo_total, precio, imagen, estado)
                VALUES (:organizador_id, :titulo, :descripcion, :categoria, :fecha_inicio, :fecha_fin,
                        :lugar, :direccion, :cupo_total, :precio, :imagen, 'pendiente_aprobacion')";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([
            'organizador_id' => (int) $data['organizador_id'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'categoria' => $data['categoria'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'lugar' => $data['lugar'],
            'direccion' => $data['direccion'] ?: null,
            'cupo_total' => (int) $data['cupo_total'],
            'precio' => $data['precio'],
            'imagen' => $data['imagen'] ?: null,
        ]);
    }

    public static function byOrganizer($organizerId)
    {
        $stmt = self::db()->prepare('SELECT * FROM eventos WHERE organizador_id = :organizador_id ORDER BY fecha_inicio DESC, id DESC');
        $stmt->execute(['organizador_id' => (int) $organizerId]);
        return $stmt->fetchAll();
    }

    public static function updateOwned($id, $organizerId, $data)
    {
        $sql = "UPDATE eventos
                SET titulo = :titulo, descripcion = :descripcion, categoria = :categoria,
                    fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, lugar = :lugar,
                    direccion = :direccion, cupo_total = :cupo_total, precio = :precio,
                    imagen = :imagen, estado = 'pendiente_aprobacion', aprobado_por = NULL,
                    aprobado_en = NULL, motivo_rechazo = NULL
                WHERE id = :id AND organizador_id = :organizador_id
                  AND estado NOT IN ('cancelado','finalizado')";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'categoria' => $data['categoria'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'lugar' => $data['lugar'],
            'direccion' => $data['direccion'] ?: null,
            'cupo_total' => (int) $data['cupo_total'],
            'precio' => $data['precio'],
            'imagen' => $data['imagen'] ?: null,
            'id' => (int) $id,
            'organizador_id' => (int) $organizerId,
        ]);
        if ($stmt->rowCount() > 0) {
            return true;
        }
        $current = self::findOwnedById($id, $organizerId);
        return $current && !in_array($current['estado'], ['cancelado', 'finalizado'], true);
    }

    public static function ownerAction($id, $organizerId, $action)
    {
        if ($action === 'cancel') {
            $sql = "UPDATE eventos SET estado = 'cancelado'
                    WHERE id = :id AND organizador_id = :organizador_id
                      AND estado NOT IN ('cancelado','finalizado')";
        } elseif ($action === 'review') {
            $sql = "UPDATE eventos SET estado = 'pendiente_aprobacion', motivo_rechazo = NULL,
                           aprobado_por = NULL, aprobado_en = NULL
                    WHERE id = :id AND organizador_id = :organizador_id
                      AND estado IN ('borrador','rechazado','inactivo')
                      AND fecha_inicio > NOW()";
        } else {
            return false;
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => (int) $id, 'organizador_id' => (int) $organizerId]);
        return $stmt->rowCount() > 0;
    }

    public static function all($filters = [])
    {
        $sql = "SELECT e.*, u.nombre AS organizador, u.email AS organizador_email,
                       aprobador.nombre AS aprobador_nombre
                FROM eventos e
                INNER JOIN usuarios u ON u.id = e.organizador_id
                LEFT JOIN usuarios aprobador ON aprobador.id = e.aprobado_por
                WHERE 1 = 1";
        $params = [];
        $states = ['borrador', 'pendiente_aprobacion', 'publicado', 'rechazado', 'cancelado', 'finalizado', 'inactivo'];
        if (!empty($filters['estado']) && in_array($filters['estado'], $states, true)) {
            $sql .= ' AND e.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['organizador_id']) && (int) $filters['organizador_id'] > 0) {
            $sql .= ' AND e.organizador_id = :organizador_id';
            $params['organizador_id'] = (int) $filters['organizador_id'];
        }
        if (!empty($filters['categoria'])) {
            $sql .= ' AND e.categoria = :categoria';
            $params['categoria'] = $filters['categoria'];
        }
        $sql .= ' ORDER BY e.fecha_inicio DESC, e.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function moderate($id, $status, $adminId, $reason = null)
    {
        if ($status === 'publicado') {
            $sql = 'UPDATE eventos SET estado = :estado, motivo_rechazo = NULL,
                    aprobado_por = :admin_id, aprobado_en = :aprobado_en WHERE id = :id';
            $params = ['estado' => $status, 'admin_id' => (int) $adminId, 'aprobado_en' => date('Y-m-d H:i:s'), 'id' => (int) $id];
        } elseif (in_array($status, ['borrador', 'pendiente_aprobacion', 'rechazado'], true)) {
            $sql = 'UPDATE eventos SET estado = :estado, motivo_rechazo = :motivo,
                    aprobado_por = NULL, aprobado_en = NULL WHERE id = :id';
            $params = ['estado' => $status, 'motivo' => $reason ?: null, 'id' => (int) $id];
        } else {
            $sql = 'UPDATE eventos SET estado = :estado, motivo_rechazo = :motivo WHERE id = :id';
            $params = ['estado' => $status, 'motivo' => $reason ?: null, 'id' => (int) $id];
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM eventos');
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    public static function countByStatus($status)
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM eventos WHERE estado = :estado');
        $stmt->execute(['estado' => $status]);
        return (int) $stmt->fetch()['total'];
    }

    public static function categories()
    {
        $stmt = self::db()->prepare('SELECT DISTINCT categoria FROM eventos WHERE categoria IS NOT NULL AND categoria <> \'\' ORDER BY categoria');
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'categoria');
    }
}
