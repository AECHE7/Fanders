<?php
/**
 * Enhanced Overdue Payment Service
 * Provides accurate overdue payment tracking based on payment schedules
 */

require_once BASE_PATH . '/app/core/BaseService.php';
require_once BASE_PATH . '/app/services/LoanCalculationService.php';

class OverduePaymentService extends BaseService {
    private $loanModel;
    private $paymentModel;
    private $clientModel;
    private $loanCalculationService;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->paymentModel = new PaymentModel();
        $this->clientModel = new ClientModel();
        $this->loanCalculationService = new LoanCalculationService();
    }

    /**
     * Get comprehensive overdue payment analysis
     * This method provides accurate overdue tracking based on payment schedules
     */
    public function getOverdueAnalysis($filters = []) {
        // First get all active loans with their payment information
        $query = "SELECT
            l.id,
            l.id as loan_number,
            l.client_id,
            c.name as client_name,
            c.email as client_email,
            c.phone_number as phone,
            c.address,
            l.principal as principal_amount,
            l.total_loan_amount as total_amount,
            l.term_weeks,
            l.disbursement_date,
            l.completion_date as expected_completion,
            l.status,
            l.created_at as application_date,
            COALESCE(p.total_paid, 0) as total_paid,
            COALESCE(p.payment_count, 0) as payments_made,
            COALESCE(p.last_payment_date, null) as last_payment_date,
            (l.total_loan_amount - COALESCE(p.total_paid, 0)) as remaining_balance
        FROM loans l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN (
            SELECT 
                loan_id, 
                SUM(amount) AS total_paid,
                COUNT(*) AS payment_count,
                MAX(payment_date) AS last_payment_date
            FROM payments
            GROUP BY loan_id
        ) p ON l.id = p.loan_id
        WHERE LOWER(l.status) = 'active'
          AND l.disbursement_date IS NOT NULL
          AND (l.total_loan_amount - COALESCE(p.total_paid, 0)) > 0";

        $params = [];
        
        // Apply basic filters
        if (!empty($filters['client_id'])) {
            $query .= " AND l.client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (!empty($filters['min_amount'])) {
            $query .= " AND (l.total_loan_amount - COALESCE(p.total_paid, 0)) >= ?";
            $params[] = $filters['min_amount'];
        }

        $query .= " ORDER BY l.disbursement_date ASC";

        $loans = $this->db->resultSet($query, $params);
        
        $overdueLoans = [];
        $currentDate = new DateTime();
        
        foreach ($loans as $loan) {
            // Calculate expected payment schedule
            $loanAnalysis = $this->analyzeLoanPaymentStatus($loan, $currentDate);
            
            // Only include loans that are actually overdue
            if ($loanAnalysis['is_overdue']) {
                // Apply overdue-specific filters
                if (!empty($filters['days_overdue']) && $loanAnalysis['days_overdue'] < $filters['days_overdue']) {
                    continue;
                }
                
                if (!empty($filters['severity'])) {
                    if ($filters['severity'] !== $loanAnalysis['severity']) {
                        continue;
                    }
                }
                
                $overdueLoans[] = $loanAnalysis;
            }
        }
        
        // Sort by severity and days overdue
        usort($overdueLoans, function($a, $b) {
            $severityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $aSeverity = $severityOrder[$a['severity']] ?? 1;
            $bSeverity = $severityOrder[$b['severity']] ?? 1;
            
            if ($aSeverity === $bSeverity) {
                return $b['days_overdue'] - $a['days_overdue'];
            }
            return $bSeverity - $aSeverity;
        });
        
        return $overdueLoans;
    }

    /**
     * Analyze individual loan payment status
     */
    private function analyzeLoanPaymentStatus($loan, $currentDate) {
        $disbursementDate = new DateTime($loan['disbursement_date']);
        $termWeeks = (int)$loan['term_weeks'];
        $totalLoanAmount = (float)$loan['total_amount'];
        $totalPaid = (float)$loan['total_paid'];
        $paymentsMade = (int)$loan['payments_made'];
        
        // Calculate expected weekly payment
        $expectedWeeklyPayment = $totalLoanAmount / $termWeeks;
        
        // Calculate how many weeks have passed since disbursement
        $weeksSinceDisbursement = $disbursementDate->diff($currentDate)->days / 7;
        $weeksSinceDisbursement = floor($weeksSinceDisbursement);
        
        // Calculate expected payments made and amount by now
        $expectedPaymentsMade = min($weeksSinceDisbursement, $termWeeks);
        $expectedAmountPaid = $expectedPaymentsMade * $expectedWeeklyPayment;
        
        // Calculate overdue amounts
        $paymentShortfall = max(0, $expectedAmountPaid - $totalPaid);
        $paymentsShortfall = max(0, $expectedPaymentsMade - $paymentsMade);
        
        // Determine if loan is overdue (allowing 7-day grace period)
        $gracePeriodDays = 7;
        $isOverdue = $paymentShortfall > ($expectedWeeklyPayment * 0.1) && 
                     $weeksSinceDisbursement > 0 &&
                     $disbursementDate->diff($currentDate)->days > $gracePeriodDays;
        
        // Calculate days overdue (days since expected payment date)
        $daysOverdue = 0;
        if ($isOverdue) {
            $expectedPaymentDate = clone $disbursementDate;
            $expectedPaymentDate->add(new DateInterval('P' . ($expectedPaymentsMade * 7) . 'D'));
            $daysOverdue = max(0, $expectedPaymentDate->diff($currentDate)->days);
        }
        
        // Determine severity
        $severity = $this->calculateSeverity($daysOverdue, $paymentsShortfall, $paymentShortfall, $totalLoanAmount);
        
        // Calculate percentage paid
        $percentagePaid = ($totalLoanAmount > 0) ? round(($totalPaid / $totalLoanAmount) * 100, 1) : 0;
        
        // Calculate weeks behind
        $weeksBehind = $paymentsShortfall;
        
        // Last payment information
        $lastPaymentDate = $loan['last_payment_date'] ? new DateTime($loan['last_payment_date']) : null;
        $daysSinceLastPayment = $lastPaymentDate ? $lastPaymentDate->diff($currentDate)->days : null;
        
        return array_merge($loan, [
            'is_overdue' => $isOverdue,
            'days_overdue' => $daysOverdue,
            'severity' => $severity,
            'severity_label' => $this->getSeverityLabel($severity),
            'severity_class' => $this->getSeverityClass($severity),
            'expected_weekly_payment' => $expectedWeeklyPayment,
            'expected_payments_made' => $expectedPaymentsMade,
            'expected_amount_paid' => $expectedAmountPaid,
            'payment_shortfall' => $paymentShortfall,
            'payments_shortfall' => $paymentsShortfall,
            'percentage_paid' => $percentagePaid,
            'weeks_behind' => $weeksBehind,
            'weeks_since_disbursement' => $weeksSinceDisbursement,
            'days_since_last_payment' => $daysSinceLastPayment,
            'next_expected_payment_date' => $this->calculateNextPaymentDate($disbursementDate, $paymentsMade + 1),
            'remaining_balance' => $loan['remaining_balance']
        ]);
    }

    /**
     * Calculate severity based on multiple factors
     */
    private function calculateSeverity($daysOverdue, $paymentsShortfall, $paymentShortfall, $totalLoanAmount) {
        // Critical: Very long overdue or large amount
        if ($daysOverdue > 60 || $paymentShortfall > ($totalLoanAmount * 0.5)) {
            return 'critical';
        }
        
        // High: Significantly overdue
        if ($daysOverdue > 30 || $paymentsShortfall > 4 || $paymentShortfall > ($totalLoanAmount * 0.25)) {
            return 'high';
        }
        
        // Medium: Moderately overdue
        if ($daysOverdue > 14 || $paymentsShortfall > 2) {
            return 'medium';
        }
        
        // Low: Recently overdue
        return 'low';
    }

    /**
     * Get severity label
     */
    private function getSeverityLabel($severity) {
        $labels = [
            'critical' => 'Critical - Immediate Action Required',
            'high' => 'High Priority - Contact Client',
            'medium' => 'Moderate - Follow Up Soon',
            'low' => 'Recently Overdue - Monitor'
        ];
        
        return $labels[$severity] ?? 'Unknown';
    }

    /**
     * Get severity CSS class
     */
    private function getSeverityClass($severity) {
        $classes = [
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary'
        ];
        
        return $classes[$severity] ?? 'secondary';
    }

    /**
     * Calculate next expected payment date
     */
    private function calculateNextPaymentDate($disbursementDate, $weekNumber) {
        $nextPaymentDate = clone $disbursementDate;
        $nextPaymentDate->add(new DateInterval('P' . (($weekNumber - 1) * 7) . 'D'));
        return $nextPaymentDate;
    }

    /**
     * Get overdue statistics
     */
    public function getOverdueStatistics($overdueLoans = null) {
        if ($overdueLoans === null) {
            $overdueLoans = $this->getOverdueAnalysis();
        }

        $totalOverdue = count($overdueLoans);
        $totalOverdueAmount = array_sum(array_column($overdueLoans, 'payment_shortfall'));
        $totalRemainingBalance = array_sum(array_column($overdueLoans, 'remaining_balance'));
        $averageDaysOverdue = $totalOverdue > 0 ? 
            round(array_sum(array_column($overdueLoans, 'days_overdue')) / $totalOverdue, 1) : 0;

        // Severity breakdown
        $severityStats = [
            'critical' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'critical')),
            'high' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'high')),
            'medium' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'medium')),
            'low' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'low'))
        ];

        // Calculate collection rate
        $totalExpectedPayments = array_sum(array_column($overdueLoans, 'expected_amount_paid'));
        $totalActualPayments = array_sum(array_column($overdueLoans, 'total_paid'));
        $collectionRate = $totalExpectedPayments > 0 ? 
            round(($totalActualPayments / $totalExpectedPayments) * 100, 1) : 100;

        return [
            'total_overdue' => $totalOverdue,
            'total_overdue_amount' => $totalOverdueAmount,
            'total_remaining_balance' => $totalRemainingBalance,
            'average_days_overdue' => $averageDaysOverdue,
            'severity_stats' => $severityStats,
            'collection_rate' => $collectionRate,
            'total_expected_payments' => $totalExpectedPayments,
            'total_actual_payments' => $totalActualPayments
        ];
    }

    /**
     * Export overdue loans data for CSV
     */
    public function exportOverdueLoansCSV($overdueLoans) {
        $csvData = [];
        $csvData[] = [
            'Loan #', 'Client Name', 'Phone', 'Email', 'Principal', 'Total Amount',
            'Total Paid', 'Remaining Balance', 'Expected Weekly', 'Payments Made',
            'Expected Payments', 'Payment Shortfall', 'Days Overdue', 'Weeks Behind',
            'Severity', 'Percentage Paid', 'Last Payment', 'Disbursement Date'
        ];

        foreach ($overdueLoans as $loan) {
            $csvData[] = [
                $loan['loan_number'],
                $loan['client_name'],
                $loan['phone'] ?? '',
                $loan['client_email'] ?? '',
                number_format($loan['principal_amount'], 2),
                number_format($loan['total_amount'], 2),
                number_format($loan['total_paid'], 2),
                number_format($loan['remaining_balance'], 2),
                number_format($loan['expected_weekly_payment'], 2),
                $loan['payments_made'],
                $loan['expected_payments_made'],
                number_format($loan['payment_shortfall'], 2),
                $loan['days_overdue'],
                number_format($loan['weeks_behind'], 1),
                $loan['severity_label'],
                $loan['percentage_paid'] . '%',
                $loan['last_payment_date'] ?? 'Never',
                $loan['disbursement_date']
            ];
        }

        return $csvData;
    }
}