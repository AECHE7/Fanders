<?php
/**
 * TransactionService - Handles book loan transactions
 */
class TransactionService extends BaseService {
    private $transactionModel;
    private $bookModel;
    private $borrowerModel;
    private $penaltyModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
        $this->bookModel = new BookModel();
        $this->borrowerModel = new BorrowerModel();
        $this->penaltyModel = new PenaltyModel();
        $this->setModel($this->transactionModel);
    }

    /**
     * Get transaction details
     * 
     * @param int $id
     * @return array|bool
     */
    public function getTransactionDetails($id) {
        return $this->transactionModel->getTransactionDetails($id);
    }

    /**
     * Get all transactions with details
     * 
     * @return array|bool
     */
    public function getAllTransactionsWithDetails() {
        return $this->transactionModel->getAllTransactionsWithDetails();
    }

    /**
     * Get active loans
     * 
     * @return array|bool
     */
    public function getActiveLoans() {
        return $this->transactionModel->getActiveLoans();
    }

    /**
     * Get overdue loans
     * 
     * @return array|bool
     */
    public function getOverdueLoans() {
        return $this->transactionModel->getOverdueLoans();
    }

    /**
     * Borrow a book
     * 
     * @param int $userId
     * @param int $bookId
     * @param int $durationDays
     * @return int|bool
     */
    public function borrowBook($userId, $bookId, $durationDays = 14) {
        return $this->transaction(function() use ($userId, $bookId, $durationDays) {
            // Check if user exists and is a borrower
            $borrower = $this->borrowerModel->findById($userId);
            if (!$borrower) {
                $this->setErrorMessage('Invalid borrower.');
                return false;
            }
            
            if ($borrower['role_id'] != ROLE_BORROWER) {
                $this->setErrorMessage('Only borrowers can borrow books.');
                return false;
            }
            
            if (!$borrower['is_active']) {
                $this->setErrorMessage('Borrower account is inactive.');
                return false;
            }
            
            // Check if book exists and is available
            if (!$this->bookModel->isBookAvailable($bookId)) {
                $this->setErrorMessage('Book is not available for borrowing.');
                return false;
            }
            
            // Check if user has already borrowed this book
            if ($this->transactionModel->hasUserBorrowedBook($userId, $bookId)) {
                $this->setErrorMessage('You have already borrowed this book.');
                return false;
            }
            
            // Check if user has reached maximum allowed loans (e.g., 3)
            if ($this->borrowerModel->hasReachedMaxLoans($userId, 3)) {
                $this->setErrorMessage('You have reached the maximum number of allowed loans (3).');
                return false;
            }
            
            // Check if user has any overdue books
            if ($this->borrowerModel->hasOverdueBooks($userId)) {
                $this->setErrorMessage('You have overdue books. Please return them before borrowing more books.');
                return false;
            }
            
            // Check if user has unpaid penalties
            $unpaidPenalties = $this->penaltyModel->getUserUnpaidPenaltiesTotal($userId);
            if ($unpaidPenalties > 0) {
                $this->setErrorMessage("You have unpaid penalties (₱{$unpaidPenalties}). Please pay them before borrowing more books.");
                return false;
            }
            
            // Create loan transaction
            $transactionId = $this->transactionModel->createLoan($userId, $bookId, $durationDays);
            
            if (!$transactionId) {
                $this->setErrorMessage('Failed to create loan transaction.');
                return false;
            }
            
            // Update book availability
            if (!$this->bookModel->decrementAvailableCopies($bookId)) {
                $this->setErrorMessage('Failed to update book availability.');
                return false;
            }
            
            return $transactionId;
        });
    }

    /**
     * Return a book
     * 
     * @param int $transactionId
     * @return bool
     */
    public function returnBook($transactionId) {
        return $this->transaction(function() use ($transactionId) {
            // Get transaction details
            $transaction = $this->transactionModel->findById($transactionId);
            
            if (!$transaction) {
                $this->setErrorMessage('Transaction not found.');
                return false;
            }
            
            if ($transaction['return_date'] !== null) {
                $this->setErrorMessage('This book has already been returned.');
                return false;
            }
            
            // Return the book
            if (!$this->transactionModel->returnBook($transactionId)) {
                $this->setErrorMessage('Failed to update return status.');
                return false;
            }
            
            // Update book availability
            if (!$this->bookModel->incrementAvailableCopies($transaction['book_id'])) {
                $this->setErrorMessage('Failed to update book availability.');
                return false;
            }
            
            // Check if book is overdue and create penalty if needed
            $dueDate = new DateTime($transaction['due_date']);
            $returnDate = new DateTime();
            
            if ($returnDate > $dueDate) {
                $daysOverdue = $returnDate->diff($dueDate)->days;
                
                // Check if penalty already exists for this transaction
                if (!$this->penaltyModel->transactionHasPenalty($transactionId)) {
                    // Create penalty
                    $this->penaltyModel->createPenalty($transactionId, $daysOverdue);
                }
            }
            
            return true;
        });
    }

    /**
     * Get user's transaction history
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getUserTransactionHistory($userId) {
        return $this->transactionModel->getUserTransactionHistory($userId);
    }

    /**
     * Get book's transaction history
     * 
     * @param int $bookId
     * @return array|bool
     */
    public function getBookTransactionHistory($bookId) {
        return $this->transactionModel->getBookTransactionHistory($bookId);
    }

    /**
     * Get transactions for reports
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string $status
     * @return array|bool
     */
    public function getTransactionsForReports($startDate = null, $endDate = null, $status = null) {
        return $this->transactionModel->getTransactionsForReports($startDate, $endDate, $status);
    }
}
