<?php
// Endpoint: /public/agreements/list.php
// Lists all agreements generated after loan approval

require_once '../../public/init.php';

// Only allow staff roles
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer', 'cashier']);

$agreementsDir = BASE_PATH . '/storage/agreements';
$agreements = [];

if (is_dir($agreementsDir)) {
    $files = scandir($agreementsDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $agreementsDir . '/' . $file;
        if (is_file($filePath)) {
            $agreements[] = [
                'name' => $file,
                'url' => APP_URL . '/storage/agreements/' . urlencode($file)
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['agreements' => $agreements]);
