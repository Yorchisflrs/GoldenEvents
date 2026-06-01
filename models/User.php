<?php
// Modelo de usuarios con consultas PDO preparadas.

require_once __DIR__ . '/../config/database.php';

class User
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function findByEmail($email)
    {
        $sql = "SELECT u.*, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.email = :email
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $sql = "SELECT u.*, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.id = :id
                LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function create($nombre, $email, $password, $telefono, $rolNombre)
    {
        if ($rolNombre === 'admin') {
            return false;
        }

        $stmt = self::db()->prepare('SELECT id FROM roles WHERE nombre = :nombre LIMIT 1');
        $stmt->execute(['nombre' => $rolNombre]);
        $rol = $stmt->fetch();

        if (!$rol) {
            return false;
        }

        $sql = "INSERT INTO usuarios (rol_id, nombre, email, password, telefono, idioma, estado)
                VALUES (:rol_id, :nombre, :email, :password, :telefono, 'es', 'activo')";
        $stmt = self::db()->prepare($sql);

        return $stmt->execute([
            'rol_id' => $rol['id'],
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'telefono' => $telefono,
        ]);
    }

    public static function all()
    {
        $sql = "SELECT u.id, u.nombre, u.email, u.telefono, u.idioma, u.estado, u.created_at, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                ORDER BY u.created_at DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function emailExists($email)
    {
        $stmt = self::db()->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    public static function updateStatus($id, $estado)
    {
        $allowed = ['activo', 'inactivo', 'bloqueado'];
        if (!in_array($estado, $allowed, true)) {
            return false;
        }

        $stmt = self::db()->prepare('UPDATE usuarios SET estado = :estado WHERE id = :id');
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM usuarios');
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) $row['total'];
    }
}
