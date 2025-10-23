<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM collection_sheets');
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "collection_sheets count: " . ($row['cnt'] ?? '0') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
