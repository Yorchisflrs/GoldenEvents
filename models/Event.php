<?php
// Modelo de eventos con PDO.

require_once __DIR__ . '/../config/database.php';

class Event
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function allAvailable()
    {
        $sql = "SELECT *
                FROM vista_eventos_disponibles
                WHERE estado = 'activo' AND cupos_disponibles > 0
                ORDER BY fecha_inicio ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $sql = "SELECT e.*, u.nombre AS organizador
                FROM eventos e
                INNER JOIN usuarios u ON e.organizador_id = u.id
                WHERE e.id = :id
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function findAvailableById($id)
    {
        $sql = "SELECT *
                FROM vista_eventos_disponibles
                WHERE id = :id AND estado = 'activo'
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $sql = "INSERT INTO eventos
                (organizador_id, titulo, descripcion, categoria, fecha_inicio, fecha_fin, lugar, direccion, cupo_total, precio, estado)
                VALUES
                (:organizador_id, :titulo, :descripcion, :categoria, :fecha_inicio, :fecha_fin, :lugar, :direccion, :cupo_total, :precio, :estado)";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([
            'organizador_id' => $data['organizador_id'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'categoria' => $data['categoria'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?: null,
            'lugar' => $data['lugar'],
            'direccion' => $data['direccion'] ?? null,
            'cupo_total' => $data['cupo_total'],
            'precio' => $data['precio'],
            'estado' => $data['estado'] ?? 'activo',
        ]);
    }

    public static function byOrganizer($organizadorId)
    {
        $sql = "SELECT *
                FROM eventos
                WHERE organizador_id = :organizador_id
                ORDER BY fecha_inicio DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['organizador_id' => $organizadorId]);
        return $stmt->fetchAll();
    }

    public static function update($id, $organizadorId, $data)
    {
        $sql = "UPDATE eventos
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    categoria = :categoria,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    lugar = :lugar,
                    direccion = :direccion,
                    cupo_total = :cupo_total,
                    precio = :precio,
                    estado = :estado
                WHERE id = :id AND organizador_id = :organizador_id";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'categoria' => $data['categoria'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?: null,
            'lugar' => $data['lugar'],
            'direccion' => $data['direccion'] ?? null,
            'cupo_total' => $data['cupo_total'],
            'precio' => $data['precio'],
            'estado' => $data['estado'] ?? 'activo',
            'id' => $id,
            'organizador_id' => $organizadorId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function deleteByOrganizer($id, $organizadorId)
    {
        $sql = "UPDATE eventos
                SET estado = 'cancelado'
                WHERE id = :id AND organizador_id = :organizador_id";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id, 'organizador_id' => $organizadorId]);
        return $stmt->rowCount() > 0;
    }

    public static function all()
    {
        $sql = "SELECT e.*, u.nombre AS organizador
                FROM eventos e
                INNER JOIN usuarios u ON e.organizador_id = u.id
                ORDER BY e.fecha_inicio DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM eventos');
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) $row['total'];
    }
}
