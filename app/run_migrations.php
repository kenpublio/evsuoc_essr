<?php

require_once __DIR__ . '/database.php';

function record_migration(PDO $pdo, string $migrationName): void
{
    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
    $stmt->execute(['migration' => $migrationName]);
}

function migration_already_run(PDO $pdo, string $migrationName): bool
{
    $stmt = $pdo->prepare('SELECT migration FROM schema_migrations WHERE migration = :migration LIMIT 1');
    $stmt->execute(['migration' => $migrationName]);
    return (bool) $stmt->fetch();
}

$pdo = db();

// Create schema_migrations table if it doesn't exist
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration varchar(255) NOT NULL PRIMARY KEY,
        migrated_at datetime NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
);

// Path to your SQL file
$sqlFile = dirname(__DIR__) . '/evsu_evaluation.sql';

if (!is_file($sqlFile)) {
    echo "❌ evsu_evaluation.sql not found at: $sqlFile\n";
    exit(1);
}

$migrationName = 'evsu_evaluation.sql';

// Check if already migrated
if (migration_already_run($pdo, $migrationName)) {
    echo "✅ Database already migrated. Skipping...\n";
    exit(0);
}

// Read and execute the SQL file
echo "📦 Importing evsu_evaluation.sql...\n";

$sql = file_get_contents($sqlFile);

if ($sql === false || trim($sql) === '') {
    echo "❌ Failed to read SQL file or file is empty\n";
    exit(1);
}

try {
    // Disable foreign key checks for smooth import
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Execute the SQL
    $pdo->exec($sql);
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Record the migration
    record_migration($pdo, $migrationName);
    
    echo "✅ Database imported successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error importing database: " . $e->getMessage() . "\n";
    exit(1);
}