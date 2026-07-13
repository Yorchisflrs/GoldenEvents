<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$message = '';
$messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $result = AdminController::moderateUser(
        (int) ($_POST['user_id'] ?? 0),
        trim((string) ($_POST['action'] ?? '')),
        trim((string) ($_POST['motivo'] ?? '')),
        (int) currentUser()['id']
    );
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

$roleFilter = trim((string) ($_GET['rol'] ?? ''));
$stateFilter = trim((string) ($_GET['estado'] ?? ''));
$users = AdminController::getUsers(['rol' => $roleFilter, 'estado' => $stateFilter]);
$pageTitle = 'Gestión de usuarios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container">
<section class="section">
    <h1 class="page-title">Gestión de usuarios</h1>
    <p class="page-subtitle">Aprueba, rechaza, bloquea o reactiva cuentas mediante transiciones controladas.</p>
    <?php if ($message !== ''): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <form class="form-container" method="GET" action="/GoldenHoursEvents/views/admin/users.php">
        <div class="grid-2">
            <div class="form-group"><label for="rol">Rol</label><select id="rol" name="rol">
                <option value="">Todos</option>
                <?php foreach (['admin', 'cliente', 'organizador', 'proveedor'] as $role): ?><option value="<?php echo $role; ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
            </select></div>
            <div class="form-group"><label for="estado">Estado</label><select id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (['pendiente', 'activo', 'inactivo', 'bloqueado', 'rechazado'] as $state): ?><option value="<?php echo $state; ?>" <?php echo $stateFilter === $state ? 'selected' : ''; ?>><?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
            </select></div>
        </div><button class="btn btn-outline" type="submit">Filtrar</button>
    </form>

    <div class="table-wrapper"><table><thead><tr><th>Usuario</th><th>Contacto</th><th>Rol/estado</th><th>Aprobación</th><th>Motivo</th><th>Acción</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?><tr>
        <td>#<?php echo (int) $user['id']; ?> — <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></small></td>
        <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($user['telefono'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="badge badge-info"><?php echo htmlspecialchars($user['rol'], ENT_QUOTES, 'UTF-8'); ?></span><br><span class="badge badge-<?php echo htmlspecialchars($user['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><?php echo htmlspecialchars($user['aprobador_nombre'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($user['aprobado_en'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></small></td>
        <td><?php echo htmlspecialchars($user['motivo_rechazo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <form method="POST" action="/GoldenHoursEvents/views/admin/users.php?rol=<?php echo urlencode($roleFilter); ?>&amp;estado=<?php echo urlencode($stateFilter); ?>">
                <?php echo csrfField(); ?><input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                <select name="action" required><option value="">Seleccionar</option>
                    <?php if ($user['estado'] === 'pendiente'): ?><option value="approve">Aprobar</option><option value="reject">Rechazar</option><?php endif; ?>
                    <?php if ($user['estado'] === 'activo'): ?><option value="block">Bloquear</option><?php endif; ?>
                    <?php if (in_array($user['estado'], ['bloqueado', 'inactivo', 'rechazado'], true)): ?><option value="reactivate">Reactivar</option><?php endif; ?>
                </select>
                <input type="text" name="motivo" maxlength="500" placeholder="Motivo (obligatorio al rechazar)">
                <button class="btn btn-primary" type="submit">Aplicar</button>
            </form>
        </td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
