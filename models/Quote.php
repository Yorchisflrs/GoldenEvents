<?php
// Modelo de cotizaciones con calculo de total en servidor.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Quote
{
    private static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function createWithDetails($data, $selectedServices)
    {
        $db = self::db();
        $publicToken = null;
        $publicTokenHash = null;

        if (empty($data['usuario_id'])) {
            $publicToken = bin2hex(random_bytes(32));
            $publicTokenHash = hash('sha256', $publicToken);
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO cotizaciones
                (usuario_id, public_token_hash, nombre_cliente, telefono_cliente, email_cliente, tipo_evento, fecha_evento, cantidad_invitados, total_estimado, mensaje, estado)
                VALUES
                (:usuario_id, :public_token_hash, :nombre_cliente, :telefono_cliente, :email_cliente, :tipo_evento, :fecha_evento, :cantidad_invitados, 0.00, :mensaje, 'pendiente')");
            $stmt->execute([
                'usuario_id' => $data['usuario_id'],
                'public_token_hash' => $publicTokenHash,
                'nombre_cliente' => $data['nombre_cliente'],
                'telefono_cliente' => $data['telefono_cliente'],
                'email_cliente' => $data['email_cliente'] ?: null,
                'tipo_evento' => $data['tipo_evento'],
                'fecha_evento' => $data['fecha_evento'] ?: null,
                'cantidad_invitados' => $data['cantidad_invitados'],
                'mensaje' => $data['mensaje'] ?: null,
            ]);

            $quoteId = (int) $db->lastInsertId();
            $totalCents = 0;
            $detailSql = "INSERT INTO cotizacion_detalles
                (cotizacion_id, servicio_id, categoria_nombre, servicio_nombre, precio_unitario, cantidad, subtotal)
                VALUES
                (:cotizacion_id, :servicio_id, :categoria_nombre, :servicio_nombre, :precio_unitario, :cantidad, :subtotal)";
            $detailStmt = $db->prepare($detailSql);
            $serviceStmt = $db->prepare("SELECT s.id, s.nombre, s.precio, c.nombre AS categoria
                                         FROM servicios s
                                         INNER JOIN proveedores p ON p.id = s.proveedor_id
                                         INNER JOIN usuarios u ON u.id = p.usuario_id
                                         INNER JOIN categorias_servicio c ON s.categoria_id = c.id
                                         WHERE s.id = :id AND s.estado = 'activo' AND s.disponibilidad = 1
                                           AND p.estado = 'activo' AND u.estado = 'activo' AND c.estado = 'activo'
                                         LIMIT 1");

            foreach ($selectedServices as $serviceId => $quantity) {
                $serviceStmt->execute(['id' => (int) $serviceId]);
                $service = $serviceStmt->fetch();

                if (!$service) {
                    continue;
                }

                $quantity = max(1, (int) $quantity);
                $priceCents = moneyToCents($service['precio']);
                $subtotalCents = $priceCents * $quantity;
                $totalCents += $subtotalCents;

                $detailStmt->execute([
                    'cotizacion_id' => $quoteId,
                    'servicio_id' => $service['id'],
                    'categoria_nombre' => $service['categoria'] ?? 'Sin categoria',
                    'servicio_nombre' => $service['nombre'],
                    'precio_unitario' => centsToMoney($priceCents),
                    'cantidad' => $quantity,
                    'subtotal' => centsToMoney($subtotalCents),
                ]);
            }

            if ($totalCents <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selecciona al menos un servicio valido.'];
            }

            $total = centsToMoney($totalCents);
            $stmt = $db->prepare('UPDATE cotizaciones SET total_estimado = :total WHERE id = :id');
            $stmt->execute(['total' => $total, 'id' => $quoteId]);

            $db->commit();
            return [
                'success' => true,
                'quote_id' => $quoteId,
                'total' => $total,
                'public_token' => $publicToken,
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('[GoldenHourEvents] Error al crear cotizacion: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo registrar la cotizacion.'];
        }
    }

    public static function findAccessibleById($id, $userId, $isAdmin = false)
    {
        $sql = "SELECT c.*, u.email AS usuario_email
                FROM cotizaciones c
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                WHERE c.id = :id";
        $params = ['id' => $id];

        if (!$isAdmin) {
            $sql .= ' AND c.usuario_id = :usuario_id';
            $params['usuario_id'] = $userId;
        }

        $sql .= ' LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public static function findByPublicToken($token)
    {
        if (!is_string($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
            return false;
        }

        $stmt = self::db()->prepare("SELECT c.*, u.email AS usuario_email
                                     FROM cotizaciones c
                                     LEFT JOIN usuarios u ON c.usuario_id = u.id
                                     WHERE c.public_token_hash = :token_hash AND c.usuario_id IS NULL
                                     LIMIT 1");
        $stmt->execute(['token_hash' => hash('sha256', strtolower($token))]);
        return $stmt->fetch();
    }

    public static function details($quoteId)
    {
        $stmt = self::db()->prepare('SELECT * FROM cotizacion_detalles WHERE cotizacion_id = :cotizacion_id ORDER BY id ASC');
        $stmt->execute(['cotizacion_id' => $quoteId]);
        return $stmt->fetchAll();
    }

    public static function byUser($userId)
    {
        $stmt = self::db()->prepare('SELECT * FROM cotizaciones WHERE usuario_id = :usuario_id ORDER BY created_at DESC');
        $stmt->execute(['usuario_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function all()
    {
        $sql = "SELECT c.*, u.email AS usuario_email
                FROM cotizaciones c
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                ORDER BY c.created_at DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateStatus($id, $estado)
    {
        $allowed = ['pendiente', 'contactado', 'aprobado', 'rechazado', 'cancelado'];
        if (!in_array($estado, $allowed, true)) {
            return false;
        }

        $stmt = self::db()->prepare('UPDATE cotizaciones SET estado = :estado WHERE id = :id');
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public static function countAll()
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM cotizaciones');
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    public static function countPending()
    {
        $stmt = self::db()->prepare("SELECT COUNT(*) AS total FROM cotizaciones WHERE estado = 'pendiente'");
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }
}
