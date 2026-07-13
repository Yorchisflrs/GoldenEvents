<?php
// Modelo de servicios para el marketplace/cotizador.

require_once __DIR__ . '/../config/database.php';

class Service
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function allAvailable($categoriaId = null)
    {
        $params = [];
        $where = "s.disponibilidad = 1 AND s.estado = 'activo' AND p.estado = 'activo'";

        if ($categoriaId !== null && (int) $categoriaId > 0) {
            $where .= ' AND s.categoria_id = :categoria_id';
            $params['categoria_id'] = (int) $categoriaId;
        }

        $sql = "SELECT
                    s.id,
                    s.nombre,
                    s.descripcion,
                    s.precio,
                    s.capacidad,
                    s.ubicacion,
                    s.imagen,
                    s.disponibilidad,
                    c.nombre AS categoria,
                    u.nombre AS proveedor
                FROM servicios s
                INNER JOIN proveedores p ON s.proveedor_id = p.id
                INNER JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN categorias_servicio c ON s.categoria_id = c.id
                WHERE {$where}
                ORDER BY c.nombre ASC, s.nombre ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $sql = "SELECT s.*, c.nombre AS categoria, c.slug AS categoria_slug, u.nombre AS proveedor
                FROM servicios s
                INNER JOIN proveedores p ON s.proveedor_id = p.id
                INNER JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN categorias_servicio c ON s.categoria_id = c.id
                WHERE s.id = :id
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $sql = "INSERT INTO servicios
                (proveedor_id, categoria_id, nombre, descripcion, precio, capacidad, ubicacion, imagen, disponibilidad, estado)
                VALUES
                (:proveedor_id, :categoria_id, :nombre, :descripcion, :precio, :capacidad, :ubicacion, :imagen, :disponibilidad, :estado)";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([
            'proveedor_id' => $data['proveedor_id'],
            'categoria_id' => $data['categoria_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'precio' => $data['precio'],
            'capacidad' => $data['capacidad'] ?: null,
            'ubicacion' => $data['ubicacion'] ?: null,
            'imagen' => $data['imagen'] ?: null,
            'disponibilidad' => $data['disponibilidad'] ?? 1,
            'estado' => $data['estado'] ?? 'pendiente',
        ]);
    }

    public static function byProvider($proveedorId)
    {
        $sql = "SELECT s.*, c.nombre AS categoria
                FROM servicios s
                LEFT JOIN categorias_servicio c ON s.categoria_id = c.id
                WHERE s.proveedor_id = :proveedor_id
                ORDER BY s.created_at DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['proveedor_id' => $proveedorId]);
        return $stmt->fetchAll();
    }

    public static function update($id, $proveedorId, $data)
    {
        $sql = "UPDATE servicios
                SET categoria_id = :categoria_id,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio = :precio,
                    capacidad = :capacidad,
                    ubicacion = :ubicacion,
                    imagen = :imagen,
                    disponibilidad = :disponibilidad,
                    estado = :estado
                WHERE id = :id AND proveedor_id = :proveedor_id";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            'categoria_id' => $data['categoria_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'precio' => $data['precio'],
            'capacidad' => $data['capacidad'] ?: null,
            'ubicacion' => $data['ubicacion'] ?: null,
            'imagen' => $data['imagen'] ?: null,
            'disponibilidad' => $data['disponibilidad'] ?? 1,
            'estado' => $data['estado'] ?? 'activo',
            'id' => $id,
            'proveedor_id' => $proveedorId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function disable($id, $proveedorId)
    {
        $stmt = self::db()->prepare("UPDATE servicios SET estado = 'inactivo' WHERE id = :id AND proveedor_id = :proveedor_id");
        $stmt->execute(['id' => $id, 'proveedor_id' => $proveedorId]);
        return $stmt->rowCount() > 0;
    }

    public static function allForAdmin()
    {
        $sql = "SELECT s.*, c.nombre AS categoria, u.nombre AS proveedor
                FROM servicios s
                INNER JOIN proveedores p ON s.proveedor_id = p.id
                INNER JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN categorias_servicio c ON s.categoria_id = c.id
                ORDER BY s.created_at DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM servicios');
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }
}
