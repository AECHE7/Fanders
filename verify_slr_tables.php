<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

echo "🔍 Checking SLR tables...\n";
$db = Database::getInstance();
$connection = $db->getConnection();

$tables = ["slr_documents", "slr_generation_rules", "slr_access_log"];

foreach ($tables as $table) {
    $result = $connection->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table' ORDER BY ordinal_position");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Table: $table\n";
    if (empty($columns)) {
        echo "  ❌ Table not found!\n";
    } else {
        foreach ($columns as $col) {
            echo "  ✅ " . $col["column_name"] . " (" . $col["data_type"] . ")\n";
        }
    }
}

// Check generation rules data
echo "\n🔧 Generation Rules:\n";
$rulesResult = $connection->query("SELECT rule_name, trigger_event, auto_generate FROM slr_generation_rules");
$rules = $rulesResult->fetchAll(PDO::FETCH_ASSOC);
foreach ($rules as $rule) {
    $autoStatus = $rule["auto_generate"] ? "AUTO" : "MANUAL";
    echo "  📋 " . $rule["rule_name"] . " (" . $rule["trigger_event"] . ") - $autoStatus\n";
}

echo "\n✅ SLR System verification complete!\n";
?>