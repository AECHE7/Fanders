<?php
/**
 * Simple DB connectivity test for the Fanders project.
 *
 * Usage examples:
 *  - From host with PHP installed:
 *      php tools/test_db.php
 *
 *  - Inside docker-compose PHP container (recommended if your app runs there):
 *      docker-compose exec php php tools/test_db.php
 *
 * The script will use the existing `app/config/config.php` environment parsing
 * so you can either export DATABASE_URL or set DB_HOST/DB_USER/DB_PASS in env or in .env.
 */

chdir(__DIR__ . '/..');

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "Testing DB connection using configured environment...\n";

try {
    // Instantiate Database singleton and get PDO
    $dbInstance = Database::getInstance();
    $pdo = $dbInstance->getConnection();

    echo "Connected to DB successfully. Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";

    // Simple query to list public tables
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE' ORDER BY table_name";
    $stmt = $pdo->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($tables === false) {
        echo "No tables found or query failed.\n";
    } else {
        echo "Tables in public schema (first 50):\n";
        foreach (array_slice($tables, 0, 50) as $t) {
            echo " - $t\n";
        }
    }

    // Show users table description if present
    if (in_array('users', $tables, true)) {
        echo "\n\nSchema for 'users':\n";
        $desc = $pdo->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='users' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($desc as $col) {
            echo sprintf("%s — %s (%s)\n", $col['column_name'], $col['data_type'], $col['is_nullable']);
        }
    }

    echo "\nDB test completed.\n";
    exit(0);

} catch (Exception $e) {
    // Database::getInstance() may have already printed/died on error; still catch here.
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(2);
}
