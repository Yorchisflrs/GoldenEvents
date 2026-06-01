<?php
// Modelo de proveedores.

require_once __DIR__ . '/../config/database.php';

class Provider
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function findByUser($userId)
    {
        $stmt = self::db()->prepare('SELECT * FROM proveedores WHERE usuario_id = :usuario_id LIMIT 1');
        $stmt->execute(['usuario_id' => $userId]);
        return $stmt->fetch();
    }

    public static function createIfNotExists($userId, $tipoServicio, $descripcion)
    {
        $provider = self::findByUser($userId);
        if ($provider) {
            return $provider;
        }

        $stmt = self::db()->prepare("INSERT INTO proveedores (usuario_id, tipo_servicio, descripcion, estado)
                                     VALUES (:usuario_id, :tipo_servicio, :descripcion, 'activo')");
        $stmt->execute([
            'usuario_id' => $userId,
            'tipo_servicio' => $tipoServicio,
            'descripcion' => $descripcion,
        ]);

        return self::findByUser($userId);
    }
}
