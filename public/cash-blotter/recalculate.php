<?php
/**
 * AJAX endpoint to recalculate cash blotter
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Check authentication and permissions
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Only admins can recalculate cash blotter
if (!in_array($_SESSION['role'], ['admin', 'super-admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

// Include required services
require_once '../../app/services/CashBlotterService.php';

header('Content-Type: application/json');

try {
    $cashBlotterService = new CashBlotterService();

    // Get the start date (earliest transaction date)
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT MIN(DATE(created_at)) as start_date FROM transactions WHERE transaction_type IN ('LOAN_DISBURSED', 'PAYMENT_RECORDED')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $startDate = $result && $result['start_date'] ? $result['start_date'] : date('Y-m-d', strtotime('-90 days'));

    // Recalculate from start date
    $success = $cashBlotterService->recalculateFromDate($startDate);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Cash blotter recalculated successfully from ' . $startDate
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to recalculate cash blotter'
        ]);
    }

} catch (Exception $e) {
    error_log("Cash blotter recalculation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error recalculating cash blotter: ' . $e->getMessage()
    ]);
}
