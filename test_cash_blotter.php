<?php
require_once 'app/config/config.php';
require_once 'app/services/CashBlotterService.php';

try {
    $cashBlotterService = new CashBlotterService();

    // Test with today's date
    $result = $cashBlotterService->updateBlotterForDate(date('Y-m-d'));
    echo 'Cash blotter update for today: ' . ($result ? 'SUCCESS' : 'FAILED') . "\n";

    // Get current balance
    $balance = $cashBlotterService->getCurrentBalance();
    echo 'Current balance: ₱' . number_format($balance, 2) . "\n";

    // Get range
    $range = $cashBlotterService->getBlotterRange(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
    echo 'Blotter range records: ' . count($range) . "\n";

    if (count($range) > 0) {
        echo 'Sample record: ' . json_encode($range[0]) . "\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
