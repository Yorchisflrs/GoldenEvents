<?php
// Controlador de administración, moderaciones y auditoría.

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Quote.php';
require_once __DIR__ . '/../models/AdminAudit.php';

class AdminController
{
    private static function transaction($callback)
    {
        global $pdo;
        $ownsTransaction = !$pdo->inTransaction();
        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }
            $result = $callback();
            if (empty($result['success'])) {
                if ($ownsTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return $result;
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[GoldenHourEvents] Error administrativo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo completar la operación administrativa.'];
        }
    }

    public static function dashboardStats()
    {
        return [
            'usuarios_pendientes' => User::countByStatus('pendiente'),
            'organizadores_pendientes' => User::countPendingByRole('organizador'),
            'proveedores_pendientes' => User::countPendingByRole('proveedor'),
            'servicios_pendientes' => Service::countByStatus('pendiente'),
            'eventos_pendientes' => Event::countByStatus('pendiente_aprobacion'),
            'usuarios' => User::countAll(),
            'servicios' => Service::countAll(),
            'eventos_publicados' => Event::countByStatus('publicado'),
            'cotizaciones' => Quote::countAll(),
            'cotizaciones_pendientes' => Quote::countPending(),
        ];
    }

    public static function getUsers($filters = [])
    {
        return User::all($filters);
    }

    public static function moderateUser($targetId, $action, $reason, $adminId)
    {
        return self::transaction(function () use ($targetId, $action, $reason, $adminId) {
            $user = User::findById($targetId);
            if (!$user) {
                return ['success' => false, 'message' => 'El usuario no existe.'];
            }
            $reason = trim((string) $reason);
            $transitions = [
                'approve' => ['from' => ['pendiente'], 'to' => 'activo', 'approved' => true],
                'reject' => ['from' => ['pendiente'], 'to' => 'rechazado', 'approved' => false],
                'block' => ['from' => ['activo'], 'to' => 'bloqueado', 'approved' => false],
                'reactivate' => ['from' => ['bloqueado', 'inactivo', 'rechazado'], 'to' => 'activo', 'approved' => true],
            ];
            if (!isset($transitions[$action]) || !in_array($user['estado'], $transitions[$action]['from'], true)) {
                return ['success' => false, 'message' => 'La transición de estado no es válida.'];
            }
            if ($action === 'reject' && $reason === '') {
                return ['success' => false, 'message' => 'El motivo de rechazo es obligatorio.'];
            }
            if ($action === 'block' && (int) $targetId === (int) $adminId) {
                return ['success' => false, 'message' => 'No puedes bloquear tu propia cuenta.'];
            }
            if ($action === 'block' && $user['rol'] === 'admin' && User::countActiveAdmins() <= 1) {
                return ['success' => false, 'message' => 'No se puede bloquear al último administrador activo.'];
            }

            $transition = $transitions[$action];
            $stateReason = in_array($action, ['reject', 'block'], true) ? ($reason ?: 'Cuenta bloqueada por administración.') : null;
            if (!User::moderate($targetId, $transition['to'], $adminId, $stateReason, $transition['approved'])) {
                return ['success' => false, 'message' => 'No se pudo actualizar el usuario.'];
            }
            if (!AdminAudit::record($adminId, 'usuario_' . $action, 'usuario', $targetId, $user['estado'], $transition['to'], ['rol' => $user['rol']])) {
                throw new RuntimeException('No se pudo registrar la auditoría del usuario.');
            }
            return ['success' => true, 'message' => 'Estado del usuario actualizado correctamente.'];
        });
    }

    public static function getEvents($filters = [])
    {
        return Event::all($filters);
    }

    public static function moderateEvent($eventId, $action, $reason, $adminId)
    {
        return self::transaction(function () use ($eventId, $action, $reason, $adminId) {
            $event = Event::findById($eventId);
            if (!$event) {
                return ['success' => false, 'message' => 'El evento no existe.'];
            }
            $reason = trim((string) $reason);
            $transitions = [
                'approve' => ['from' => ['pendiente_aprobacion'], 'to' => 'publicado'],
                'reject' => ['from' => ['pendiente_aprobacion'], 'to' => 'rechazado'],
                'cancel' => ['from' => ['borrador', 'pendiente_aprobacion', 'publicado', 'rechazado', 'inactivo'], 'to' => 'cancelado'],
                'disable' => ['from' => ['publicado'], 'to' => 'inactivo'],
                'review' => ['from' => ['borrador', 'rechazado', 'inactivo'], 'to' => 'pendiente_aprobacion'],
            ];
            if (!isset($transitions[$action]) || !in_array($event['estado'], $transitions[$action]['from'], true)) {
                return ['success' => false, 'message' => 'La transición del evento no es válida.'];
            }
            if ($action === 'approve' && strtotime($event['fecha_inicio']) <= time()) {
                return ['success' => false, 'message' => 'No se puede publicar un evento cuya fecha de inicio ya pasó.'];
            }
            if ($action === 'review' && strtotime($event['fecha_inicio']) <= time()) {
                return ['success' => false, 'message' => 'No se puede devolver a revisión un evento pasado.'];
            }
            if ($action === 'reject' && $reason === '') {
                return ['success' => false, 'message' => 'El motivo de rechazo es obligatorio.'];
            }
            $next = $transitions[$action]['to'];
            if (!Event::moderate($eventId, $next, $adminId, $action === 'reject' ? $reason : null)) {
                return ['success' => false, 'message' => 'No se pudo actualizar el evento.'];
            }
            if (!AdminAudit::record($adminId, 'evento_' . $action, 'evento', $eventId, $event['estado'], $next, ['organizador_id' => (int) $event['organizador_id']])) {
                throw new RuntimeException('No se pudo registrar la auditoría del evento.');
            }
            return ['success' => true, 'message' => 'Estado del evento actualizado correctamente.'];
        });
    }

    public static function getReservations()
    {
        return Reservation::all();
    }

    public static function getPayments()
    {
        return Payment::all();
    }

    public static function getServices($filters = [])
    {
        return Service::allForAdmin($filters);
    }

    public static function moderateService($serviceId, $action, $reason, $adminId)
    {
        return self::transaction(function () use ($serviceId, $action, $reason, $adminId) {
            $service = Service::findById($serviceId);
            if (!$service) {
                return ['success' => false, 'message' => 'El servicio no existe.'];
            }
            $reason = trim((string) $reason);
            $transitions = [
                'approve' => ['from' => ['pendiente'], 'to' => 'activo'],
                'reject' => ['from' => ['pendiente'], 'to' => 'rechazado'],
                'disable' => ['from' => ['activo'], 'to' => 'inactivo'],
                'reactivate' => ['from' => ['inactivo'], 'to' => 'activo'],
                'review' => ['from' => ['rechazado', 'inactivo'], 'to' => 'pendiente'],
            ];
            if (!isset($transitions[$action]) || !in_array($service['estado'], $transitions[$action]['from'], true)) {
                return ['success' => false, 'message' => 'La transición del servicio no es válida.'];
            }
            if ($action === 'reject' && $reason === '') {
                return ['success' => false, 'message' => 'El motivo de rechazo es obligatorio.'];
            }
            $next = $transitions[$action]['to'];
            if (!Service::moderate($serviceId, $next, $adminId, $action === 'reject' ? $reason : null)) {
                return ['success' => false, 'message' => 'No se pudo actualizar el servicio.'];
            }
            if (!AdminAudit::record($adminId, 'servicio_' . $action, 'servicio', $serviceId, $service['estado'], $next, ['proveedor_id' => (int) $service['proveedor_id']])) {
                throw new RuntimeException('No se pudo registrar la auditoría del servicio.');
            }
            return ['success' => true, 'message' => 'Estado del servicio actualizado correctamente.'];
        });
    }

    public static function getQuotes()
    {
        return Quote::all();
    }

    public static function changeQuoteStatus($quoteId, $status, $adminId)
    {
        return self::transaction(function () use ($quoteId, $status, $adminId) {
            $quote = Quote::findAccessibleById($quoteId, $adminId, true);
            if (!$quote) {
                return ['success' => false, 'message' => 'La cotización no existe.'];
            }
            $transitions = [
                'pendiente' => ['contactado', 'rechazado', 'cancelado'],
                'contactado' => ['aprobado', 'rechazado', 'cancelado'],
                'aprobado' => ['cancelado'],
                'rechazado' => [],
                'cancelado' => [],
            ];
            if (!isset($transitions[$quote['estado']]) || !in_array($status, $transitions[$quote['estado']], true)) {
                return ['success' => false, 'message' => 'La transición de la cotización no es válida.'];
            }
            if (!Quote::updateStatus($quoteId, $status)) {
                return ['success' => false, 'message' => 'No se pudo actualizar la cotización.'];
            }
            if (!AdminAudit::record($adminId, 'cotizacion_estado', 'cotizacion', $quoteId, $quote['estado'], $status)) {
                throw new RuntimeException('No se pudo registrar la auditoría de la cotización.');
            }
            return ['success' => true, 'message' => 'Estado de la cotización actualizado correctamente.'];
        });
    }

    public static function audits($page = 1, $perPage = 20)
    {
        return [
            'items' => AdminAudit::paginated($page, $perPage),
            'total' => AdminAudit::countAll(),
            'page' => max(1, (int) $page),
            'per_page' => max(1, min(100, (int) $perPage)),
        ];
    }
}
