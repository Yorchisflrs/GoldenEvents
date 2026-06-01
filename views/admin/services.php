<?php
// Servicios registrados para administracion.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$services = AdminController::getServices();
$pageTitle = 'Servicios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Servicios</h1>
        <p class="page-subtitle">Catalogo completo de servicios registrados por proveedores.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Categoria</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Capacidad</th>
                        <th>Ubicacion</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['proveedor'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoria', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>S/ <?php echo number_format((float) $service['precio'], 2); ?></td>
                            <td><?php echo htmlspecialchars($service['capacidad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($service['ubicacion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
