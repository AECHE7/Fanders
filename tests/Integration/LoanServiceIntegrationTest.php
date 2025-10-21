<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\LoanService;
use App\Services\ClientService;
use PDO;

class LoanServiceIntegrationTest extends TestCase
{
    private $pdo;
    private $loanService;
    private $clientService;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Load test schema
        $this->loadTestSchema();

        $this->loanService = new LoanService($this->pdo);
        $this->clientService = new ClientService($this->pdo);
    }

    private function loadTestSchema()
    {
        // Create clients table
        $this->pdo->exec("
            CREATE TABLE clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                phone_number VARCHAR(20),
                address TEXT,
                date_of_birth DATE,
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

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

        // Create payments table
        $this->pdo->exec("
            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_date DATE NOT NULL,
                payment_method VARCHAR(20) DEFAULT 'cash',
                recorded_by INTEGER NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (loan_id) REFERENCES loans(id)
            )
        ");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testCreateLoanWithClient()
    {
        // Create a test client first
        $clientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '+1234567890',
            'address' => '123 Main St',
            'date_of_birth' => '1990-01-01'
        ];

        $clientId = $this->clientService->createClient($clientData);

        // Create a loan for the client
        $loanData = [
            'client_id' => $clientId,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12
        ];

        $loanId = $this->loanService->createLoan($loanData);

        $this->assertIsInt($loanId);
        $this->assertGreaterThan(0, $loanId);

        // Verify loan was created with correct calculations
        $loan = $this->loanService->getLoanById($loanId);
        $this->assertEquals($clientId, $loan['client_id']);
        $this->assertEquals(10000.00, $loan['principal_amount']);
        $this->assertEquals(5.00, $loan['interest_rate']);
        $this->assertEquals(12, $loan['term_months']);
        $this->assertGreaterThan(0, $loan['monthly_payment']);
        $this->assertGreaterThan(10000.00, $loan['total_amount']);
    }

    public function testApproveLoan()
    {
        // Create client and loan
        $clientData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone_number' => '+0987654321',
            'address' => '456 Oak Ave',
            'date_of_birth' => '1985-05-15'
        ];

        $clientId = $this->clientService->createClient($clientData);

        $loanData = [
            'client_id' => $clientId,
            'principal_amount' => 5000.00,
            'interest_rate' => 4.50,
            'term_months' => 6
        ];

        $loanId = $this->loanService->createLoan($loanData);

        // Approve the loan
        $result = $this->loanService->approveLoan($loanId);

        $this->assertTrue($result);

        // Verify loan status changed to approved
        $loan = $this->loanService->getLoanById($loanId);
        $this->assertEquals('approved', $loan['status']);
    }

    public function testDisburseLoan()
    {
        // Create client and loan
        $clientData = [
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'phone_number' => '+1111111111',
            'address' => '789 Pine St',
            'date_of_birth' => '1975-12-31'
        ];

        $clientId = $this->clientService->createClient($clientData);

        $loanData = [
            'client_id' => $clientId,
            'principal_amount' => 8000.00,
            'interest_rate' => 5.50,
            'term_months' => 10
        ];

        $loanId = $this->loanService->createLoan($loanData);

        // Approve and disburse the loan
        $this->loanService->approveLoan($loanId);
        $disbursementDate = '2025-01-15';
        $result = $this->loanService->disburseLoan($loanId, $disbursementDate);

        $this->assertTrue($result);

        // Verify loan status and disbursement date
        $loan = $this->loanService->getLoanById($loanId);
        $this->assertEquals('active', $loan['status']);
        $this->assertEquals($disbursementDate, $loan['disbursement_date']);
        $this->assertNotNull($loan['maturity_date']);
    }

    public function testGetLoansWithClientInfo()
    {
        // Create multiple clients and loans
        $client1Data = [
            'name' => 'Client One',
            'email' => 'client1@example.com',
            'phone_number' => '+1111111111',
            'address' => '111 First St',
            'date_of_birth' => '1990-01-01'
        ];

        $client2Data = [
            'name' => 'Client Two',
            'email' => 'client2@example.com',
            'phone_number' => '+2222222222',
            'address' => '222 Second St',
            'date_of_birth' => '1985-05-15'
        ];

        $client1Id = $this->clientService->createClient($client1Data);
        $client2Id = $this->clientService->createClient($client2Data);

        // Create loans
        $loan1Data = [
            'client_id' => $client1Id,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12
        ];

        $loan2Data = [
            'client_id' => $client2Id,
            'principal_amount' => 5000.00,
            'interest_rate' => 4.50,
            'term_months' => 6
        ];

        $this->loanService->createLoan($loan1Data);
        $this->loanService->createLoan($loan2Data);

        // Get loans with client info
        $loans = $this->loanService->getLoansWithClientInfo(1, 10);

        $this->assertCount(2, $loans);
        $this->assertArrayHasKey('client_name', $loans[0]);
        $this->assertArrayHasKey('client_email', $loans[0]);
        $this->assertEquals('Client One', $loans[0]['client_name']);
        $this->assertEquals('Client Two', $loans[1]['client_name']);
    }

    public function testCalculateLoanProgress()
    {
        // Create client and loan
        $clientData = [
            'name' => 'Progress Test',
            'email' => 'progress@example.com',
            'phone_number' => '+3333333333',
            'address' => '333 Progress St',
            'date_of_birth' => '1990-01-01'
        ];

        $clientId = $this->clientService->createClient($clientData);

        $loanData = [
            'client_id' => $clientId,
            'principal_amount' => 12000.00,
            'interest_rate' => 5.00,
            'term_months' => 12
        ];

        $loanId = $this->loanService->createLoan($loanData);
        $this->loanService->approveLoan($loanId);
        $this->loanService->disburseLoan($loanId, '2025-01-01');

        // Add some payments
        $this->pdo->exec("
            INSERT INTO payments (loan_id, amount, payment_date, payment_method, recorded_by)
            VALUES ($loanId, 1000.00, '2025-01-15', 'cash', 1)
        ");

        $this->pdo->exec("
            INSERT INTO payments (loan_id, amount, payment_date, payment_method, recorded_by)
            VALUES ($loanId, 1000.00, '2025-02-15', 'cash', 1)
        ");

        $progress = $this->loanService->calculateLoanProgress($loanId);

        $this->assertIsArray($progress);
        $this->assertArrayHasKey('total_paid', $progress);
        $this->assertArrayHasKey('remaining_balance', $progress);
        $this->assertArrayHasKey('progress_percentage', $progress);
        $this->assertEquals(2000.00, $progress['total_paid']);
        $this->assertGreaterThan(0, $progress['remaining_balance']);
        $this->assertGreaterThan(0, $progress['progress_percentage']);
        $this->assertLessThanOrEqual(100, $progress['progress_percentage']);
    }

    public function testGetLoanSummary()
    {
        // Create test data
        $clientData = [
            'name' => 'Summary Test',
            'email' => 'summary@example.com',
            'phone_number' => '+4444444444',
            'address' => '444 Summary St',
            'date_of_birth' => '1990-01-01'
        ];

        $clientId = $this->clientService->createClient($clientData);

        $loanData = [
            'client_id' => $clientId,
            'principal_amount' => 15000.00,
            'interest_rate' => 6.00,
            'term_months' => 12
        ];

        $this->loanService->createLoan($loanData);

        $summary = $this->loanService->getLoanSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_loans', $summary);
        $this->assertArrayHasKey('active_loans', $summary);
        $this->assertArrayHasKey('total_principal', $summary);
        $this->assertEquals(1, $summary['total_loans']);
        $this->assertEquals(0, $summary['active_loans']); // Not disbursed yet
        $this->assertEquals(15000.00, $summary['total_principal']);
    }

    public function testGetOverdueLoans()
    {
        // Create client and loan
        $clientData = [
            'name' => 'Overdue Test',
            'email' => 'overdue@example.com',
            'phone_number' => '+5555555555',
            'address' => '555 Overdue St',
            'date_of_birth' => '1990-01-01'
        ];

        $clientId = $this->clientService->createClient($clientData);

        $loanData = [
            'client_id' => $clientId,
            'principal_amount' => 10000.00,
            'interest_rate' => 5.00,
            'term_months' => 12
        ];

        $loanId = $this->loanService->createLoan($loanData);
        $this->loanService->approveLoan($loanId);

        // Set disbursement date to past date to make it overdue
        $pastDate = date('Y-m-d', strtotime('-400 days'));
        $this->loanService->disburseLoan($loanId, $pastDate);

        $overdueLoans = $this->loanService->getOverdueLoans();

        $this->assertCount(1, $overdueLoans);
        $this->assertEquals($loanId, $overdueLoans[0]['id']);
        $this->assertArrayHasKey('days_overdue', $overdueLoans[0]);
        $this->assertGreaterThan(0, $overdueLoans[0]['days_overdue']);
    }
}
