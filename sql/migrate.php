<?php
// Ejecutor CLI de migraciones ordenadas y registradas para MariaDB.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Pagina no disponible.');
}

require_once __DIR__ . '/../config/database.php';

function normalizedMigrationChecksum($sql)
{
    return hash('sha256', str_replace(["\r\n", "\r"], "\n", $sql));
}

function migrationStatements($sql)
{
    $parts = preg_split('/^\s*--\s*statement-break\s*$/mi', $sql);
    return array_values(array_filter(array_map('trim', $parts), fn($part) => $part !== ''));
}

function ensureMigrationTable(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(255) NOT NULL PRIMARY KEY,
        checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
}

function appliedMigrations(PDO $pdo)
{
    $rows = $pdo->query('SELECT migration, checksum FROM schema_migrations ORDER BY migration')->fetchAll();
    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['migration']] = $row['checksum'];
    }
    return $applied;
}

$arguments = array_slice($argv, 1);
$statusOnly = in_array('--status', $arguments, true);
$dryRun = in_array('--dry-run', $arguments, true);
$migrationFiles = glob(__DIR__ . '/migrations/*.sql');
sort($migrationFiles, SORT_STRING);

if (!$migrationFiles) {
    fwrite(STDERR, "No se encontraron migraciones.\n");
    exit(1);
}

$lockName = 'golden_hour_events_schema_migrations';
$lockStatement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
$lockStatement->execute(['lock_name' => $lockName]);
if ((int) $lockStatement->fetchColumn() !== 1) {
    fwrite(STDERR, "No se pudo obtener el bloqueo de migraciones.\n");
    exit(1);
}

try {
    ensureMigrationTable($pdo);
    $applied = appliedMigrations($pdo);
    $pending = 0;

    foreach ($migrationFiles as $file) {
        $name = basename($file);
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('No se pudo leer la migracion ' . $name);
        }

        $checksum = normalizedMigrationChecksum($sql);
        if (isset($applied[$name])) {
            if (!hash_equals($applied[$name], $checksum)) {
                throw new RuntimeException('El checksum cambio para una migracion aplicada: ' . $name);
            }
            echo "[aplicada] {$name}\n";
            continue;
        }

        $pending++;
        if ($statusOnly || $dryRun) {
            echo "[pendiente] {$name}\n";
            continue;
        }

        foreach (migrationStatements($sql) as $statement) {
            $pdo->exec($statement);
        }

        $record = $pdo->prepare('INSERT INTO schema_migrations (migration, checksum) VALUES (:migration, :checksum)');
        $record->execute(['migration' => $name, 'checksum' => $checksum]);
        echo "[aplicada ahora] {$name}\n";
    }

    if ($statusOnly || $dryRun) {
        echo "Pendientes: {$pending}\n";
    } else {
        echo "Migraciones nuevas aplicadas: {$pending}\n";
    }
} catch (Throwable $exception) {
    error_log('[GoldenHourEvents][Migration] ' . $exception->getMessage());
    fwrite(STDERR, "La migracion no pudo completarse. Revisa el registro tecnico.\n");
    exit(1);
} finally {
    $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
    $release->execute(['lock_name' => $lockName]);
}
