<?php
// Servicios registrados por el proveedor.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
requireLogin();
requireRole('proveedor');

$services = ServiceController::myServices(currentUser()['id']);

$pageTitle = 'Mis servicios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Mis servicios</h1>
        <p class="page-subtitle">Gestiona la informacion que los clientes veran en el catalogo.</p>
        <p><a class="btn btn-primary" href="/GoldenHoursEvents/views/provider/create_service.php">Crear servicio</a></p>

        <?php if (empty($services)): ?>
            <div class="empty-state">
                <h2>📦 Todavia no registraste servicios.</h2>
                <p>Publica tu primer servicio para aparecer en el catalogo.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Categoria</th>
                            <th>Nombre</th>
                            <th>Descripcion</th>
                            <th>Precio</th>
                            <th>Capacidad</th>
                            <th>Ubicacion</th>
                            <th>Disponibilidad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($service['imagen'])): ?>
                                        <img class="thumb" src="/GoldenHoursEvents/<?php echo htmlspecialchars($service['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                    <?php else: ?>
                                        <span class="badge badge-info">Sin imagen</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoria', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($service['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>S/ <?php echo number_format((float) $service['precio'], 2); ?></td>
                                <td><?php echo htmlspecialchars($service['capacidad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($service['ubicacion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo ((int) $service['disponibilidad'] === 1) ? 'Disponible' : 'No disponible'; ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
