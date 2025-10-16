<?php
/**
 * Payment Transaction Report Controller (payment-transactions.php)
 * Role: Displays a detailed list of all payment transactions received over a specified period (FR-005).
 * Integrates: ReportService
 */

require_once '../../public/init.php';

// Enforce access for auditing roles (Admin, Manager, Cashier)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize services
$reportService = new ReportService();

// --- 1. Get Filter Parameters (Defaults to current month) ---
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$action = $_GET['action'] ?? 'view';

// --- 2. Fetch Report Data ---

try {
    // Get the detailed list of payments for the period
    $transactions = $reportService->getPaymentTransactionReport($startDate, $endDate);
    $reportError = null;
    
    // Calculate total collected amount
    $totalCollected = array_sum(array_column($transactions, 'amount'));

    // --- 3. Handle PDF Generation ---
    if ($action === 'pdf') {
        // NOTE: Standard practice uses a library (FPDF/TCPDF). Outputting simple text confirmation.
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Payment_Audit_Report_' . $startDate . '_to_' . $endDate . '.pdf"');

        $pdfContent = "FANDERS MICROFINANCE PAYMENT AUDIT REPORT\n";
        $pdfContent .= "Period: {$startDate} to {$endDate}\n";
        $pdfContent .= "Total Transactions: " . count($transactions) . "\n";
        $pdfContent .= "Total Collected: ₱" . number_format($totalCollected, 2) . "\n\n";
        
        // Add table headers
        $pdfContent .= str_pad("ID", 5) . str_pad("Client", 30) . str_pad("Loan ID", 10) . str_pad("Amount", 15) . str_pad("Date", 20) . str_pad("Recorded By", 20) . "\n";
        $pdfContent .= str_repeat("-", 100) . "\n";

        // Add table data
        foreach ($transactions as $t) {
            $pdfContent .= str_pad($t['id'], 5) . str_pad($t['client_name'], 30) . str_pad($t['loan_id'], 10) . str_pad('₱' . number_format($t['amount'], 2), 15) . str_pad(date('Y-m-d H:i', strtotime($t['payment_date'])), 20) . str_pad($t['staff_name'], 20) . "\n";
        }
        
        echo $pdfContent;
        exit;
    }

} catch (Exception $e) {
    $transactions = [];
    $totalCollected = 0;
    $reportError = "Failed to generate report: " . $e->getMessage();
}


// --- 4. Display View ---
$pageTitle = "Payment Transactions Report";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Payment Transactions Audit</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/reports/cash-blotter.php" class="btn btn-sm btn-outline-secondary me-2">
                <i data-feather="dollar-sign"></i> View Cash Blotter
            </a>
            <a href="<?= APP_URL ?>/public/reports/payment-transactions.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&action=pdf" 
               class="btn btn-sm btn-info">
                <i data-feather="download"></i> Download Report
            </a>
        </div>
    </div>
    
    <!-- Report Summary Card -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-uppercase small">Total Collected in Period (<?= count($transactions) ?> payments)</h5>
                    <h2 class="mb-0 fw-bold">₱<?= number_format($totalCollected, 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Period Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/public/reports/payment-transactions.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Run Audit</button>
                    <input type="hidden" name="action" value="view">
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($reportError): ?>
        <div class="alert alert-danger"><?= $reportError ?></div>
    <?php elseif (!empty($transactions)): ?>
        <!-- Detailed Transaction Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Txn ID</th>
                                <th>Client Name</th>
                                <th>Loan ID</th>
                                <th>Amount (₱)</th>
                                <th>Payment Date</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['id']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($t['client_name']) ?><br>
                                        <small class="text-muted">Principal: ₱<?= number_format($t['principal'], 2) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $t['loan_id'] ?>" class="text-decoration-none">
                                            #<?= $t['loan_id'] ?>
                                        </a>
                                    </td>
                                    <td class="text-success fw-bold">₱<?= number_format($t['amount'], 2) ?></td>
                                    <td><?= date('M d, Y H:i A', strtotime($t['payment_date'])) ?></td>
                                    <td><?= htmlspecialchars($t['staff_name'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                             <tr>
                                <th colspan="3" class="text-end">TOTAL COLLECTED:</th>
                                <th class="text-success fw-bold">₱<?= number_format($totalCollected, 2) ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No payment transactions found in the specified period.</div>
    <?php endif; ?>
</main>

<?php
// Initialize Feather icons
echo '<script>document.addEventListener(\'DOMContentLoaded\', function() { if (typeof feather !== \'undefined\') { feather.replace(); } });</script>';
include_once BASE_PATH . '/templates/layout/footer.php';
?>