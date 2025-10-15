<?php
/**
 * LoanCalculationService - Handles all loan calculation logic for Fanders Microfinance
 * Based on the requirements: 5% monthly interest, 4-month term, 17-week payments, ₱425 insurance
 */
require_once __DIR__ . '/../core/BaseService.php';

class LoanCalculationService extends BaseService {
    // Constants based on Fanders requirements
    const MONTHLY_INTEREST_RATE = 0.05; // 5% monthly interest
    const LOAN_TERM_MONTHS = 4; // Always 4 months
    const INSURANCE_FEE = 425.00; // Fixed ₱425 insurance fee
    const WEEKS_IN_LOAN = 17; // 17 weeks total

    /**
     * Calculate loan details based on principal amount
     * @param float $principalAmount The loan principal amount
     * @return array Loan calculation details
     */
    public function calculateLoan($principalAmount) {
        // Validate principal amount
        if ($principalAmount <= 0) {
            $this->setErrorMessage('Principal amount must be greater than zero.');
            return false;
        }

        // Calculate interest (5% per month for 4 months)
        $totalInterest = $principalAmount * self::MONTHLY_INTEREST_RATE * self::LOAN_TERM_MONTHS;

        // Calculate total amount (Principal + Interest + Insurance)
        $totalAmount = $principalAmount + $totalInterest + self::INSURANCE_FEE;

        // Calculate weekly payment (total amount divided by 17 weeks)
        $weeklyPayment = round($totalAmount / self::WEEKS_IN_LOAN, 2);

        // Calculate breakdown per week
        $principalPerWeek = round($principalAmount / self::WEEKS_IN_LOAN, 2);
        $interestPerWeek = round($totalInterest / self::WEEKS_IN_LOAN, 2);
        $insurancePerWeek = round(self::INSURANCE_FEE / self::WEEKS_IN_LOAN, 2);

        return [
            'principal_amount' => $principalAmount,
            'interest_rate' => self::MONTHLY_INTEREST_RATE,
            'loan_term_months' => self::LOAN_TERM_MONTHS,
            'total_interest' => $totalInterest,
            'insurance_fee' => self::INSURANCE_FEE,
            'total_amount' => $totalAmount,
            'weekly_payment' => $weeklyPayment,
            'weeks_total' => self::WEEKS_IN_LOAN,
            'breakdown' => [
                'principal_per_week' => $principalPerWeek,
                'interest_per_week' => $interestPerWeek,
                'insurance_per_week' => $insurancePerWeek
            ],
            'payment_schedule' => $this->generatePaymentSchedule($principalAmount, $totalInterest, self::INSURANCE_FEE)
        ];
    }

    /**
     * Generate detailed payment schedule for 17 weeks
     * @param float $principal Principal amount
     * @param float $totalInterest Total interest
     * @param float $insuranceFee Insurance fee
     * @return array Payment schedule
     */
    private function generatePaymentSchedule($principal, $totalInterest, $insuranceFee) {
        $schedule = [];
        $remainingPrincipal = $principal;
        $remainingInterest = $totalInterest;
        $remainingInsurance = $insuranceFee;

        for ($week = 1; $week <= self::WEEKS_IN_LOAN; $week++) {
            // Calculate amounts for this week
            $principalPayment = round($principal / self::WEEKS_IN_LOAN, 2);
            $interestPayment = round($totalInterest / self::WEEKS_IN_LOAN, 2);
            $insurancePayment = round($insuranceFee / self::WEEKS_IN_LOAN, 2);

            // Adjust for rounding on last payment
            if ($week == self::WEEKS_IN_LOAN) {
                $principalPayment = $remainingPrincipal;
                $interestPayment = $remainingInterest;
                $insurancePayment = $remainingInsurance;
            }

            $totalPayment = $principalPayment + $interestPayment + $insurancePayment;

            $schedule[] = [
                'week' => $week,
                'principal_payment' => $principalPayment,
                'interest_payment' => $interestPayment,
                'insurance_payment' => $insurancePayment,
                'total_payment' => $totalPayment,
                'remaining_principal' => max(0, $remainingPrincipal - $principalPayment),
                'remaining_interest' => max(0, $remainingInterest - $interestPayment),
                'remaining_insurance' => max(0, $remainingInsurance - $insurancePayment)
            ];

            // Update remaining amounts
            $remainingPrincipal -= $principalPayment;
            $remainingInterest -= $interestPayment;
            $remainingInsurance -= $insurancePayment;
        }

        return $schedule;
    }

    /**
     * Calculate remaining balance for a loan at a specific week
     * @param float $principalAmount Original principal
     * @param int $weeksPaid Number of weeks already paid
     * @return array Remaining balance details
     */
    public function calculateRemainingBalance($principalAmount, $weeksPaid) {
        $loanDetails = $this->calculateLoan($principalAmount);

        if (!$loanDetails) {
            return false;
        }

        $totalPaid = $weeksPaid * $loanDetails['weekly_payment'];
        $remainingWeeks = self::WEEKS_IN_LOAN - $weeksPaid;
        $remainingAmount = $remainingWeeks * $loanDetails['weekly_payment'];

        // Calculate remaining components
        $remainingPrincipal = $principalAmount - ($weeksPaid * $loanDetails['breakdown']['principal_per_week']);
        $remainingInterest = $loanDetails['total_interest'] - ($weeksPaid * $loanDetails['breakdown']['interest_per_week']);
        $remainingInsurance = self::INSURANCE_FEE - ($weeksPaid * $loanDetails['breakdown']['insurance_per_week']);

        return [
            'total_paid' => $totalPaid,
            'remaining_amount' => $remainingAmount,
            'remaining_weeks' => $remainingWeeks,
            'remaining_principal' => max(0, $remainingPrincipal),
            'remaining_interest' => max(0, $remainingInterest),
            'remaining_insurance' => max(0, $remainingInsurance),
            'next_payment_due' => $loanDetails['weekly_payment']
        ];
    }

    /**
     * Calculate penalty for late payments
     * @param float $weeklyPayment The regular weekly payment amount
     * @param int $daysLate Number of days late
     * @return float Penalty amount
     */
    public function calculateLatePaymentPenalty($weeklyPayment, $daysLate) {
        // Assuming 2% per day late penalty (this can be adjusted based on requirements)
        $penaltyRate = 0.02; // 2% per day
        return round($weeklyPayment * $penaltyRate * $daysLate, 2);
    }

    /**
     * Validate loan amount against business rules
     * @param float $amount Loan amount to validate
     * @return bool True if valid
     */
    public function validateLoanAmount($amount) {
        // Minimum loan amount (can be adjusted)
        $minAmount = 1000.00;

        // Maximum loan amount (can be adjusted)
        $maxAmount = 50000.00;

        if ($amount < $minAmount) {
            $this->setErrorMessage("Minimum loan amount is ₱" . number_format($minAmount, 2));
            return false;
        }

        if ($amount > $maxAmount) {
            $this->setErrorMessage("Maximum loan amount is ₱" . number_format($maxAmount, 2));
            return false;
        }

        return true;
    }

    /**
     * Calculate savings amount (if applicable)
     * @param float $weeklyPayment Weekly payment amount
     * @return float Savings amount (currently 10% of weekly payment)
     */
    public function calculateSavings($weeklyPayment) {
        // Assuming 10% of weekly payment goes to savings (can be adjusted)
        $savingsRate = 0.10;
        return round($weeklyPayment * $savingsRate, 2);
    }

    /**
     * Get loan summary for display
     * @param float $principalAmount Principal amount
     * @return array Formatted summary
     */
    public function getLoanSummary($principalAmount) {
        $calculation = $this->calculateLoan($principalAmount);

        if (!$calculation) {
            return false;
        }

        return [
            'loan_amount' => '₱' . number_format($calculation['principal_amount'], 2),
            'interest_rate' => ($calculation['interest_rate'] * 100) . '% per month',
            'loan_term' => $calculation['loan_term_months'] . ' months (' . $calculation['weeks_total'] . ' weeks)',
            'total_interest' => '₱' . number_format($calculation['total_interest'], 2),
            'insurance_fee' => '₱' . number_format($calculation['insurance_fee'], 2),
            'total_amount' => '₱' . number_format($calculation['total_amount'], 2),
            'weekly_payment' => '₱' . number_format($calculation['weekly_payment'], 2),
            'first_payment_due' => date('Y-m-d', strtotime('+1 week')),
            'maturity_date' => date('Y-m-d', strtotime('+' . $calculation['weeks_total'] . ' weeks'))
        ];
    }
}
