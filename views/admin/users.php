<?php
// Gestion de usuarios para administrador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$users = AdminController::getUsers();
$pageTitle = 'Gestion de usuarios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Gestion de usuarios</h1>
        <p class="page-subtitle">Usuarios registrados y sus roles dentro del sistema.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo (int) $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($user['rol'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($user['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
