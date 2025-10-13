<?php
/**
 * TransactionService - Handles book borrowing and return operations
 */
class TransactionService extends BaseService {
    private $transactionModel;
    private $bookModel;
    private $userModel;
    private $penaltyModel;

    private $penaltyService;

    public function __construct() {
        parent::__construct();
        $this->bookModel = new BookModel();
        $this->userModel = new UserModel();
        $this->penaltyModel = new PenaltyModel();
        $this->penaltyService = new PenaltyService();
        $this->transactionModel = new TransactionModel();
        $this->setModel($this->transactionModel);
    }

    public function createTransactionRequest($data) {
        return $this->transactionModel->create($data);
    }

    public function getLastError() {
        return $this->transactionModel->getLastError();
    }

 
    public function borrowBook($userId, $bookId, $durationDays = 14) {
        // Check if book exists and is available
        $book = $this->bookModel->findById($bookId);
        if (!$book) {
            $this->setErrorMessage('Book not found.');
            return false;
        }

        if ($book['available_copies'] <= 0) {
            $this->setErrorMessage('Book is not available for borrowing.');
            return false;
        }

        // Check if user exists and is active
        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->setErrorMessage('User not found.');
            return false;
        }

        if ($user['status'] !== 'active') {
            $this->setErrorMessage('User account is not active.');
            return false;
        }

        // Check if user has any overdue books
        $overdueBooks = $this->transactionModel->getUserOverdueLoans($userId);
        if ($overdueBooks && count($overdueBooks) > 0) {
            $this->setErrorMessage('Cannot borrow books while having overdue items.');
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Create loan record
            $transactionId = $this->transactionModel->createLoan($userId, $bookId, $durationDays);
            if (!$transactionId) {
                throw new Exception('Failed to create loan record.');
            }

            // Update book available copies
            if (!$this->bookModel->decrementAvailableCopies($bookId)) {
                throw new Exception('Failed to update book availability.');
            }

            $this->db->commit();
            return $transactionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

   

    private $penaltyRatePerDay = 50;

    public function updateOverduePenalties() {
        // Get all overdue loans without penalties
        $overdueLoans = $this->transactionModel->getOverdueLoans();
        if (!$overdueLoans) {
            return;
        }

        foreach ($overdueLoans as $loan) {
            $transactionId = $loan['id'];
            $userId = $loan['user_id'];
            $daysOverdue = $loan['days_overdue'];
            if ($daysOverdue <= 0) {
                continue;
            }

            $penaltyAmount = $daysOverdue * $this->penaltyRatePerDay;

            // Insert penalty for overdue transaction
            $this->penaltyService->createOrUpdatePenalty($userId, $transactionId, $penaltyAmount);

            // Update transaction status to 'overdue'
            $this->transactionModel->updateTransaction($transactionId, ['status' => 'overdue']);
        }
    }

  
    public function getActiveLoans() {
        return $this->transactionModel->getActiveLoans();
    }

   
    public function getOverdueLoans() {
        return $this->transactionModel->getOverdueLoans();
    }

    public function getUserCurrentBorrows($userId) {
        return $this->transactionModel->getUserCurrentBorrows($userId);
    }

    public function getUserOverdueLoans($userId) {
        return $this->transactionModel->getUserOverdueLoans($userId);
    }

    public function getUserTransactionHistory($userId) {
        return $this->transactionModel->getUserTransactionHistory($userId);
    }

 
    public function getBookTransactionHistory($bookId) {
        return $this->transactionModel->getBookTransactionHistory($bookId);
    }

    public function getBookTransactionHistoryByUser($bookId, $userId) {
        return $this->transactionModel->getBookTransactionHistoryByUser($bookId, $userId);
    }


    public function exportTransactionsToPDF($filters = []) {
        // Get transactions based on filters
        $transactions = $this->transactionModel->getFilteredTransactions($filters);
        
        if (!$transactions) {
            $this->setErrorMessage('No transactions found to export.');
            return false;
        }

        // Create PDF using TCPDF or similar library
        require_once '/vendor/tecnickcom/fpdf/fpdf.php';

        $pdf = new FPDF('P', 'mm', 'A4');

        // Set document information
        $pdf->SetCreator('LibraryVault System');
        $pdf->SetAuthor('LibraryVault System');
        $pdf->SetTitle('Transaction Report');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 10);

        // Add a page
        $pdf->AddPage();

        // Create the table
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Transaction Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Table header
        $pdf->SetFont('Arial', 'B', 10);
        $headers = ['ID', 'Book Title', 'Borrower', 'Borrow Date', 'Due Date', 'Return Date', 'Status'];
        $widths = [20, 50, 40, 30, 30, 30, 30];
        
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C');
        }
        $pdf->Ln();

        // Table data
        $pdf->SetFont('Arial', '', 10);
        foreach ($transactions as $transaction) {
            $pdf->Cell($widths[0], 6, $transaction['id'], 1);
            $pdf->Cell($widths[1], 6, $transaction['book_title'], 1);
            $pdf->Cell($widths[2], 6, $transaction['name'], 1);
            $pdf->Cell($widths[3], 6, date('Y-m-d', strtotime($transaction['borrow_date'])), 1);
            $pdf->Cell($widths[4], 6, date('Y-m-d', strtotime($transaction['due_date'])), 1);
            $pdf->Cell($widths[5], 6, $transaction['return_date'] ? date('Y-m-d', strtotime($transaction['return_date'])) : 'Not returned', 1);
            $pdf->Cell($widths[6], 6, $transaction['status_label'], 1);
            $pdf->Ln();
        }

        // Generate file path
        $filePath = '/storage/reports/transactions_' . date('Y-m-d_His') . '.pdf';

        // Save PDF
        $pdf->Output($filePath, 'F');

        return $filePath;
    }

 
    public function getAllTransactionsWithDetails() {
        return $this->transactionModel->getAllTransactionsWithDetails();
    }

    public function getTransactionById($transactionId) {
        return $this->transactionModel->getTransactionDetails($transactionId);
    }

    public function updateTransaction($transactionId, $data) {
        return $this->transactionModel->updateTransaction($transactionId, $data);
    }

    public function deleteTransaction($transactionId) {
        return $this->transactionModel->delete($transactionId);
    }

    public function getTransactionsForReports($startDate = null, $endDate = null, $status = null) {
        return $this->transactionModel->getTransactionsForReports($startDate, $endDate, $status);
    }

    public function approveBorrowRequest($transactionId) {
        $transaction = $this->getTransactionById($transactionId);
        if (!$transaction || $transaction['status'] !== 'borrowing') {
            $this->setErrorMessage('Invalid borrow request.');
            return false;
        }

        // Check book availability
        $book = $this->bookModel->findById($transaction['book_id']);
        if (!$book || $book['available_copies'] <= 0) {
            $this->setErrorMessage('Book is not available for borrowing.');
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update transaction status to 'borrowed', set borrow_date to today and due_date (14 days after borrow_date)
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime($borrowDate . ' +14 days'));
            $this->transactionModel->updateTransaction($transactionId, [
                'status' => 'borrowed',
                'borrow_date' => $borrowDate,
                'due_date' => $dueDate,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Decrement book available copies
            if (!$this->bookModel->decrementAvailableCopies($transaction['book_id'])) {
                throw new Exception('Failed to update book availability.');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }


    public function approveReturnRequest($transactionId) {
        $transaction = $this->getTransactionById($transactionId);
        if (!$transaction || $transaction['status'] !== 'returning') {
            $this->setErrorMessage('Invalid return request.');
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update transaction status to 'returned', set return_date and updated_at on approval
            $this->transactionModel->updateTransaction($transactionId, [
                'status' => 'returned',
                'return_date' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Increment book available copies
            if (!$this->bookModel->incrementAvailableCopies($transaction['book_id'])) {
                throw new Exception('Failed to update book availability.');
            }

            // Check for overdue and calculate penalty
            if (strtotime($transaction['due_date']) < time()) {
                $daysOverdue = ceil((time() - strtotime($transaction['due_date'])) / (60 * 60 * 24));
                $penaltyAmount = $daysOverdue * $this->penaltyRatePerDay;

                if (!$this->penaltyService->createOrUpdatePenalty($transaction['user_id'], $transactionId, $penaltyAmount)) {
                    throw new Exception('Failed to create or update penalty record.');
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

  
    public function getTransactionsByStatuses(array $statuses) {
        if (empty($statuses)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT t.*, b.title as book_title, u.name as borrower_name, u.email as borrower_email
                FROM transaction t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.status IN ($placeholders)
                ORDER BY t.created_at DESC";

        return $this->db->resultSet($sql, $statuses);
    }
 }
