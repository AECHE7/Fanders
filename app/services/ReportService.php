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
        $query = "SELECT
            l.id,
            l.loan_number,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            l.principal_amount,
            l.interest_rate,
            l.term_months,
            l.total_amount,
            l.status,
            l.created_at,
            l.disbursement_date,
            l.maturity_date,
            COALESCE(SUM(p.amount), 0) as total_paid,
            (l.total_amount - COALESCE(SUM(p.amount), 0)) as remaining_balance
        FROM loans l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN payments p ON l.id = p.loan_id AND p.status = 'completed'
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
            $query .= " AND l.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['client_id'])) {
            $query .= " AND l.client_id = ?";
            $params[] = $filters['client_id'];
        }

        $query .= " GROUP BY l.id ORDER BY l.created_at DESC";

        $result = $this->db->resultSet($query, $params);
        return $result ?: [];
    }

    /**
     * Generate payment report with filtering options
     */
    public function generatePaymentReport($filters = []) {
        $query = "SELECT
            p.id,
            p.payment_number,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            l.loan_number,
            p.amount,
            p.payment_date,
            p.payment_method,
            p.status,
            p.created_at,
            p.principal_amount,
            p.interest_amount,
            p.penalty_amount
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

        if (!empty($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_method'])) {
            $query .= " AND p.payment_method = ?";
            $params[] = $filters['payment_method'];
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
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            c.email,
            c.phone,
            c.address,
            c.status,
            c.created_at,
            c.updated_at,
            COUNT(DISTINCT l.id) as total_loans,
            COALESCE(SUM(l.principal_amount), 0) as total_principal,
            COALESCE(SUM(p.amount), 0) as total_payments,
            (COALESCE(SUM(l.total_amount), 0) - COALESCE(SUM(p.amount), 0)) as outstanding_balance
        FROM clients c
        LEFT JOIN loans l ON c.id = l.client_id
        LEFT JOIN payments p ON l.id = p.loan_id AND p.status = 'completed'
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
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.email,
            u.role,
            u.is_active,
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
            $query .= " AND u.is_active = ?";
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
            COALESCE(SUM(principal_amount), 0) as total_principal,
            COALESCE(SUM(total_amount), 0) as total_amount_with_interest
        FROM loans
        WHERE status IN ('active', 'completed')
        AND disbursement_date BETWEEN ? AND ?";

        $loansData = $this->db->single($loansQuery, [$dateFrom, $dateTo]) ?: [
            'total_loans' => 0,
            'total_principal' => 0,
            'total_amount_with_interest' => 0
        ];

        // Total payments received
        $paymentsQuery = "SELECT
            COUNT(*) as total_payments,
            COALESCE(SUM(amount), 0) as total_payments_received,
            COALESCE(SUM(principal_amount), 0) as principal_paid,
            COALESCE(SUM(interest_amount), 0) as interest_received,
            COALESCE(SUM(penalty_amount), 0) as penalties_received
        FROM payments
        WHERE status = 'completed'
        AND payment_date BETWEEN ? AND ?";

        $paymentsData = $this->db->single($paymentsQuery, [$dateFrom, $dateTo]) ?: [
            'total_payments' => 0,
            'total_payments_received' => 0,
            'principal_paid' => 0,
            'interest_received' => 0,
            'penalties_received' => 0
        ];

        // Outstanding balances
        $outstandingQuery = "SELECT
            COALESCE(SUM(total_amount - COALESCE(paid_amount, 0)), 0) as total_outstanding
        FROM (
            SELECT
                l.total_amount,
                SUM(p.amount) as paid_amount
            FROM loans l
            LEFT JOIN payments p ON l.id = p.loan_id AND p.status = 'completed'
            WHERE l.status = 'active'
            GROUP BY l.id
        ) as loan_balances";

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
            l.loan_number,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            c.phone,
            l.principal_amount,
            l.total_amount,
            COALESCE(SUM(p.amount), 0) as total_paid,
            (l.total_amount - COALESCE(SUM(p.amount), 0)) as remaining_balance,
            l.maturity_date,
            DATEDIFF(CURDATE(), l.maturity_date) as days_overdue,
            l.status
        FROM loans l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN payments p ON l.id = p.loan_id AND p.status = 'completed'
        WHERE l.status = 'active'
        AND l.maturity_date < CURDATE()
        GROUP BY l.id
        HAVING remaining_balance > 0";

        $params = [];

        if (!empty($filters['days_overdue'])) {
            $query .= " AND DATEDIFF(CURDATE(), l.maturity_date) >= ?";
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

        // Define columns
        $columns = [
            ['header' => 'Payment #', 'width' => 25],
            ['header' => 'Client', 'width' => 35],
            ['header' => 'Loan #', 'width' => 25],
            ['header' => 'Amount', 'width' => 25],
            ['header' => 'Date', 'width' => 25],
            ['header' => 'Method', 'width' => 20],
            ['header' => 'Status', 'width' => 20]
        ];

        // Prepare data
        $tableData = [];
        foreach ($data as $payment) {
            $tableData[] = [
                $payment['payment_number'],
                $payment['client_name'],
                $payment['loan_number'],
                number_format($payment['amount'], 2),
                date('Y-m-d', strtotime($payment['payment_date'])),
                ucfirst($payment['payment_method']),
                ucfirst($payment['status'])
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
        $pdf->addLine('Principal Paid: ₱' . number_format($data['payments']['principal_paid'], 2));
        $pdf->addLine('Interest Received: ₱' . number_format($data['payments']['interest_received'], 2));
        $pdf->addLine('Penalties Received: ₱' . number_format($data['payments']['penalties_received'], 2));
        $pdf->addSpace();

        // Outstanding Section
        $pdf->addSubHeader('Outstanding Balances');
        $pdf->addLine('Total Outstanding: ₱' . number_format($data['outstanding']['total_outstanding'], 2));

        return $pdf->output('D', 'financial_summary_' . date('Y-m-d') . '.pdf');
    }
}
