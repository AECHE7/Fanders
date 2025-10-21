<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\PaymentModel;
use PDO;

class PaymentModelTest extends TestCase
{
    private $pdo;
    private $paymentModel;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create payments table
        $this->pdo->exec("
            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_date DATE NOT NULL,
                payment_method ENUM('cash', 'bank_transfer', 'check') DEFAULT 'cash',
                recorded_by INTEGER NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (loan_id) REFERENCES loans(id),
                FOREIGN KEY (recorded_by) REFERENCES users(id)
            )
        ");

        $this->paymentModel = new PaymentModel($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testCreatePayment()
    {
        $paymentData = [
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-15',
            'payment_method' => 'cash',
            'recorded_by' => 1,
            'notes' => 'Monthly payment'
        ];

        $paymentId = $this->paymentModel->create($paymentData);

        $this->assertIsInt($paymentId);
        $this->assertGreaterThan(0, $paymentId);
    }

    public function testFindById()
    {
        // Create a test payment
        $paymentData = [
            'loan_id' => 1,
            'amount' => 500.00,
            'payment_date' => '2025-01-10',
            'payment_method' => 'bank_transfer',
            'recorded_by' => 1,
            'notes' => 'Partial payment'
        ];

        $paymentId = $this->paymentModel->create($paymentData);
        $payment = $this->paymentModel->findById($paymentId);

        $this->assertIsArray($payment);
        $this->assertEquals($paymentId, $payment['id']);
        $this->assertEquals(500.00, $payment['amount']);
        $this->assertEquals('bank_transfer', $payment['payment_method']);
    }

    public function testGetPaymentsByLoanId()
    {
        // Create payments for loan 1
        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-02-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        // Create payment for different loan
        $this->paymentModel->create([
            'loan_id' => 2,
            'amount' => 425.00,
            'payment_date' => '2025-01-15',
            'payment_method' => 'check',
            'recorded_by' => 2
        ]);

        $loanPayments = $this->paymentModel->getPaymentsByLoanId(1);

        $this->assertCount(2, $loanPayments);
        $this->assertEquals(1, $loanPayments[0]['loan_id']);
        $this->assertEquals(1, $loanPayments[1]['loan_id']);
        $this->assertEquals(850.00, $loanPayments[0]['amount']);
        $this->assertEquals(850.00, $loanPayments[1]['amount']);
    }

    public function testGetTotalPaymentsByLoanId()
    {
        // Create payments for loan 1
        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 500.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 300.00,
            'payment_date' => '2025-01-15',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $totalPayments = $this->paymentModel->getTotalPaymentsByLoanId(1);

        $this->assertEquals(800.00, $totalPayments);
    }

    public function testGetPaymentsInDateRange()
    {
        // Create payments in different date ranges
        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-15',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-02-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $paymentsInRange = $this->paymentModel->getPaymentsInDateRange('2025-01-01', '2025-01-31');

        $this->assertCount(2, $paymentsInRange);
        $this->assertEquals('2025-01-01', $paymentsInRange[0]['payment_date']);
        $this->assertEquals('2025-01-15', $paymentsInRange[1]['payment_date']);
    }

    public function testGetPaymentsByRecordedBy()
    {
        // Create payments recorded by different users
        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $this->paymentModel->create([
            'loan_id' => 2,
            'amount' => 425.00,
            'payment_date' => '2025-01-15',
            'payment_method' => 'check',
            'recorded_by' => 2
        ]);

        $this->paymentModel->create([
            'loan_id' => 3,
            'amount' => 600.00,
            'payment_date' => '2025-02-01',
            'payment_method' => 'bank_transfer',
            'recorded_by' => 1
        ]);

        $userPayments = $this->paymentModel->getPaymentsByRecordedBy(1);

        $this->assertCount(2, $userPayments);
        $this->assertEquals(1, $userPayments[0]['recorded_by']);
        $this->assertEquals(1, $userPayments[1]['recorded_by']);
    }

    public function testUpdatePayment()
    {
        // Create a test payment
        $paymentData = [
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1,
            'notes' => 'Original payment'
        ];

        $paymentId = $this->paymentModel->create($paymentData);

        // Update the payment
        $updateData = [
            'amount' => 900.00,
            'payment_method' => 'bank_transfer',
            'notes' => 'Updated payment amount'
        ];

        $result = $this->paymentModel->update($paymentId, $updateData);

        $this->assertTrue($result);

        // Verify the update
        $updatedPayment = $this->paymentModel->findById($paymentId);
        $this->assertEquals(900.00, $updatedPayment['amount']);
        $this->assertEquals('bank_transfer', $updatedPayment['payment_method']);
        $this->assertEquals('Updated payment amount', $updatedPayment['notes']);
    }

    public function testDeletePayment()
    {
        // Create a test payment
        $paymentData = [
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ];

        $paymentId = $this->paymentModel->create($paymentData);

        // Delete the payment
        $result = $this->paymentModel->delete($paymentId);

        $this->assertTrue($result);

        // Verify the payment is deleted
        $deletedPayment = $this->paymentModel->findById($paymentId);
        $this->assertNull($deletedPayment);
    }

    public function testGetPaymentSummary()
    {
        // Create test payments
        $this->paymentModel->create([
            'loan_id' => 1,
            'amount' => 850.00,
            'payment_date' => '2025-01-01',
            'payment_method' => 'cash',
            'recorded_by' => 1
        ]);

        $this->paymentModel->create([
            'loan_id' => 2,
            'amount' => 425.00,
            'payment_date' => '2025-01-15',
            'payment_method' => 'check',
            'recorded_by' => 2
        ]);

        $summary = $this->paymentModel->getPaymentSummary('2025-01-01', '2025-01-31');

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_payments', $summary);
        $this->assertArrayHasKey('total_amount', $summary);
        $this->assertArrayHasKey('average_payment', $summary);
        $this->assertEquals(2, $summary['total_payments']);
        $this->assertEquals(1275.00, $summary['total_amount']);
        $this->assertEquals(637.50, $summary['average_payment']);
    }
}
