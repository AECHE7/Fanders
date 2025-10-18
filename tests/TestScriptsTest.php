<?php
use PHPUnit\Framework\TestCase;

class TestScriptsTest extends TestCase {

    public function testAddUserService() {
        $userService = new UserService();

        $userData = [
            'name' => 'PHPUnit User',
            'email' => 'phpunit@example.com',
            'phone_number' => '09123450001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
            'status' => 'active'
        ];

        $result = $userService->addUser($userData);
        $this->assertIsInt($result);
    }

    public function testAddAdminUserService() {
        $userService = new UserService();

        $adminData = [
            'name' => 'PHPUnit Admin',
            'email' => 'phpunit.admin@example.com',
            'phone_number' => '09123450002',
            'password' => 'adminpass123',
            'password_confirmation' => 'adminpass123',
            'role' => 'admin',
            'status' => 'active'
        ];

        $result = $userService->addUser($adminData);
        $this->assertIsInt($result);
    }

    public function testTransactionServiceLoggingAndRetrieval() {
        $transactionService = new TransactionService();

        $r1 = $transactionService->logLoanTransaction('created', 1, 1, ['amount' => 10000]);
        $this->assertTrue((bool)$r1);

        $r2 = $transactionService->logPaymentTransaction(1, 1, ['amount' => 500]);
        $this->assertTrue((bool)$r2);

        $r3 = $transactionService->logClientTransaction('created', 1, 1, ['name' => 'Test Client']);
        $this->assertTrue((bool)$r3);

        $r4 = $transactionService->logUserTransaction('login', 1, 1, ['ip' => '127.0.0.1']);
        $this->assertTrue((bool)$r4);

        $transactions = $transactionService->getTransactionHistory(10);
        $this->assertIsArray($transactions);
    }
}
