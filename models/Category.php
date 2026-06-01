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

    public static function findById($id)
    {
        $stmt = self::db()->prepare('SELECT * FROM categorias_servicio WHERE id = :id LIMIT 1');
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
