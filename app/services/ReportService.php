<?php
/**
 * ReportService - Centralizes complex data aggregation for all management and operational reports (FR-005).
 * Role: Provides consolidated views of financial data across Loans, Payments, and Cash Blotters.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/CashBlotterModel.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/SlrDocumentModel.php';
require_once __DIR__ . '/../models/ClientModel.php';

class ReportService extends BaseService {
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Retrieves the comprehensive financial summary for the entire loan portfolio.
     * @return array
     */
    public function getLoanPortfolioSummary() {
        // Calculates total initial capital lent and outstanding balances across all loans (active/completed/defaulted).
        $sql = "
            SELECT 
                COUNT(l.id) AS total_loans,
                SUM(l.principal) AS total_principal_lent,
                SUM(l.l.total_loan_amount) AS total_amount_due,
                COALESCE(SUM(CASE WHEN l.status = ? THEN l.total_loan_amount ELSE 0 END), 0) AS total_active_amount,
                COALESCE(SUM(p.amount), 0) AS total_payments_received
            FROM loans l
            LEFT JOIN payments p ON l.id = p.loan_id
        ";
        
        $result = $this->db->single($sql, [LoanModel::$STATUS_ACTIVE]);
        
        $totalLoans = $result['total_loans'] ?? 0;
        $totalPaymentsReceived = $result['total_payments_received'] ?? 0;
        $totalAmountDue = $result['total_amount_due'] ?? 0;

        // Calculate Outstanding Balance (must be done outside SUMs)
        $outstandingSql = "
            SELECT 
                SUM(l.total_loan_amount - COALESCE(p.total_paid, 0)) AS outstanding_balance
            FROM loans l
            LEFT JOIN (
                SELECT loan_id, SUM(amount) AS total_paid FROM payments GROUP BY loan_id
            ) p ON l.id = p.loan_id
            WHERE l.status = ?
        ";
        $outstandingResult = $this->db->single($outstandingSql, [LoanModel::$STATUS_ACTIVE]);
        $totalOutstandingBalance = $outstandingResult['outstanding_balance'] ?? 0;

        return [
            'total_loans' => (int)$totalLoans,
            'total_principal_lent' => (float)($result['total_principal_lent'] ?? 0),
            'total_amount_due' => (float)$totalAmountDue,
            'total_active_amount' => (float)($result['total_active_amount'] ?? 0),
            'total_payments_received' => (float)$totalPaymentsReceived,
            'total_outstanding_balance' => (float)max(0, $totalOutstandingBalance),
        ];
    }
    
    /**
     * Retrieves aggregated cash blotter data over a given date range (FR-004 Reporting).
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array
     */
    public function getCashBlotterSummaryReport($startDate, $endDate) {
        $sql = "
            SELECT
                SUM(total_collections) AS total_collections,
                SUM(total_loan_releases) AS total_releases,
                SUM(total_expenses) AS total_expenses
            FROM cash_blotter
            WHERE blotter_date BETWEEN ? AND ? AND status = ?
        ";
        
        $result = $this->db->single($sql, [$startDate, $endDate, CashBlotterModel::$STATUS_FINALIZED]);
        
        // Get the opening balance from the earliest finalized blotter in the range
        $openingBalanceSql = "
            SELECT opening_balance
            FROM cash_blotter
            WHERE blotter_date = ? AND status = ?
            ORDER BY blotter_date ASC LIMIT 1
        ";
        $openingBlotter = (new CashBlotterModel())->getBlotterByDate($startDate);
        $openingBalance = $openingBlotter['opening_balance'] ?? 0.00;

        // Get the closing balance from the latest finalized blotter in the range
        $closingBlotter = (new CashBlotterModel())->getBlotterByDate($endDate);
        $closingBalance = $closingBlotter['closing_balance'] ?? 0.00;

        $totalCollections = $result['total_collections'] ?? 0;
        $totalReleases = $result['total_releases'] ?? 0;
        $totalExpenses = $result['total_expenses'] ?? 0;

        $netFlow = $totalCollections - $totalReleases - $totalExpenses;

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'opening_balance' => (float)$openingBalance,
            'closing_balance' => (float)$closingBalance,
            'total_collections' => (float)$totalCollections,
            'total_releases' => (float)$totalReleases,
            'total_expenses' => (float)$totalExpenses,
            'net_flow' => (float)$netFlow
        ];
    }
    
    /**
     * Retrieves payment data for detailed transaction reporting in the given period (FR-004 Detail).
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array Detailed list of payments.
     */
    public function getPaymentTransactionReport($startDate, $endDate) {
        $sql = "
            SELECT 
                p.id,
                p.amount,
                p.payment_date,
                l.id AS loan_id,
                l.principal,
                c.name AS client_name,
                u.name AS staff_name
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            JOIN clients c ON l.client_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE DATE(p.payment_date) BETWEEN ? AND ?
            ORDER BY p.payment_date DESC
        ";
        
        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }
}