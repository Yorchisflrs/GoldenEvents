<?php
// Modelo para categorias de servicios.

require_once __DIR__ . '/../config/database.php';

class Category
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function allActive()
    {
        $stmt = self::db()->prepare("SELECT * FROM categorias_servicio WHERE estado = 'activo' ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function allPublicAvailable()
    {
        $sql = "SELECT DISTINCT c.*
                FROM categorias_servicio c
                INNER JOIN servicios s ON s.categoria_id = c.id
                INNER JOIN proveedores p ON p.id = s.proveedor_id
                INNER JOIN usuarios u ON u.id = p.usuario_id
                WHERE c.estado = 'activo'
                  AND s.estado = 'activo'
                  AND s.disponibilidad = 1
                  AND p.estado = 'activo'
                  AND u.estado = 'activo'
                ORDER BY c.nombre ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $stmt = self::db()->prepare('SELECT * FROM categorias_servicio WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function findActiveById($id)
    {
        $stmt = self::db()->prepare("SELECT * FROM categorias_servicio WHERE id = :id AND estado = 'activo' LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function findBySlug($slug)
    {
        $stmt = self::db()->prepare('SELECT * FROM categorias_servicio WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch();
    }
}
