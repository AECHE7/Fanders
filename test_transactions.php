<?php
require_once 'app/config/config.php';
require_once 'app/services/TransactionService.php';

try {
    $transactionService = new TransactionService();

    // Test logging different types of transactions
    echo "Testing Transaction Logging:\n";

    // Test loan transaction
    $result1 = $transactionService->logLoanTransaction('created', 1, 1, ['amount' => 10000]);
    echo "Loan creation log: " . ($result1 ? 'SUCCESS' : 'FAILED') . "\n";

    // Test payment transaction
    $result2 = $transactionService->logPaymentTransaction(1, 1, ['amount' => 500]);
    echo "Payment log: " . ($result2 ? 'SUCCESS' : 'FAILED') . "\n";

    // Test client transaction
    $result3 = $transactionService->logClientTransaction('created', 1, 1, ['name' => 'Test Client']);
    echo "Client creation log: " . ($result3 ? 'SUCCESS' : 'FAILED') . "\n";

    // Test user transaction
    $result4 = $transactionService->logUserTransaction('login', 1, 1, ['ip' => '127.0.0.1']);
    echo "User login log: " . ($result4 ? 'SUCCESS' : 'FAILED') . "\n";

    // Test retrieval
    echo "\nTesting Transaction Retrieval:\n";
    $transactions = $transactionService->getTransactionHistory(10);
    echo "Recent transactions: " . (is_array($transactions) ? count($transactions) . ' records' : 'FAILED') . "\n";

    if (count($transactions) > 0) {
        echo "Latest transaction: " . json_encode($transactions[0]) . "\n";
    }

    // Test transaction stats
    $stats = $transactionService->getTransactionStats();
    echo "Transaction stats: " . (is_array($stats) ? json_encode($stats) : 'FAILED') . "\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
