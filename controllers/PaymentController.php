<?php
// Registro de comprobantes y validación administrativa de pagos Yape.

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../includes/payment_proof.php';

class PaymentController
{
    public static function normalizeOperationCode($value)
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
    }

    public static function registerPayment($userId, $reservationId, $post, $files)
    {
        $reservation = Reservation::findOwnedById($reservationId, $userId);
        if (!$reservation) return ['success' => false, 'message' => 'La reserva no existe o no te pertenece.'];
        if ($reservation['estado'] !== 'pendiente_pago' || empty($reservation['plazo_vigente'])) {
            return ['success' => false, 'message' => 'La reserva ya no admite pagos.'];
        }
        if (!appConfig('payments.yape_enabled', true)) return ['success' => false, 'message' => 'Yape no está disponible temporalmente.'];
        $totalCents = moneyToCents($reservation['monto_total']);
        $maxCents = moneyToCents(appConfig('payments.yape_max_amount'));
        if ($totalCents === null || $maxCents === null || $totalCents > $maxCents) return ['success' => false, 'message' => 'El monto no puede procesarse mediante Yape.'];
        if (!empty($reservation['pago_id'])) return ['success' => false, 'message' => 'La reserva ya tiene un pago registrado.'];
        $operationCode = self::normalizeOperationCode($post['codigo_operacion'] ?? '');
        if (!preg_match('/^[A-Z0-9-]{6,60}$/', $operationCode)) return ['success' => false, 'message' => 'Ingresa un código de operación válido de 6 a 60 caracteres.'];
        if (Payment::operationCodeExists($operationCode)) return ['success' => false, 'message' => 'El código de operación ya fue registrado.'];
        $proof = savePrivatePaymentProof($files['comprobante'] ?? null);
        if (!$proof['success']) return $proof;
        $result = Payment::registerForReservation($userId, $reservationId, $operationCode, $proof['path']);
        if (!$result['success']) deletePrivatePaymentProof($proof['path']);
        return $result;
    }

    public static function getByReservation($reservationId) { return Payment::findByReservation($reservationId); }
    public static function adminPayments($filters = []) { return Payment::all($filters); }
    public static function adminTransition($paymentId, $action, $reason, $adminId) { return Payment::adminTransition($paymentId, $action, $reason, $adminId); }
}
