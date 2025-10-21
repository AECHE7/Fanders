<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\LoanModel;
use PDO;

class LoanModelTest extends TestCase
{
    private $pdo;
    private $loanModel;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create loans table
        $this->pdo->exec("
            CREATE TABLE loans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                principal_amount DECIMAL(10,2) NOT NULL,
                interest_rate DECIMAL(5,2) NOT NULL,
                term_months INTEGER NOT NULL,
                monthly_payment DECIMAL(10,2) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                disbursement_date DATE,
                maturity_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id)
            )
        ");

        $this->loanModel = new LoanModel($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testCreateLoan()
    {
        $loanData = [
            'client_id' => 1,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12,
            'monthly_payment' => 850.00,
            'total_amount' => 10200.00
        ];

        $loanId = $this->loanModel->create($loanData);

        $this->assertIsInt($loanId);
        $this->assertGreaterThan(0, $loanId);
    }

    public function testFindById()
    {
        // Create a test loan
        $loanData = [
            'client_id' => 1,
            'principal_amount' => 5000.00,
            'interest_rate' => 4.50,
            'term_months' => 6,
            'monthly_payment' => 425.00,
            'total_amount' => 5100.00
        ];

        $loanId = $this->loanModel->create($loanData);
        $loan = $this->loanModel->findById($loanId);

        $this->assertIsArray($loan);
        $this->assertEquals($loanId, $loan['id']);
        $this->assertEquals(5000.00, $loan['principal_amount']);
        $this->assertEquals(4.50, $loan['interest_rate']);
    }

    public function testUpdateLoan()
    {
        // Create a test loan
        $loanData = [
            'client_id' => 1,
            'principal_amount' => 8000.00,
            'interest_rate' => 5.50,
            'term_months' => 10,
            'monthly_payment' => 680.00,
            'total_amount' => 8160.00,
            'status' => 'pending'
        ];

        $loanId = $this->loanModel->create($loanData);

        // Update the loan
        $updateData = [
            'status' => 'approved',
            'disbursement_date' => '2025-01-15'
        ];

        $result = $this->loanModel->update($loanId, $updateData);

        $this->assertTrue($result);

        // Verify the update
        $updatedLoan = $this->loanModel->findById($loanId);
        $this->assertEquals('approved', $updatedLoan['status']);
        $this->assertEquals('2025-01-15', $updatedLoan['disbursement_date']);
    }

    public function testGetLoansByClientId()
    {
        // Create loans for client 1
        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 3000.00,
            'interest_rate' => 5.00,
            'term_months' => 6,
            'monthly_payment' => 255.00,
            'total_amount' => 3060.00
        ]);

        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 4000.00,
            'interest_rate' => 5.00,
            'term_months' => 8,
            'monthly_payment' => 340.00,
            'total_amount' => 4080.00
        ]);

        // Create loan for different client
        $this->loanModel->create([
            'client_id' => 2,
            'principal_amount' => 2000.00,
            'interest_rate' => 5.00,
            'term_months' => 4,
            'monthly_payment' => 170.00,
            'total_amount' => 2040.00
        ]);

        $clientLoans = $this->loanModel->getLoansByClientId(1);

        $this->assertCount(2, $clientLoans);
        $this->assertEquals(1, $clientLoans[0]['client_id']);
        $this->assertEquals(1, $clientLoans[1]['client_id']);
    }

    public function testGetActiveLoans()
    {
        // Create loans with different statuses
        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 5000.00,
            'interest_rate' => 5.00,
            'term_months' => 12,
            'monthly_payment' => 425.00,
            'total_amount' => 5100.00,
            'status' => 'active'
        ]);

        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 3000.00,
            'interest_rate' => 5.00,
            'term_months' => 6,
            'monthly_payment' => 255.00,
            'total_amount' => 3060.00,
            'status' => 'completed'
        ]);

        $this->loanModel->create([
            'client_id' => 2,
            'principal_amount' => 4000.00,
            'interest_rate' => 5.00,
            'term_months' => 8,
            'monthly_payment' => 340.00,
            'total_amount' => 4080.00,
            'status' => 'pending'
        ]);

        $activeLoans = $this->loanModel->getActiveLoans();

        $this->assertCount(1, $activeLoans);
        $this->assertEquals('active', $activeLoans[0]['status']);
        $this->assertEquals(5000.00, $activeLoans[0]['principal_amount']);
    }

    public function testCalculateLoanSummary()
    {
        // Create test loans
        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12,
            'monthly_payment' => 850.00,
            'total_amount' => 10200.00,
            'status' => 'active'
        ]);

        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 5000.00,
            'interest_rate' => 5.00,
            'term_months' => 6,
            'monthly_payment' => 425.00,
            'total_amount' => 5100.00,
            'status' => 'completed'
        ]);

        $summary = $this->loanModel->calculateLoanSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_loans', $summary);
        $this->assertArrayHasKey('active_loans', $summary);
        $this->assertArrayHasKey('total_principal', $summary);
        $this->assertEquals(2, $summary['total_loans']);
        $this->assertEquals(1, $summary['active_loans']);
        $this->assertEquals(15000.00, $summary['total_principal']);
    }

    public function testGetLoansByStatus()
    {
        // Create loans with different statuses
        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12,
            'monthly_payment' => 850.00,
            'total_amount' => 10200.00,
            'status' => 'active'
        ]);

        $this->loanModel->create([
            'client_id' => 2,
            'principal_amount' => 5000.00,
            'interest_rate' => 5.00,
            'term_months' => 6,
            'monthly_payment' => 425.00,
            'total_amount' => 5100.00,
            'status' => 'completed'
        ]);

        $activeLoans = $this->loanModel->getLoansByStatus('active');
        $completedLoans = $this->loanModel->getLoansByStatus('completed');

        $this->assertCount(1, $activeLoans);
        $this->assertCount(1, $completedLoans);
        $this->assertEquals('active', $activeLoans[0]['status']);
        $this->assertEquals('completed', $completedLoans[0]['status']);
    }

    public function testGetOverdueLoans()
    {
        // Create a loan with past maturity date
        $this->loanModel->create([
            'client_id' => 1,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12,
            'monthly_payment' => 850.00,
            'total_amount' => 10200.00,
            'status' => 'active',
            'maturity_date' => '2024-01-01' // Past date
        ]);

        // Create a loan with future maturity date
        $this->loanModel->create([
            'client_id' => 2,
            'principal_amount' => 5000.00,
            'interest_rate' => 5.00,
            'term_months' => 6,
            'monthly_payment' => 425.00,
            'total_amount' => 5100.00,
            'status' => 'active',
            'maturity_date' => '2026-01-01' // Future date
        ]);

        $overdueLoans = $this->loanModel->getOverdueLoans();

        $this->assertCount(1, $overdueLoans);
        $this->assertEquals('2024-01-01', $overdueLoans[0]['maturity_date']);
    }
}
