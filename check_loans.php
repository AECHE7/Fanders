<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "Existing loans:\n";
$stmt = $conn->query("SELECT id, status, principal FROM loans LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Loan #{$row['id']} - {$row['status']} - ₱" . number_format($row['principal'], 2) . "\n";
}

echo "\nUpdating a loan to approved status for testing...\n";
$stmt = $conn->query("UPDATE loans SET status = 'approved' WHERE id = (SELECT id FROM loans LIMIT 1) RETURNING id");
$updated = $stmt->fetch(PDO::FETCH_ASSOC);
if ($updated) {
    echo "Updated loan #{$updated['id']} to approved status\n";
} else {
    echo "No loans to update\n";
}