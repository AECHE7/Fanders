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
require_once BASE_PATH . '/app/utilities/ExcelExportUtility.php';
require_once BASE_PATH . '/app/utilities/FormatUtility.php';

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
            // Handle both lowercase and uppercase forms
            $normalizedStatus = ucfirst(strtolower($filters['status']));
            $query .= " AND LOWER(l.status) = LOWER(?)";
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
        // If no dates provided, treat as "All time" (no date constraint)
        $hasDateFrom = !empty($filters['date_from']);
        $hasDateTo = !empty($filters['date_to']);
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        // --- Loans Disbursed ---
        $loansParams = [];
        $loansQuery = "SELECT
            COUNT(*) as total_loans,
            COALESCE(SUM(principal), 0) as total_principal,
            COALESCE(SUM(total_loan_amount), 0) as total_amount_with_interest
        FROM loans
        WHERE LOWER(status) IN ('active', 'completed')";

        if ($hasDateFrom && $hasDateTo) {
            $loansQuery .= " AND disbursement_date BETWEEN ? AND ?";
            $loansParams[] = $dateFrom;
            $loansParams[] = $dateTo;
        } elseif ($hasDateFrom) {
            $loansQuery .= " AND disbursement_date >= ?";
            $loansParams[] = $dateFrom;
        } elseif ($hasDateTo) {
            $loansQuery .= " AND disbursement_date <= ?";
            $loansParams[] = $dateTo;
        }

        $loansData = $this->db->single($loansQuery, $loansParams) ?: [
            'total_loans' => 0,
            'total_principal' => 0,
            'total_amount_with_interest' => 0
        ];

        // --- Payments Received ---
        $paymentsParams = [];
        $paymentsQuery = "SELECT
            COUNT(*) as total_payments,
            COALESCE(SUM(amount), 0) as total_payments_received
        FROM payments
        WHERE 1=1";

        if ($hasDateFrom && $hasDateTo) {
            $paymentsQuery .= " AND payment_date BETWEEN ? AND ?";
            $paymentsParams[] = $dateFrom;
            $paymentsParams[] = $dateTo;
        } elseif ($hasDateFrom) {
            $paymentsQuery .= " AND payment_date >= ?";
            $paymentsParams[] = $dateFrom;
        } elseif ($hasDateTo) {
            $paymentsQuery .= " AND payment_date <= ?";
            $paymentsParams[] = $dateTo;
        }

        $paymentsData = $this->db->single($paymentsQuery, $paymentsParams) ?: [
            'total_payments' => 0,
            'total_payments_received' => 0
        ];

        // --- Outstanding Balances (across all active loans) ---
        $outstandingQuery = "SELECT
            COALESCE(SUM(l.total_loan_amount - COALESCE(p.paid_amount, 0)), 0) as total_outstanding
        FROM loans l
        LEFT JOIN (
            SELECT loan_id, SUM(amount) as paid_amount
            FROM payments
            GROUP BY loan_id
        ) p ON l.id = p.loan_id
        WHERE LOWER(l.status) = 'active'";

        $outstandingData = $this->db->single($outstandingQuery) ?: [
            'total_outstanding' => 0
        ];

        // Expose a friendly period label
        $period = [
            'from' => $dateFrom ?: 'All time',
            'to' => $dateTo ?: 'Present'
        ];

        return [
            'period' => $period,
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
        // Pre-aggregate payments to satisfy PostgreSQL grouping rules and filter on remaining balance
        $query = "SELECT
            l.id,
            l.id as loan_number,
            c.name as client_name,
            c.email as client_email,
            c.phone_number as phone,
            l.principal as principal_amount,
            l.total_loan_amount as total_amount,
            COALESCE(p.total_paid, 0) as total_paid,
            (l.total_loan_amount - COALESCE(p.total_paid, 0)) as remaining_balance,
            l.completion_date as maturity_date,
            (CURRENT_DATE - l.completion_date::date) as days_overdue,
            l.status
        FROM loans l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN (
            SELECT loan_id, SUM(amount) AS total_paid
            FROM payments
            GROUP BY loan_id
        ) p ON l.id = p.loan_id
        WHERE LOWER(l.status) = 'active'
          AND l.completion_date < CURRENT_DATE
          AND (l.total_loan_amount - COALESCE(p.total_paid, 0)) > 0";

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
    $pdf->addLine('Total Principal: ' . FormatUtility::peso($totalPrincipal));
    $pdf->addLine('Total Paid: ' . FormatUtility::peso($totalPaid));
    $pdf->addLine('Total Outstanding: ' . FormatUtility::peso($totalBalance));

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
    $pdf->addLine('Total Amount: ' . FormatUtility::peso($totalAmount));

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
    $pdf->addLine('Total Outstanding: ' . FormatUtility::peso($totalOutstanding));

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
            // Parse details, tolerate missing/null strings
            $rawDetails = $transaction['details'] ?? '';
            $details = is_string($rawDetails) ? (json_decode($rawDetails, true) ?: []) : (is_array($rawDetails) ? $rawDetails : []);

            // Normalize transaction type and entity/reference id
            $txType = $transaction['transaction_type'] ?? ($details['transaction_type'] ?? 'system');
            $entityType = $this->getEntityTypeFromTransactionType($txType);
            $entityId = $transaction['reference_id'] ?? ($details['reference_id'] ?? null);

            // Get user information (safe for null IDs)
            $userInfo = $this->getUserInfo($transaction['user_id'] ?? null);

            // Determine action (prefer explicit detail action)
            $action = $details['action'] ?? $this->getActionFromTransactionType($txType);

            $transformedData[] = [
                'timestamp' => $transaction['created_at'] ?? ($transaction['timestamp'] ?? date('Y-m-d H:i:s')),
                // Use single name field from users table
                'user_name' => $userInfo['name'] ?? '',
                'role' => $userInfo['role'] ?? '',
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId ?? '-',
                'details' => is_string($rawDetails) ? $rawDetails : json_encode($details),
                'transaction_type' => $txType,
                'user_email' => $transaction['user_email'] ?? null,
                'created_at' => $transaction['created_at'] ?? null
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
        $pdf->addEmphasisLine('Total Loans: ' . number_format($data['loans']['total_loans']), 14, true);
        $pdf->addEmphasisLine('Total Principal: ₱' . number_format($data['loans']['total_principal'], 2), 14, true);
        $pdf->addEmphasisLine('Total Amount (with interest): ₱' . number_format($data['loans']['total_amount_with_interest'], 2), 14, true);
        $pdf->addSpace();

        // Payments Section
        $pdf->addSubHeader('Payments Received');
        $pdf->addEmphasisLine('Total Payments: ' . number_format($data['payments']['total_payments']), 14, true);
        $pdf->addEmphasisLine('Total Amount Received: ₱' . number_format($data['payments']['total_payments_received'], 2), 14, true);
        $pdf->addSpace();

        // Outstanding Section
        $pdf->addSubHeader('Outstanding Balances');
        $pdf->addEmphasisLine('Total Outstanding: ₱' . number_format($data['outstanding']['total_outstanding'], 2), 14, true);

        return $pdf->output('D', 'financial_summary_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export overdue loans report to PDF
     */
    public function exportOverdueReportPDF($data, $filters = []) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('Overdue Loans Report');

        $title = 'Overdue Loans Report';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $title .= ' (' . $filters['date_from'] . ' to ' . $filters['date_to'] . ')';
        }

        $pdf->addHeader($title);
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Define columns
        $columns = [
            ['header' => 'Loan #', 'width' => 20],
            ['header' => 'Client', 'width' => 45],
            ['header' => 'Phone', 'width' => 30],
            ['header' => 'Principal', 'width' => 25],
            ['header' => 'Balance', 'width' => 25],
            ['header' => 'Days Overdue', 'width' => 25]
        ];

        // Prepare data
        $tableData = [];
        foreach ($data as $row) {
            $tableData[] = [
                $row['loan_number'],
                $row['client_name'],
                $row['phone'] ?? '',
                number_format($row['principal_amount'], 2),
                number_format($row['remaining_balance'], 2),
                (string)$row['days_overdue']
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
    $totalOverdue = array_sum(array_column($data, 'remaining_balance'));
        $avgDays = count($data) > 0 ? array_sum(array_column($data, 'days_overdue')) / count($data) : 0;
        $pdf->addLine('Total Overdue Loans: ' . count($data));
    $pdf->addLine('Total Overdue Amount: ' . FormatUtility::peso($totalOverdue));
        $pdf->addLine('Average Days Overdue: ' . number_format($avgDays, 1));

        return $pdf->output('D', 'overdue_loans_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Excel Exports - Excel 2003 XML (SpreadsheetML)
     */
    public function exportLoanReportExcel($data, $filters = []) {
        // Validate data before export
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('No loan data available for export');
        }
        
        $headers = ['Loan #', 'Client', 'Principal', 'Total', 'Paid', 'Balance', 'Status'];
        $rows = [];
        foreach ($data as $loan) {
            // Ensure $loan is an array and has basic structure
            if (!is_array($loan)) {
                continue; // Skip non-array records
            }
            
            // Extract values safely with comprehensive null coalescing
            $loanNumber = $loan['loan_number'] ?? $loan['number'] ?? '';
            $clientName = $loan['client_name'] ?? $loan['name'] ?? $loan['full_name'] ?? '';
            $principal = $loan['principal_amount'] ?? $loan['principal'] ?? 0;
            $total = $loan['total_amount'] ?? $loan['amount'] ?? 0;
            $paid = $loan['total_paid'] ?? $loan['paid'] ?? 0;
            $balance = $loan['remaining_balance'] ?? $loan['balance'] ?? 0;
            $status = $loan['status'] ?? 'unknown';
            
            // Skip records with no identifiable loan number or client
            if (empty($loanNumber) && empty($clientName)) {
                continue;
            }
            
            $rows[] = [
                $loanNumber,
                $clientName,
                (float)$principal,
                (float)$total,
                (float)$paid,
                (float)$balance,
                ucfirst((string)$status)
            ];
        }
        
        // Ensure we have data to export after validation
        if (empty($rows)) {
            throw new InvalidArgumentException('No valid loan records found for export');
        }
        
        $title = 'loans_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputSingleSheet('Loans', $headers, $rows, $title);
    }

    public function exportPaymentReportExcel($data, $filters = []) {
        // Validate data before export
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('No payment data available for export');
        }
        
        $headers = ['Payment #', 'Client', 'Loan #', 'Amount', 'Date'];
        $rows = [];
        foreach ($data as $p) {
            // Ensure $p is an array and has basic structure
            if (!is_array($p)) {
                continue; // Skip non-array records
            }
            
            // Extract values safely with comprehensive null coalescing
            $paymentNumber = $p['payment_number'] ?? $p['number'] ?? '';
            $clientName = $p['client_name'] ?? $p['name'] ?? $p['full_name'] ?? '';
            $loanNumber = $p['loan_number'] ?? $p['loan_id'] ?? '';
            $amount = $p['amount'] ?? $p['payment_amount'] ?? 0;
            $paymentDate = $p['payment_date'] ?? $p['date'] ?? '';
            
            // Skip records with no identifiable payment number or client
            if (empty($paymentNumber) && empty($clientName)) {
                continue;
            }
            
            $rows[] = [
                $paymentNumber,
                $clientName,
                $loanNumber,
                (float)$amount,
                !empty($paymentDate) ? date('Y-m-d', strtotime($paymentDate)) : ''
            ];
        }
        
        // Ensure we have data to export after validation
        if (empty($rows)) {
            throw new InvalidArgumentException('No valid payment records found for export');
        }
        
        $title = 'payments_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputSingleSheet('Payments', $headers, $rows, $title);
    }

    public function exportClientReportExcel($data, $filters = []) {
        // Validate data before export
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('No client data available for export');
        }
        
        $headers = ['Client Name', 'Email', 'Phone', 'Loans', 'Outstanding', 'Status'];
        $rows = [];
        foreach ($data as $c) {
            // Ensure $c is an array and has basic structure
            if (!is_array($c)) {
                continue; // Skip non-array records
            }
            
            // Extract values safely with comprehensive null coalescing
            $clientName = $c['client_name'] ?? $c['name'] ?? $c['full_name'] ?? '';
            $email = $c['email'] ?? '';
            $phone = $c['phone'] ?? $c['phone_number'] ?? '';
            $totalLoans = $c['total_loans'] ?? $c['loan_count'] ?? 0;
            $outstanding = $c['outstanding_balance'] ?? $c['balance'] ?? 0;
            $status = $c['status'] ?? $c['is_active'] ?? 'unknown';
            
            // Skip records with no identifiable client name
            if (empty($clientName)) {
                continue;
            }
            
            $rows[] = [
                $clientName,
                $email,
                $phone,
                (int)$totalLoans,
                (float)$outstanding,
                ucfirst((string)$status)
            ];
        }
        
        // Ensure we have data to export after validation
        if (empty($rows)) {
            throw new InvalidArgumentException('No valid client records found for export');
        }
        
        $title = 'clients_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputSingleSheet('Clients', $headers, $rows, $title);
    }

    public function exportUserReportExcel($data, $filters = []) {
        // Validate data before export
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('No user data available for export');
        }
        
        $headers = ['Username', 'Full Name', 'Email', 'Role', 'Status'];
        $rows = [];
        foreach ($data as $u) {
            // Ensure $u is an array and has basic structure
            if (!is_array($u)) {
                continue; // Skip non-array records
            }
            
            // Extract values safely with comprehensive null coalescing
            $username = $u['username'] ?? $u['user_name'] ?? '';
            // Build full name safely without undefined index notices
            $fullName = $u['full_name']
                ?? ($u['name'] ?? null)
                ?? trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            $email = $u['email'] ?? '';
            $role = $u['role'] ?? $u['user_role'] ?? 'unknown';
            // Normalize active status from various shapes (bool/int/string)
            if (array_key_exists('is_active', $u)) {
                $isActive = (bool)$u['is_active'];
            } elseif (array_key_exists('active', $u)) {
                $isActive = filter_var($u['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $isActive = $isActive === null ? (bool)$u['active'] : $isActive;
            } elseif (array_key_exists('status', $u)) {
                $statusVal = is_string($u['status']) ? strtolower((string)$u['status']) : $u['status'];
                $isActive = ($statusVal === 'active' || $statusVal === 1 || $statusVal === true);
            } else {
                $isActive = false;
            }
            
            // Skip records with no identifiable username
            if (empty($username)) {
                continue;
            }
            
            $rows[] = [
                $username,
                trim((string)$fullName),
                $email,
                ucfirst((string)$role),
                $isActive ? 'Active' : 'Inactive'
            ];
        }
        
        // Ensure we have data to export after validation
        if (empty($rows)) {
            throw new InvalidArgumentException('No valid user records found for export');
        }
        
        $title = 'users_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputSingleSheet('Users', $headers, $rows, $title);
    }

    public function exportOverdueReportExcel($data, $filters = []) {
        // Validate data before export
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('No overdue data available for export');
        }
        
        $headers = ['Loan #', 'Client', 'Phone', 'Principal', 'Balance', 'Days Overdue'];
        $rows = [];
        foreach ($data as $r) {
            // Validate each overdue record has required fields
            if (!isset($r['loan_number']) || !isset($r['client_name'])) {
                continue; // Skip malformed records
            }
            
            $rows[] = [
                $r['loan_number'],
                $r['client_name'],
                $r['phone'] ?? '',
                (float)($r['principal_amount'] ?? 0),
                (float)($r['remaining_balance'] ?? 0),
                (int)($r['days_overdue'] ?? 0)
            ];
        }
        
        // Ensure we have data to export after validation
        if (empty($rows)) {
            throw new InvalidArgumentException('No valid overdue records found for export');
        }
        
        $title = 'overdue_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputSingleSheet('Overdue', $headers, $rows, $title);
    }

    public function exportFinancialSummaryExcel($data) {
        // Validate data before export
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('No financial summary data available for export');
        }
        
        // Ensure required keys exist
        $requiredKeys = ['period', 'loans', 'payments', 'outstanding', 'generated_at'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required financial data: $key");
            }
        }
        
        $pairs = [
            'Period' => ($data['period']['from'] ?? '') . ' to ' . ($data['period']['to'] ?? ''),
            'Total Loans' => (int)($data['loans']['total_loans'] ?? 0),
            'Total Principal' => (float)($data['loans']['total_principal'] ?? 0),
            'Total Amount (with interest)' => (float)($data['loans']['total_amount_with_interest'] ?? 0),
            'Total Payments' => (int)($data['payments']['total_payments'] ?? 0),
            'Total Amount Received' => (float)($data['payments']['total_payments_received'] ?? 0),
            'Total Outstanding' => (float)($data['outstanding']['total_outstanding'] ?? 0),
            'Generated At' => $data['generated_at']
        ];
        $title = 'financial_summary_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputKeyValueSheet('Financial Summary', $pairs, $title);
    }

    // --- Cash Blotter Exports ---

    public function exportCashBlotterPDF($blotterData, $summary, $currentBalance, $filters) {
        $pdf = new PDFGenerator();
        $pdf->setTitle('Cash Blotter Report');
        
        $title = 'Cash Blotter Report';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $title .= ' (' . $filters['date_from'] . ' to ' . $filters['date_to'] . ')';
        }
        
        $pdf->addHeader($title);
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Summary Section
        $pdf->addSubHeader('Summary');
        $pdf->addLine('Current Balance: ' . FormatUtility::peso($currentBalance));
        $pdf->addLine('Total Inflow: ' . FormatUtility::peso($summary['total_inflow'] ?? 0));
        $pdf->addLine('Total Outflow: ' . FormatUtility::peso($summary['total_outflow'] ?? 0));
        $pdf->addLine('Net Flow: ' . FormatUtility::peso(($summary['total_inflow'] ?? 0) - ($summary['total_outflow'] ?? 0)));
        $pdf->addSpace();

        // Transactions Table
        $pdf->addSubHeader('Daily Cash Flow');
        
        $columns = [
            ['header' => 'Date', 'width' => 30],
            ['header' => 'Inflow', 'width' => 40],
            ['header' => 'Outflow', 'width' => 40],
            ['header' => 'Balance', 'width' => 40]
        ];

        $tableData = [];
        foreach ($blotterData as $entry) {
            $tableData[] = [
                date('M j, Y', strtotime($entry['blotter_date'] ?? 'today')),
                FormatUtility::peso($entry['total_inflow'] ?? 0),
                FormatUtility::peso($entry['total_outflow'] ?? 0),
                FormatUtility::peso($entry['calculated_balance'] ?? 0)
            ];
        }

        $pdf->addTable($columns, $tableData);
        
        $pdf->output('D', 'cash_blotter_' . date('Y-m-d') . '.pdf');
    }

    public function exportCashBlotterExcel($blotterData, $summary, $currentBalance, $filters) {
        // Validate data before export
        if (empty($blotterData) || !is_array($blotterData)) {
            throw new InvalidArgumentException('No cash blotter data available for export');
        }
        
        $headers = ['Date', 'Inflow', 'Outflow', 'Closing Balance'];
        $rows = [];
        foreach ($blotterData as $entry) {
            $rows[] = [
                isset($entry['blotter_date']) ? date('Y-m-d', strtotime($entry['blotter_date'])) : date('Y-m-d'),
                (float)($entry['total_inflow'] ?? 0),
                (float)($entry['total_outflow'] ?? 0),
                (float)($entry['calculated_balance'] ?? 0)
            ];
        }
        
        // Ensure we have data to export after validation
        if (empty($rows)) {
            throw new InvalidArgumentException('No valid cash blotter records found for export');
        }
        
        $title = 'cash_blotter_' . date('Y-m-d') . '.xls';
        ExcelExportUtility::outputSingleSheet('Cash Blotter', $headers, $rows, $title);
    }
}
