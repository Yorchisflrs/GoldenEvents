<?php
require_once __DIR__ . '/config/database.php';

echo "<h1>Prueba de conexión - Golden Hour Events</h1>";

try {
    echo "<p style='color:green;'>Conexión exitosa a la base de datos.</p>";

    $stmt = $pdo->query("SELECT DATABASE() AS database_name");
    $db = $stmt->fetch();

    echo "<p>Base de datos conectada: <strong>" . htmlspecialchars($db['database_name']) . "</strong></p>";

    $tables = $pdo->query("SHOW TABLES")->fetchAll();

    echo "<h2>Tablas encontradas:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars(array_values($table)[0]) . "</li>";
    }
    echo "</ul>";

    $services = $pdo->query("SELECT s.id, s.nombre, s.precio, c.nombre AS categoria
                             FROM servicios s
                             LEFT JOIN categorias_servicio c ON s.categoria_id = c.id
                             WHERE s.estado = 'activo'
                             LIMIT 10")->fetchAll();

    echo "<h2>Servicios disponibles:</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Categoria</th><th>Servicio</th><th>Precio</th></tr>";

    foreach ($services as $service) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($service['id']) . "</td>";
        echo "<td>" . htmlspecialchars($service['categoria'] ?? 'Sin categoria') . "</td>";
        echo "<td>" . htmlspecialchars($service['nombre']) . "</td>";
        echo "<td>S/ " . htmlspecialchars($service['precio']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
