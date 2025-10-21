<?php
/**
 * ReportService - Handles report generation for the microfinance system
 *
 * This service provides methods to generate various reports including:
 * - Loan reports
 * - Payment reports
 * - Client reports
 * - User reports
 * - Financial summaries
 */

require_once BASE_PATH . '/app/core/BaseService.php';
require_once BASE_PATH . '/app/utilities/PDFGenerator.php';

class ReportService extends BaseService {
    private $loanModel;
    private $paymentModel;
    private $clientModel;
    private $userModel;
    private $transactionLogModel;
    private $cashBlotterModel;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->paymentModel = new PaymentModel();
        $this->clientModel = new ClientModel();
        $this->userModel = new UserModel();
        $this->transactionLogModel = new TransactionLogModel();
        $this->cashBlotterModel = new CashBlotterModel();
    }

    /**
     * Generate loan report with filtering options
     */
    public function generateLoanReport($filters = []) {
        // Use a pre-aggregated payments subquery to avoid GROUP BY conflicts in PostgreSQL
        $query = "SELECT
            l.id,
            l.id as loan_number,
            c.name as client_name,
            c.email as client_email,
            l.principal as principal_amount,
            l.interest_rate,
            l.term_weeks as term_months,
            l.total_loan_amount as total_amount,
            l.status,
            l.created_at,
            l.disbursement_date,
            l.completion_date as maturity_date,
            COALESCE(p.total_paid, 0) as total_paid,
            (l.total_loan_amount - COALESCE(p.total_paid, 0)) as remaining_balance
        FROM loans l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN (
            SELECT loan_id, SUM(amount) AS total_paid
            FROM payments
            GROUP BY loan_id
        ) p ON l.id = p.loan_id
        WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query .= " AND l.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND l.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['status'])) {
            // Normalize status to match DB values (e.g., 'Active', 'Completed')
            $normalizedStatus = ucfirst(strtolower($filters['status']));
            $query .= " AND l.status = ?";
            $params[] = $normalizedStatus;
        }

        if (!empty($filters['client_id'])) {
            $query .= " AND l.client_id = ?";
            $params[] = $filters['client_id'];
        }

    $query .= " ORDER BY l.created_at DESC";

        $result = $this->db->resultSet($query, $params);
        return $result ?: [];
    }

    /**
     * Generate payment report with filtering options
     */
    public function generatePaymentReport($filters = []) {
        $query = "SELECT
            p.id,
            p.id as payment_number,
            c.name as client_name,
            c.email as client_email,
            l.id as loan_number,
            l.id as loan_id,
            p.amount,
            p.payment_date,
            p.created_at
        FROM payments p
        LEFT JOIN loans l ON p.loan_id = l.id
        LEFT JOIN clients c ON l.client_id = c.id
        WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query .= " AND p.payment_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND p.payment_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['client_id'])) {
            $query .= " AND l.client_id = ?";
            $params[] = $filters['client_id'];
        }

        $query .= " ORDER BY p.payment_date DESC";

        $result = $this->db->resultSet($query, $params);
        return $result ?: [];
    }

    /**
     * Generate client report with filtering options
     */
    public function generateClientReport($filters = []) {
        $query = "SELECT
            c.id,
            c.id as client_id,
            c.name as client_name,
            c.email,
            c.phone_number as phone,
            c.address,
            c.status,
            c.created_at,
            c.updated_at,
            COUNT(DISTINCT l.id) as total_loans,
            COALESCE(SUM(l.principal), 0) as total_principal,
            COALESCE(SUM(p.amount), 0) as total_payments,
            (COALESCE(SUM(l.total_loan_amount), 0) - COALESCE(SUM(p.amount), 0)) as outstanding_balance
        FROM clients c
        LEFT JOIN loans l ON c.id = l.client_id
        LEFT JOIN payments p ON l.id = p.loan_id
        WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query .= " AND c.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND c.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['status'])) {
            $query .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        $query .= " GROUP BY c.id ORDER BY c.created_at DESC";

        $result = $this->db->resultSet($query, $params);
        return $result ?: [];
    }

    /**
     * Generate user report with filtering options
     */
    public function generateUserReport($filters = []) {
        $query = "SELECT
            u.id,
            u.id as user_id,
            u.email as username,
            u.name as full_name,
            u.email,
            u.role,
            CASE WHEN u.status = 'active' THEN 1 ELSE 0 END as is_active,
            u.created_at,
            u.last_login
        FROM users u
        WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['role'])) {
            $query .= " AND u.role = ?";
            $params[] = $filters['role'];
        }

        if (isset($filters['is_active'])) {
            $query .= " AND CASE WHEN u.status = 'active' THEN 1 ELSE 0 END = ?";
            $params[] = $filters['is_active'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND u.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND u.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $query .= " ORDER BY u.created_at DESC";

        $result = $this->db->resultSet($query, $params);
        return $result ?: [];
    }

    /**
     * Generate financial summary report
     */
    public function generateFinancialSummary($filters = []) {
        $dateFrom = $filters['date_from'] ?? date('Y-m-01');
        $dateTo = $filters['date_to'] ?? date('Y-m-t');

        // Total loans disbursed
        $loansQuery = "SELECT
            COUNT(*) as total_loans,
            COALESCE(SUM(principal), 0) as total_principal,
            COALESCE(SUM(total_loan_amount), 0) as total_amount_with_interest
        FROM loans
        WHERE status IN ('Active', 'Completed')
        AND disbursement_date BETWEEN ? AND ?";

        $loansData = $this->db->single($loansQuery, [$dateFrom, $dateTo]) ?: [
            'total_loans' => 0,
            'total_principal' => 0,
            'total_amount_with_interest' => 0
        ];

        // Total payments received
        $paymentsQuery = "SELECT
            COUNT(*) as total_payments,
            COALESCE(SUM(amount), 0) as total_payments_received
        FROM payments
        WHERE payment_date BETWEEN ? AND ?";

        $paymentsData = $this->db->single($paymentsQuery, [$dateFrom, $dateTo]) ?: [
            'total_payments' => 0,
            'total_payments_received' => 0
        ];

        // Outstanding balances
        $outstandingQuery = "SELECT
            COALESCE(SUM(l.total_loan_amount - COALESCE(p.paid_amount, 0)), 0) as total_outstanding
        FROM loans l
        LEFT JOIN (
            SELECT loan_id, SUM(amount) as paid_amount
            FROM payments
            GROUP BY loan_id
        ) p ON l.id = p.loan_id
        WHERE l.status = 'Active'";

        $outstandingData = $this->db->single($outstandingQuery) ?: [
            'total_outstanding' => 0
        ];

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'loans' => $loansData,
            'payments' => $paymentsData,
            'outstanding' => $outstandingData,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate overdue loans report
     */
    public function generateOverdueReport($filters = []) {
        $query = "SELECT
            l.id,
            l.id as loan_number,
            c.name as client_name,
            c.email as client_email,
            c.phone_number as phone,
            l.principal as principal_amount,
            l.total_loan_amount as total_amount,
            COALESCE(SUM(p.amount), 0) as total_paid,
            (l.total_loan_amount - COALESCE(SUM(p.amount), 0)) as remaining_balance,
            l.completion_date as maturity_date,
            (CURRENT_DATE - l.completion_date::date) as days_overdue,
            l.status
        FROM loans l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN payments p ON l.id = p.loan_id
        WHERE l.status = 'active'
        AND l.completion_date < CURRENT_DATE
        GROUP BY l.id, c.id
        HAVING remaining_balance > 0";

        $params = [];

        if (!empty($filters['days_overdue'])) {
            $query .= " AND (CURRENT_DATE - l.completion_date::date) >= ?";
            $params[] = $filters['days_overdue'];
        }

        $query .= " ORDER BY days_overdue DESC";

        return $this->db->resultSet($query, $params);
    }

    /**
     * Export loan report to PDF
     */
    public function exportLoanReportPDF($data, $filters = []) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('Loan Report');

        $title = 'Loan Report';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $title .= ' (' . $filters['date_from'] . ' to ' . $filters['date_to'] . ')';
        }

        $pdf->addHeader($title);
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Define columns
        $columns = [
            ['header' => 'Loan #', 'width' => 25],
            ['header' => 'Client', 'width' => 40],
            ['header' => 'Principal', 'width' => 25],
            ['header' => 'Total', 'width' => 25],
            ['header' => 'Paid', 'width' => 25],
            ['header' => 'Balance', 'width' => 25],
            ['header' => 'Status', 'width' => 20]
        ];

        // Prepare data
        $tableData = [];
        foreach ($data as $loan) {
            $tableData[] = [
                $loan['loan_number'],
                $loan['client_name'],
                number_format($loan['principal_amount'], 2),
                number_format($loan['total_amount'], 2),
                number_format($loan['total_paid'], 2),
                number_format($loan['remaining_balance'], 2),
                ucfirst($loan['status'])
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Add summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
        $totalLoans = count($data);
        $totalPrincipal = array_sum(array_column($data, 'principal_amount'));
        $totalPaid = array_sum(array_column($data, 'total_paid'));
        $totalBalance = array_sum(array_column($data, 'remaining_balance'));

        $pdf->addLine("Total Loans: $totalLoans");
        $pdf->addLine("Total Principal: ₱" . number_format($totalPrincipal, 2));
        $pdf->addLine("Total Paid: ₱" . number_format($totalPaid, 2));
        $pdf->addLine("Total Outstanding: ₱" . number_format($totalBalance, 2));

        return $pdf->output('D', 'loan_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export payment report to PDF
     */
    public function exportPaymentReportPDF($data, $filters = []) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('Payment Report');

        $title = 'Payment Report';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $title .= ' (' . $filters['date_from'] . ' to ' . $filters['date_to'] . ')';
        }

        $pdf->addHeader($title);
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Define columns (limited to fields present in DB)
        $columns = [
            ['header' => 'Payment #', 'width' => 30],
            ['header' => 'Client', 'width' => 40],
            ['header' => 'Loan #', 'width' => 25],
            ['header' => 'Amount', 'width' => 25],
            ['header' => 'Date', 'width' => 25]
        ];

        // Prepare data
        $tableData = [];
        foreach ($data as $payment) {
            $tableData[] = [
                $payment['payment_number'],
                $payment['client_name'],
                $payment['loan_number'],
                number_format($payment['amount'], 2),
                date('Y-m-d', strtotime($payment['payment_date']))
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Add summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
        $totalPayments = count($data);
        $totalAmount = array_sum(array_column($data, 'amount'));

        $pdf->addLine("Total Payments: $totalPayments");
        $pdf->addLine("Total Amount: ₱" . number_format($totalAmount, 2));

        return $pdf->output('D', 'payment_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export client report to PDF
     */
    public function exportClientReportPDF($data, $filters = []) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('Client Report');

        $title = 'Client Report';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $title .= ' (' . $filters['date_from'] . ' to ' . $filters['date_to'] . ')';
        }

        $pdf->addHeader($title);
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Define columns
        $columns = [
            ['header' => 'Client Name', 'width' => 40],
            ['header' => 'Email', 'width' => 50],
            ['header' => 'Phone', 'width' => 30],
            ['header' => 'Loans', 'width' => 15],
            ['header' => 'Outstanding', 'width' => 30],
            ['header' => 'Status', 'width' => 20]
        ];

        // Prepare data
        $tableData = [];
        foreach ($data as $client) {
            $tableData[] = [
                $client['client_name'],
                $client['email'],
                $client['phone'],
                $client['total_loans'],
                number_format($client['outstanding_balance'], 2),
                ucfirst($client['status'])
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Add summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
        $totalClients = count($data);
        $totalLoans = array_sum(array_column($data, 'total_loans'));
        $totalOutstanding = array_sum(array_column($data, 'outstanding_balance'));

        $pdf->addLine("Total Clients: $totalClients");
        $pdf->addLine("Total Loans: $totalLoans");
        $pdf->addLine("Total Outstanding: ₱" . number_format($totalOutstanding, 2));

        return $pdf->output('D', 'client_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export user report to PDF
     */
    public function exportUserReportPDF($data, $filters = []) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('User Report');

        $pdf->addHeader('User Report');
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Define columns
        $columns = [
            ['header' => 'Username', 'width' => 30],
            ['header' => 'Full Name', 'width' => 50],
            ['header' => 'Email', 'width' => 60],
            ['header' => 'Role', 'width' => 25],
            ['header' => 'Status', 'width' => 20]
        ];

        // Prepare data
        $tableData = [];
        foreach ($data as $user) {
            $tableData[] = [
                $user['username'],
                $user['full_name'],
                $user['email'],
                ucfirst($user['role']),
                $user['is_active'] ? 'Active' : 'Inactive'
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Add summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
        $totalUsers = count($data);
        $activeUsers = count(array_filter($data, function($user) { return $user['is_active']; }));

        $pdf->addLine("Total Users: $totalUsers");
        $pdf->addLine("Active Users: $activeUsers");
        $pdf->addLine("Inactive Users: " . ($totalUsers - $activeUsers));

        return $pdf->output('D', 'user_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Generate transaction report with filtering options
     */
    public function generateTransactionReport($filters = []) {
        $transactionService = new TransactionService();
        $transactions = $transactionService->getTransactionHistory($filters, 1000); // Large limit for reports

        // Transform transaction data to match report template expectations
        $transformedData = [];
        foreach ($transactions as $transaction) {
            $details = json_decode($transaction['details'], true) ?: [];

            // Determine entity type from transaction type
            $entityType = $this->getEntityTypeFromTransactionType($transaction['transaction_type']);

            // Get user information
            $userInfo = $this->getUserInfo($transaction['user_id']);

            $transformedData[] = [
                'timestamp' => $transaction['created_at'],
                // Use single name field from users table
                'user_name' => $userInfo['name'] ?? null,
                'role' => $userInfo['role'] ?? null,
                'action' => $details['action'] ?? $this->getActionFromTransactionType($transaction['transaction_type']),
                'entity_type' => $entityType,
                'entity_id' => $transaction['reference_id'],
                'details' => $transaction['details'],
                'transaction_type' => $transaction['transaction_type'],
                'user_email' => $transaction['user_email'] ?? null,
                'created_at' => $transaction['created_at']
            ];
        }

        return $transformedData;
    }

    /**
     * Get entity type from transaction type
     */
    private function getEntityTypeFromTransactionType($transactionType) {
        $mapping = [
            'user_created' => 'user',
            'user_updated' => 'user',
            'user_deleted' => 'user',
            'user_viewed' => 'user',
            'client_created' => 'client',
            'client_updated' => 'client',
            'client_deleted' => 'client',
            'client_viewed' => 'client',
            'loan_created' => 'loan',
            'loan_updated' => 'loan',
            'loan_approved' => 'loan',
            'loan_disbursed' => 'loan',
            'loan_completed' => 'loan',
            'loan_cancelled' => 'loan',
            'loan_deleted' => 'loan',
            'loan_viewed' => 'loan',
            'payment_created' => 'payment',
            'payment_recorded' => 'payment',
            'payment_approved' => 'payment',
            'payment_cancelled' => 'payment',
            'payment_overdue' => 'payment',
            'payment_viewed' => 'payment',
        ];

        return $mapping[$transactionType] ?? 'system';
    }

    /**
     * Get action from transaction type
     */
    private function getActionFromTransactionType($transactionType) {
        $actions = [
            'user_created' => 'created',
            'user_updated' => 'updated',
            'user_deleted' => 'deleted',
            'user_viewed' => 'viewed',
            'client_created' => 'created',
            'client_updated' => 'updated',
            'client_deleted' => 'deleted',
            'client_viewed' => 'viewed',
            'loan_created' => 'created',
            'loan_updated' => 'updated',
            'loan_approved' => 'approved',
            'loan_disbursed' => 'disbursed',
            'loan_completed' => 'completed',
            'loan_cancelled' => 'cancelled',
            'loan_deleted' => 'deleted',
            'loan_viewed' => 'viewed',
            'payment_created' => 'created',
            'payment_recorded' => 'recorded',
            'payment_approved' => 'approved',
            'payment_cancelled' => 'cancelled',
            'payment_overdue' => 'overdue',
            'payment_viewed' => 'viewed',
            'login' => 'login',
            'logout' => 'logout',
            'session_extended' => 'session_extended',
        ];

        return $actions[$transactionType] ?? $transactionType;
    }

    /**
     * Get user information by ID
     */
    private function getUserInfo($userId) {
        if (!$userId) return [];

        $sql = "SELECT name, role FROM users WHERE id = ?";
        $result = $this->db->single($sql, [$userId]);
        return $result ?: [];
    }

    /**
     * Export transaction report to PDF
     */
    public function exportTransactionReportPDF($data, $filters = []) {
        $transactionService = new TransactionService();
        return $transactionService->exportTransactionsPDF($data, $filters);
    }

    /**
     * Export financial summary to PDF
     */
    public function exportFinancialSummaryPDF($data) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('Financial Summary Report');

        $pdf->addHeader('Financial Summary Report');
        $pdf->addLine('Period: ' . $data['period']['from'] . ' to ' . $data['period']['to']);
        $pdf->addLine('Generated on: ' . $data['generated_at']);
        $pdf->addSpace();

        // Loans Section
        $pdf->addSubHeader('Loan Disbursements');
        $pdf->addLine('Total Loans: ' . number_format($data['loans']['total_loans']));
        $pdf->addLine('Total Principal: ₱' . number_format($data['loans']['total_principal'], 2));
        $pdf->addLine('Total Amount (with interest): ₱' . number_format($data['loans']['total_amount_with_interest'], 2));
        $pdf->addSpace();

        // Payments Section
    $pdf->addSubHeader('Payments Received');
    $pdf->addLine('Total Payments: ' . number_format($data['payments']['total_payments']));
    $pdf->addLine('Total Amount Received: ₱' . number_format($data['payments']['total_payments_received'], 2));
        $pdf->addSpace();

        // Outstanding Section
        $pdf->addSubHeader('Outstanding Balances');
        $pdf->addLine('Total Outstanding: ₱' . number_format($data['outstanding']['total_outstanding'], 2));

        return $pdf->output('D', 'financial_summary_' . date('Y-m-d') . '.pdf');
    }
}
