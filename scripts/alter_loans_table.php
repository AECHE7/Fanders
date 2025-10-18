<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    echo "Checking loans table structure...\n";

    // Check if columns exist
    $columns = $pdo->query("DESCRIBE loans")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');

    $missingColumns = [];

    if (!in_array('approval_date', $columnNames)) {
        $missingColumns[] = 'approval_date';
    }
    if (!in_array('disbursement_date', $columnNames)) {
        $missingColumns[] = 'disbursement_date';
    }
    if (!in_array('completion_date', $columnNames)) {
        $missingColumns[] = 'completion_date';
    }
    if (!in_array('updated_at', $columnNames)) {
        $missingColumns[] = 'updated_at';
    }

    if (!empty($missingColumns)) {
        echo "Adding missing columns: " . implode(', ', $missingColumns) . "\n";

        foreach ($missingColumns as $column) {
            $sql = "ALTER TABLE loans ADD COLUMN {$column} TIMESTAMP NULL";
            $pdo->exec($sql);
            echo "Added column: {$column}\n";
        }

        echo "All missing columns added successfully.\n";
    } else {
        echo "All required columns are already present.\n";
    }

    // Verify final structure
    echo "\nFinal loans table structure:\n";
    $result = $pdo->query("DESCRIBE loans");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
