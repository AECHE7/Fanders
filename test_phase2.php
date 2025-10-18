<?php
require_once 'app/config/config.php';
require_once 'app/services/TransactionService.php';
require_once 'app/services/CashBlotterService.php';

try {
    $transactionService = new TransactionService();
    $cashBlotterService = new CashBlotterService();

    // Test transaction logging
    $result1 = $transactionService->logTransaction('TEST', 1, 'Test transaction', ['test' => 'data']);
    echo "Transaction logging: " . ($result1 ? 'SUCCESS' : 'FAILED') . "\n";

    // Test cash blotter
    $result2 = $cashBlotterService->updateBlotterForDate(date('Y-m-d'), 1000.00, 500.00);
    echo "Cash blotter update: " . ($result2 ? 'SUCCESS' : 'FAILED') . "\n";

    // Test data retrieval
    $transactions = $transactionService->getTransactionHistory(5);
    echo "Transaction retrieval: " . (is_array($transactions) ? 'SUCCESS (' . count($transactions) . ' records)' : 'FAILED') . "\n";

    $blotter = $cashBlotterService->getBlotterRange(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
    echo "Cash blotter retrieval: " . (is_array($blotter) ? 'SUCCESS (' . count($blotter) . ' records)' : 'FAILED') . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
