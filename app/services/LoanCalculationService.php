<?php
/**
 * LoanCalculationService - Handles all loan calculation logic for Fanders Microfinance
 * Implements the core business rules (FR-002, FR-003, FR-009).
 */
require_once __DIR__ . '/../core/BaseService.php';

class LoanCalculationService extends BaseService {
    // Fixed Business Constants for Fanders Microfinance
    const INTEREST_RATE = 0.06;         // 6% fixed interest rate
    const INSURANCE_FEE = 425.00;       // Fixed ₱425 insurance fee per loan
    const SAVINGS_RATE = 0.01;          // 1% savings deduction rate
    
    // Loan Amount Limits
    const MIN_LOAN_AMOUNT = 5000.00;    // Minimum ₱5,000 loan amount
    const MAX_LOAN_AMOUNT = 50000.00;   // Maximum ₱50,000 loan amount
    
    // Default Terms (can be overridden by user input)
    const DEFAULT_LOAN_TERM_MONTHS = 4; // Default 4 months
    const DEFAULT_WEEKS_IN_LOAN = 17;   // Default 17 weeks

    /**
     * Calculate loan details based on principal amount and term
     * @param float $principalAmount The loan principal amount
     * @param int $termWeeks The loan term in weeks (flexible, user input)
     * @param int $termMonths The loan term in months (for interest calculation, defaults to 4)
     * @return array|false Loan calculation details on success.
     */
    public function calculateLoan($principalAmount, $termWeeks, $termMonths) {
        // Use default terms if not provided
        if ($termWeeks === null) {
            $termWeeks = self::DEFAULT_WEEKS_IN_LOAN;
        }
        
        if ($termMonths === null) {
            $termMonths = self::DEFAULT_LOAN_TERM_MONTHS;
        }

        // Validation check (uses BaseService validation)
        if (!$this->validate(['principal_amount' => $principalAmount, 'term_weeks' => $termWeeks], [
            'principal_amount' => 'required|positive|numeric',
            'term_weeks' => 'required|numeric|min:4|max:52'
        ])) {
            return false;
        }

        // Additional validation for loan amount limits
        if (!$this->validateLoanAmount($principalAmount)) {
            return false;
        }

        // Calculate interest based on specified formula: P x 5% x term_months
        $totalInterest = ($principalAmount * self::INTEREST_RATE) * $termMonths;

        // Calculate savings deduction (1% of principal)
        $savingsDeduction = $principalAmount * self::SAVINGS_RATE;

        // Calculate total amount (Principal + Interest + Insurance + Savings)
        $totalAmount = $principalAmount + $totalInterest + self::INSURANCE_FEE;

        // Calculate weekly payment (total amount divided by term weeks)
        $weeklyPayment = round(($totalAmount / $termWeeks) + $savingsDeduction, 2);

        // --- Rounding Adjustment for Weekly Payment ---
        // Calculate the difference due to rounding and add it to the final weekly payment
        $totalRoundedPayment = $weeklyPayment * $termWeeks;
        $roundingDifference = $totalAmount - $totalRoundedPayment;

        return [
            'principal' => $principalAmount,
            'interest_rate' => self::INTEREST_RATE,
            'term_weeks' => $termWeeks,
            'term_months' => $termMonths,
            'total_interest' => round($totalInterest, 2),
            'insurance_fee' => self::INSURANCE_FEE,
            'savings_deduction' => round($savingsDeduction, 2),
            'total_loan_amount' => round($totalAmount, 2),
            'weekly_payment_base' => $weeklyPayment,
            'rounding_difference' => round($roundingDifference, 2),
            'payment_schedule' => $this->generatePaymentSchedule($principalAmount, $totalInterest, self::INSURANCE_FEE, $savingsDeduction, $termWeeks)
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
        
        if ($principal < self::MIN_LOAN_AMOUNT) {
            $this->setErrorMessage('Loan amount must be at least ₱' . number_format(self::MIN_LOAN_AMOUNT, 0) . '.');
            return false;
        }
        if ($principal > self::MAX_LOAN_AMOUNT) {
            $this->setErrorMessage('Loan amount cannot exceed ₱' . number_format(self::MAX_LOAN_AMOUNT, 0) . '.');
            return false;
        }
        return true;
    }

    /**
     * Generate detailed payment schedule for variable weeks with component breakdown.
     * This ensures the total of all payments exactly equals the total loan amount.
     */
    private function generatePaymentSchedule($principal, $totalInterest, $insuranceFee, $savingsDeduction, $termWeeks) {
        $schedule = [];
        $totalAmount = $principal + $totalInterest + $insuranceFee + $savingsDeduction;
        $weeklyPayment = round($totalAmount / $termWeeks, 2);

        // Pre-calculate the total of the rounded weekly payments
        $totalRoundedPayment = $weeklyPayment * $termWeeks;
        $roundingAdjustment = round($totalAmount - $totalRoundedPayment, 2); // The difference we need to correct

        // Calculate weekly component breakdown (using initial principal/interest for consistency)
        $principalPerWeek = $principal / $termWeeks;
        $interestPerWeek = $totalInterest / $termWeeks;
        $insurancePerWeek = $insuranceFee / $termWeeks;
        $savingsPerWeek = $savingsDeduction / $termWeeks;

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
            $paidSavings = round($savingsPerWeek, 2);

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
                'insurance_payment' => round($paidInsurance, 2),
                'savings_payment' => round($paidSavings, 2)
            ];

            $runningPrincipal -= round($paidPrincipal, 2);
        }

        return $schedule;
    }

    /**
     * Calculate loan with conversational term input
     * @param float $principalAmount The loan principal amount
     * @param string $conversationalTerm Term like "4 months", "17 weeks", etc.
     * @return array|false Loan calculation details on success.
     */
    public function calculateLoanFromConversationalTerm($principalAmount, $conversationalTerm) {
        $parsedTerm = $this->parseConversationalTerm($conversationalTerm);
        
        if (!$parsedTerm) {
            $this->setErrorMessage('Invalid loan term format. Use formats like "4 months", "17 weeks", etc.');
            return false;
        }

        return $this->calculateLoan(
            $principalAmount, 
            $parsedTerm['weeks'], 
            $parsedTerm['months']
        );
    }

    /**
     * Parse conversational loan terms into weeks and months
     * @param string $term Conversational term like "4 months", "17 weeks"
     * @return array|false Array with 'weeks' and 'months' or false if invalid
     */
    private function parseConversationalTerm($term) {
        $term = strtolower(trim($term));
        
        // Handle "X months" format
        if (preg_match('/^(\d+(?:\.\d+)?)\s*months?$/', $term, $matches)) {
            $months = (float)$matches[1];
            $weeks = round($months * 4.33); // Average weeks per month
            return [
                'months' => $months,
                'weeks' => $weeks
            ];
        }
        
        // Handle "X weeks" format  
        if (preg_match('/^(\d+)\s*weeks?$/', $term, $matches)) {
            $weeks = (int)$matches[1];
            $months = round($weeks / 4.33, 1); // Convert to months for interest calculation
            return [
                'months' => $months,
                'weeks' => $weeks
            ];
        }

        return false;
    }

    /**
     * Get common loan term options with conversational descriptions
     * @return array Array of term options
     */
    public function getCommonLoanTerms() {
        return [
            [
                'value' => '17',
                'display' => '17 weeks (4+ months)',
                'weeks' => 17,
                'months' => 4,
                'description' => 'Standard loan term'
            ],
            [
                'value' => '13',
                'display' => '13 weeks (3 months)',
                'weeks' => 13,
                'months' => 3,
                'description' => 'Short-term loan'
            ],
            [
                'value' => '26',
                'display' => '26 weeks (6 months)',
                'weeks' => 26,
                'months' => 6,
                'description' => 'Extended loan term'
            ],
            [
                'value' => '39',
                'display' => '39 weeks (9 months)',
                'weeks' => 39,
                'months' => 9,
                'description' => 'Long-term loan'
            ],
            [
                'value' => '52',
                'display' => '52 weeks (1 year)',
                'weeks' => 52,
                'months' => 12,
                'description' => 'Maximum loan term'
            ]
        ];
    }

    /**
     * Get loan calculation summary for display
     * @param array $calculation Result from calculateLoan method
     * @return array|null Formatted summary for UI display
     */
    public function formatCalculationSummary($calculation) {
        if (!$calculation || !is_array($calculation)) {
            return null;
        }

        return [
            'loan_amount' => [
                'label' => 'Loan Amount',
                'value' => '₱' . number_format($calculation['principal'], 2),
                'raw' => $calculation['principal']
            ],
            'interest' => [
                'label' => 'Interest (' . ($calculation['interest_rate'] * 100) . '% x ' . $calculation['term_months'] . ' months)',
                'value' => '₱' . number_format($calculation['total_interest'], 2),
                'raw' => $calculation['total_interest']
            ],
            'insurance' => [
                'label' => 'Insurance Fee',
                'value' => '₱' . number_format($calculation['insurance_fee'], 2),
                'raw' => $calculation['insurance_fee']
            ],
            'savings' => [
                'label' => 'Savings Deduction (1%)',
                'value' => '₱' . number_format($calculation['savings_deduction'], 2),
                'raw' => $calculation['savings_deduction']
            ],
            'total_amount' => [
                'label' => 'Total Loan Amount',
                'value' => '₱' . number_format($calculation['total_loan_amount'], 2),
                'raw' => $calculation['total_loan_amount'],
                'class' => 'fw-bold text-primary'
            ],
            'weekly_payment' => [
                'label' => 'Weekly Payment (' . $calculation['term_weeks'] . ' weeks)',
                'value' => '₱' . number_format($calculation['weekly_payment_base'], 2),
                'raw' => $calculation['weekly_payment_base'],
                'class' => 'fw-bold text-success'
            ],
            'term_info' => [
                'weeks' => $calculation['term_weeks'],
                'months' => $calculation['term_months'],
                'display' => $calculation['term_weeks'] . ' weeks (' . $calculation['term_months'] . '+ months)'
            ]
        ];
    }

    /**
     * Get loan amount limits for display in forms
     * @return array Array with min and max loan amounts
     */
    public function getLoanAmountLimits() {
        return [
            'minimum' => self::MIN_LOAN_AMOUNT,
            'maximum' => self::MAX_LOAN_AMOUNT,
            'min_formatted' => '₱' . number_format(self::MIN_LOAN_AMOUNT, 0),
            'max_formatted' => '₱' . number_format(self::MAX_LOAN_AMOUNT, 0),
            'range_display' => '₱' . number_format(self::MIN_LOAN_AMOUNT, 0) . ' - ₱' . number_format(self::MAX_LOAN_AMOUNT, 0)
        ];
    }
}
