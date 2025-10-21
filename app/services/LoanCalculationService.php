<?php
/**
 * LoanCalculationService - Handles all loan calculation logic for Fanders Microfinance
 * Implements the core business rules (FR-002, FR-003, FR-009).
 */
require_once __DIR__ . '/../core/BaseService.php';

class LoanCalculationService extends BaseService {
    // Constants based on Fanders requirements
    const MONTHLY_INTEREST_RATE = 0.05; // 5% monthly interest
    const LOAN_TERM_MONTHS = 4;         // Always 4 months
    const INSURANCE_FEE = 425.00;       // Fixed ₱425 insurance fee
    const WEEKS_IN_LOAN = 17;           // 17 weeks total

    /**
     * Calculate loan details based on principal amount and term
     * @param float $principalAmount The loan principal amount
     * @param int $termWeeks The loan term in weeks (4-52)
     * @return array|false Loan calculation details on success.
     */
    public function calculateLoan($principalAmount, $termWeeks = null) {
        // Use default term if not provided
        if ($termWeeks === null) {
            $termWeeks = self::WEEKS_IN_LOAN;
        }

        // Validation check (uses BaseService validation)
        if (!$this->validate(['principal_amount' => $principalAmount, 'term_weeks' => $termWeeks], [
            'principal_amount' => 'required|positive|numeric',
            'term_weeks' => 'required|numeric|min:4|max:52'
        ])) {
            return false;
        }

        // Calculate interest (P x 0.05 x 4) - fixed 4 months regardless of term
        $totalInterest = $principalAmount * self::MONTHLY_INTEREST_RATE * self::LOAN_TERM_MONTHS;

        // Calculate total amount (Principal + Interest + Insurance)
        $totalAmount = $principalAmount + $totalInterest + self::INSURANCE_FEE;

        // Calculate weekly payment (total amount divided by term weeks)
        $weeklyPayment = round($totalAmount / $termWeeks, 2);

        // --- Rounding Adjustment for Weekly Payment ---
        // Calculate the difference due to rounding and add it to the final weekly payment
        $totalRoundedPayment = $weeklyPayment * $termWeeks;
        $roundingDifference = $totalAmount - $totalRoundedPayment;

        // The adjustment should be added to the total payment,
        // but for initial records, we store the rounded payment.

        return [
            'principal' => $principalAmount,
            'interest_rate' => self::MONTHLY_INTEREST_RATE,
            'term_weeks' => $termWeeks,
            'total_interest' => round($totalInterest, 2),
            'insurance_fee' => self::INSURANCE_FEE,
            'total_loan_amount' => round($totalAmount, 2),
            'weekly_payment_base' => $weeklyPayment,
            'payment_schedule' => $this->generatePaymentSchedule($principalAmount, $totalInterest, self::INSURANCE_FEE, $termWeeks)
        ];
    }

    /**
     * Validate loan amount against business rules.
     * @param float $principal The loan principal amount
     * @return bool True if valid, false otherwise
     */
    public function validateLoanAmount($principal) {
        // Ensure it's a number
        if (!is_numeric($principal)) {
            $this->setErrorMessage('Loan amount must be a valid number.');
            return false;
        }
        
        $principal = (float)$principal;
        
        if ($principal < 1000) {
            $this->setErrorMessage('Loan amount must be at least ₱1,000.');
            return false;
        }
        if ($principal > 50000) {
            $this->setErrorMessage('Loan amount cannot exceed ₱50,000.');
            return false;
        }
        return true;
    }

    /**
     * Generate detailed payment schedule for variable weeks with component breakdown.
     * This ensures the total of all payments exactly equals the total loan amount.
     */
    private function generatePaymentSchedule($principal, $totalInterest, $insuranceFee, $termWeeks) {
        $schedule = [];
        $totalAmount = $principal + $totalInterest + $insuranceFee;
        $weeklyPayment = round($totalAmount / $termWeeks, 2);

        // Pre-calculate the total of the rounded weekly payments
        $totalRoundedPayment = $weeklyPayment * $termWeeks;
        $roundingAdjustment = round($totalAmount - $totalRoundedPayment, 2); // The difference we need to correct

        // Calculate weekly component breakdown (using initial principal/interest for consistency)
        $principalPerWeek = $principal / $termWeeks;
        $interestPerWeek = $totalInterest / $termWeeks;
        $insurancePerWeek = $insuranceFee / $termWeeks;

        $runningPrincipal = $principal;

        for ($week = 1; $week <= $termWeeks; $week++) {
            $payment = $weeklyPayment;

            // Apply total rounding difference to the LAST payment
            if ($week == $termWeeks) {
                $payment += $roundingAdjustment;
            }

            // Simple amortization logic (Principal is paid down linearly across weeks)
            $paidPrincipal = round($principalPerWeek, 2);
            $paidInterest = round($interestPerWeek, 2);
            $paidInsurance = round($insurancePerWeek, 2);

            // Adjust for remaining cents on the last week if a component sum doesn't perfectly match the total component.
            // This ensures all components are fully paid by the last week.
            if ($week == $termWeeks) {
                // Ensure remaining balance is zeroed out by adjusting the last principal payment
                $paidPrincipal = $runningPrincipal;
                // Note: The total payment (payment) already includes the total rounding difference.
            }

            $schedule[] = [
                'week' => $week,
                'expected_payment' => round($payment, 2),
                'principal_payment' => round($paidPrincipal, 2),
                'interest_payment' => round($paidInterest, 2),
                'insurance_payment' => round($paidInsurance, 2)
            ];

            $runningPrincipal -= round($paidPrincipal, 2);
        }

        return $schedule;
    }
}
