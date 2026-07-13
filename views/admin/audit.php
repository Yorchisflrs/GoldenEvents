<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');
$page = max(1, (int) ($_GET['page'] ?? 1));
$audit = AdminController::audits($page, 20);
$pages = max(1, (int) ceil($audit['total'] / $audit['per_page']));
$pageTitle = 'Auditoría administrativa';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Auditoría administrativa</h1><p class="page-subtitle">Registro paginado de moderaciones y cambios de estado.</p>
<div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Administrador</th><th>Acción</th><th>Entidad</th><th>Detalles</th><th>IP</th></tr></thead><tbody>
<?php foreach ($audit['items'] as $item): ?><tr><td><?php echo htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['administrador_nombre'] ?? 'Cliente / sistema', ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($item['administrador_email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['accion'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['entidad'], ENT_QUOTES, 'UTF-8'); ?> #<?php echo (int) $item['entidad_id']; ?></td><td><small><?php echo htmlspecialchars($item['detalles'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></small></td><td><?php echo htmlspecialchars($item['direccion_ip'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?>
<?php if (!$audit['items']): ?><tr><td colspan="6">Todavía no hay acciones administrativas registradas.</td></tr><?php endif; ?>
</tbody></table></div><nav aria-label="Paginación de auditoría"><?php if ($page > 1): ?><a class="btn btn-outline" href="?page=<?php echo $page - 1; ?>">Anterior</a><?php endif; ?> <span>Página <?php echo $page; ?> de <?php echo $pages; ?></span> <?php if ($page < $pages): ?><a class="btn btn-outline" href="?page=<?php echo $page + 1; ?>">Siguiente</a><?php endif; ?></nav>
</section></main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
