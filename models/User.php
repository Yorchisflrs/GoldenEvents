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
        $allowedRoles = ['cliente', 'organizador', 'proveedor'];
        if (!in_array($rolNombre, $allowedRoles, true)) {
            return false;
        }

        $stmt = self::db()->prepare('SELECT id FROM roles WHERE nombre = :nombre LIMIT 1');
        $stmt->execute(['nombre' => $rolNombre]);
        $rol = $stmt->fetch();

        if (!$rol) {
            return false;
        }

        $status = $rolNombre === 'cliente' ? 'activo' : 'pendiente';
        $sql = "INSERT INTO usuarios (rol_id, nombre, email, password, telefono, idioma, estado)
                VALUES (:rol_id, :nombre, :email, :password, :telefono, 'es', :estado)";
        $stmt = self::db()->prepare($sql);

        return $stmt->execute([
            'rol_id' => $rol['id'],
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'telefono' => $telefono,
            'estado' => $status,
        ]);
    }

    public static function all($filters = [])
    {
        $sql = "SELECT u.id, u.nombre, u.email, u.telefono, u.idioma, u.estado,
                       u.aprobado_por, u.aprobado_en, u.motivo_rechazo, u.created_at,
                       r.nombre AS rol, aprobador.nombre AS aprobador_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                LEFT JOIN usuarios aprobador ON aprobador.id = u.aprobado_por
                WHERE 1 = 1";
        $params = [];

        $allowedRoles = ['admin', 'cliente', 'organizador', 'proveedor'];
        $allowedStates = ['pendiente', 'activo', 'inactivo', 'bloqueado', 'rechazado'];
        if (isset($filters['rol']) && in_array($filters['rol'], $allowedRoles, true)) {
            $sql .= ' AND r.nombre = :rol';
            $params['rol'] = $filters['rol'];
        }
        if (isset($filters['estado']) && in_array($filters['estado'], $allowedStates, true)) {
            $sql .= ' AND u.estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        $sql .= ' ORDER BY u.created_at DESC, u.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function emailExists($email)
    {
        $stmt = self::db()->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    public static function moderate($id, $status, $adminId, $reason = null, $approved = false)
    {
        $allowed = ['pendiente', 'activo', 'inactivo', 'bloqueado', 'rechazado'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        if ($approved) {
            $sql = 'UPDATE usuarios SET estado = :estado, motivo_rechazo = NULL,
                    aprobado_por = :aprobado_por, aprobado_en = :aprobado_en WHERE id = :id';
            $params = ['estado' => $status, 'aprobado_por' => (int) $adminId, 'aprobado_en' => date('Y-m-d H:i:s'), 'id' => (int) $id];
        } elseif ($status === 'rechazado') {
            $sql = 'UPDATE usuarios SET estado = :estado, motivo_rechazo = :motivo,
                    aprobado_por = NULL, aprobado_en = NULL WHERE id = :id';
            $params = ['estado' => $status, 'motivo' => $reason ?: null, 'id' => (int) $id];
        } else {
            $sql = 'UPDATE usuarios SET estado = :estado, motivo_rechazo = :motivo WHERE id = :id';
            $params = ['estado' => $status, 'motivo' => $reason ?: null, 'id' => (int) $id];
        }
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function countActiveAdmins()
    {
        $sql = "SELECT COUNT(*) AS total
                FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
                WHERE r.nombre = 'admin' AND u.estado = 'activo'";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    public static function countByStatus($status)
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM usuarios WHERE estado = :estado');
        $stmt->execute(['estado' => $status]);
        return (int) $stmt->fetch()['total'];
    }

    public static function countPendingByRole($role)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.estado = 'pendiente' AND r.nombre = :rol";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['rol' => $role]);
        return (int) $stmt->fetch()['total'];
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM usuarios');
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) $row['total'];
    }
}
