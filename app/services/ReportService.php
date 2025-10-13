<?php
/**
 * ReportService - Handles generating reports for books, users, and transactions
 */
class ReportService extends BaseService {
    private $bookModel;
    private $userModel;
    private $transactionModel;
    private $penaltyModel;
    private $pdfGenerator;

    public function __construct() {
        parent::__construct();
        $this->bookModel = new BookModel();
        $this->userModel = new UserModel();
        $this->transactionModel = new TransactionModel();
        $this->penaltyModel = new PenaltyModel();
        $this->pdfGenerator = new PDFGenerator();
    }

 
public function generateBooksReport($filters = [], $toPdf = false) {
        // Apply filters
        $categoryId = isset($filters['category_id']) ? $filters['category_id'] : null;
        $availability = isset($filters['availability']) ? $filters['availability'] : null;
        
        // Get books data
        if ($categoryId) {
            $books = $this->bookModel->getBooksByCategory($categoryId);
        } else {
            $books = $this->bookModel->getAllBooksWithCategories();
        }
        
        // Ensure $books is always an array
        if (!is_array($books)) {
            $books = [];
        }
        
        // Calculate availability for each book using BookModel's isBookAvailable method
        foreach ($books as &$book) {
            $book['is_available'] = $this->bookModel->isBookAvailable($book['id']) ? 1 : 0;
        }
        unset($book);
        
        // Filter by availability if specified
        if ($availability !== null && !empty($books)) {
            $filtered = [];
            foreach ($books as $book) {
                if (($availability == 1 && $book['is_available'] == 1) || 
                    ($availability == 0 && $book['is_available'] == 0)) {
                    $filtered[] = $book;
                }
            }
            $books = $filtered;
        }
        
        // Fetch borrowers for each book
        foreach ($books as &$book) {
            $transactions = $this->transactionModel->getBookTransactionHistory($book['id']);
            $borrowers = [];
            if (is_array($transactions)) {
                foreach ($transactions as $transaction) {
                    // Only include currently borrowed books (return_date is null)
                    if ($transaction['return_date'] === null) {
                        $borrowers[] = $transaction['name'];
                    }
                }
            }
            $book['borrowers'] = $borrowers;
        }
        unset($book);
        
        // Get categories for the report header
        $categories = $this->bookModel->query(
            "SELECT * FROM categories ORDER BY name",
            [],
            false
        );
        
        // Ensure $categories is always an array
        if (!is_array($categories)) {
            $categories = [];
        }
        
        // Prepare report data
        $reportData = [
            'title' => 'Books Report',
            'generated_date' => date('Y-m-d H:i:s'),
            'filters' => $filters,
            'books' => $books,
            'categories' => $categories,
            'total_books' => count($books),
            'total_categories' => count($categories)
        ];
        
        if ($toPdf) {
            return $this->generateBooksPdf($reportData);
        }
        
        return $reportData;
    }


    public function generateUsersReport($filters = [], $toPdf = false) {
        // Apply filters
        $roleFilter = isset($filters['role']) ? $filters['role'] : null;
        $rolesFilter = isset($filters['roles']) ? $filters['roles'] : null;
        $isActive = isset($filters['status']) ? $filters['status'] : null;
        
        // Get users data
        if ($rolesFilter && is_array($rolesFilter)) {
            $users = $this->userModel->getAllUsersWithRoleNames($rolesFilter);
        } elseif ($roleFilter) {
            $users = $this->userModel->getAllUsersWithRoleNames([$roleFilter]);
        } else {
            $users = $this->userModel->getAllUsersWithRoleNames();
        }
        
        // Filter by active status if specified
        if ($isActive !== null && $users) {
            $filtered = [];
            foreach ($users as $user) {
                if ($isActive == 1 && $user['status'] === \UserModel::$STATUS_ACTIVE) {
                    $filtered[] = $user;
                } elseif ($isActive == 0 && $user['status'] === \UserModel::$STATUS_INACTIVE) {
                    $filtered[] = $user;
                }
            }
            $users = $filtered;
        }
        
        // Prepare report data
        $reportData = [
            'title' => 'Users Report',
            'generated_date' => date('Y-m-d H:i:s'),
            'filters' => $filters,
            'users' => $users,
            'total_users' => count($users),
            'total_active' => array_reduce($users, function($carry, $user) {
                return $carry + (isset($user['status']) && $user['status'] ? 1 : 0);
            }, 0),
            'total_inactive' => array_reduce($users, function($carry, $user) {
                return $carry + (isset($user['status']) && !$user['status'] ? 1 : 0);
            }, 0)
        ];
        
        if ($toPdf) {
            return $this->generateUsersPdf($reportData);
        }
        
        return $reportData;
    }

   
    public function generateTransactionsReport($filters = [], $toPdf = false) {
        // Apply filters
        $startDate = isset($filters['start_date']) ? $filters['start_date'] : null;
        $endDate = isset($filters['end_date']) ? $filters['end_date'] : null;
        $status = isset($filters['status']) ? $filters['status'] : null;
        
        // Get transactions data
        $transactions = $this->transactionModel->getTransactionsForReports(
            $startDate,
            $endDate,
            $status
        );
        
        // Prepare report data
        $reportData = [
            'title' => 'Transactions Report',
            'generated_date' => date('Y-m-d H:i:s'),
            'filters' => $filters,
            'transactions' => $transactions,
            'total_transactions' => count($transactions),
            'period' => ($startDate && $endDate) ? "From {$startDate} to {$endDate}" : 'All time'
        ];
        
        if ($toPdf) {
            return $this->generateTransactionsPdf($reportData);
        }
        
        return $reportData;
    }


public function generatePenaltiesReport($filters = [], $toPdf = false) {
        // Apply filters
        $startDate = isset($filters['start_date']) ? $filters['start_date'] : null;
        $endDate = isset($filters['end_date']) ? $filters['end_date'] : null;
        $isPaid = isset($filters['is_paid']) ? $filters['is_paid'] : null;
        
        // Get penalties data
        $penalties = $this->penaltyModel->getPenaltiesForReports(
            $startDate,
            $endDate,
            $isPaid
        );
        
        // Calculate totals
        $totalAmount = 0;
        $totalPaid = 0;
        $totalUnpaid = 0;
        
        if ($penalties) {
            foreach ($penalties as $penalty) {
                $totalAmount += $penalty['penalty_amount'] ?? 0;
                if (isset($penalty['status']) && $penalty['status'] == 1) {
                    $totalPaid += $penalty['penalty_amount'] ?? 0;
                } else {
                    $totalUnpaid += $penalty['penalty_amount'] ?? 0;
                }
            }
        }
        
        // Prepare report data
        $reportData = [
            'title' => 'Penalties Report',
            'generated_date' => date('Y-m-d H:i:s'),
            'filters' => $filters,
            'penalties' => $penalties,
            'total_penalties' => count($penalties),
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'period' => ($startDate && $endDate) ? "From {$startDate} to {$endDate}" : 'All time'
        ];
        
        if ($toPdf) {
            return $this->generatePenaltiesPdf($reportData);
        }
        
        return $reportData;
    }


private function generateBooksPdf($data) {
        // Clear any previous output to avoid FPDF error
        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->pdfGenerator->setOrientation('L'); // Set landscape orientation
        $this->pdfGenerator->setTitle($data['title']);
        $this->pdfGenerator->setAuthor('Library Management System');
        
        // Add header
        $this->pdfGenerator->addHeader($data['title']);
        $this->pdfGenerator->addLine("Generated on: " . $data['generated_date']);
        $this->pdfGenerator->addLine("Total Books: " . $data['total_books']);
        $this->pdfGenerator->addLine("Total Categories: " . $data['total_categories']);
        $this->pdfGenerator->addSpace();
        
        // Add filters section if any filters are applied
        if (!empty($data['filters'])) {
            $this->pdfGenerator->addSubHeader("Applied Filters");
            
            if (isset($data['filters']['category_id'])) {
                $categoryName = "Unknown";
                foreach ($data['categories'] as $category) {
                    if ($category['id'] == $data['filters']['category_id']) {
                        $categoryName = $category['name'];
                        break;
                    }
                }
                $this->pdfGenerator->addLine("Category: {$categoryName}");
            }
            
            if (isset($data['filters']['availability'])) {
                $availability = $data['filters']['availability'] ? "Available" : "Not Available";
                $this->pdfGenerator->addLine("Availability: {$availability}");
            }
            
            $this->pdfGenerator->addSpace();
        }
        
        // Define columns for books table, add Borrowers column
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Title', 'width' => 50],
            ['header' => 'Author', 'width' => 40],
            ['header' => 'Category', 'width' => 25],
            ['header' => 'Available Copies', 'width' => 25],
            ['header' => 'Status', 'width' => 20]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['books'] as $book) {
            $tableData[] = [
                $book['id'],
                isset($book['title']) ? $book['title'] : '',
                isset($book['author']) ? $book['author'] : '',
                isset($book['category_name']) ? $book['category_name'] : '',
                (isset($book['available_copies']) ? $book['available_copies'] : '0') . '/' . (isset($book['total_copies']) ? $book['total_copies'] : '0'),
                (isset($book['is_available']) && $book['is_available']) ? 'Available' : 'Not Available'
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }

private function generateUsersPdf($data) {
        // Clear any previous output to avoid FPDF error
        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->pdfGenerator->setOrientation('L'); // Set landscape orientation
        $this->pdfGenerator->setTitle($data['title']);
        $this->pdfGenerator->setAuthor('Library Management System');
        
        // Add header
        $this->pdfGenerator->addHeader($data['title']);
        $this->pdfGenerator->addLine("Generated on: " . $data['generated_date']);
        $this->pdfGenerator->addLine("Total Users: " . $data['total_users']);
        $this->pdfGenerator->addLine("Active Users: " . $data['total_active']);
        $this->pdfGenerator->addLine("Inactive Users: " . $data['total_inactive']);
        $this->pdfGenerator->addSpace();
        
        // Add filters section if any filters are applied
        if (!empty($data['filters'])) {
            $this->pdfGenerator->addSubHeader("Applied Filters");
            
            if (isset($data['filters']['role_id'])) {
                $roleName = "Unknown";
                switch ($data['filters']['role_id']) {
                    case 'super-admin':
                        $roleName = "Super Admin";
                        break;
                    case 'admin':
                        $roleName = "Admin";
                        break;
                    case 'staff':
                        $roleName = "Staff";
                        break;
                    case 'student':
                        $roleName = "Student";
                        break;
                    case'other':
                        $roleName = "Other";
                        break;
                    }
                $this->pdfGenerator->addLine("Role: {$roleName}");
            }
            
            if (isset($data['filters']['is_active'])) {
                $status = $data['filters']['status'] ? "Active" : "Inactive";
                $this->pdfGenerator->addLine("Status: {$status}");
            }
            
            $this->pdfGenerator->addSpace();
        }
        
        // Define columns for users table
        $columns = [
            ['header' => 'Complete Name', 'width' => 60],
            ['header' => 'Email', 'width' => 60],
            ['header' => 'Role', 'width' => 30],
            ['header' => 'Status', 'width' => 25],
            ['header' => 'Created', 'width' => 30]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['users'] as $user) {
            $tableData[] = [
                isset($user['name']) ? $user['name'] : '',
                isset($user['email']) ? $user['email'] : '',
                isset($user['role_display']) ? $user['role_display'] : 'Unknown',
                isset($user['status']) ? ucfirst($user['status']) : 'Unknown',
                isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : ''
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }


private function generateTransactionsPdf($data) {
        // Clear any previous output to avoid FPDF error
        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->pdfGenerator->setOrientation('L'); // Set landscape orientation
        $this->pdfGenerator->setTitle($data['title']);
        $this->pdfGenerator->setAuthor('Library Management System');
        
        // Add header
        $this->pdfGenerator->addHeader($data['title']);
        $this->pdfGenerator->addLine("Generated on: " . $data['generated_date']);
        $this->pdfGenerator->addLine("Period: " . $data['period']);
        $this->pdfGenerator->addLine("Total Transactions: " . $data['total_transactions']);
        $this->pdfGenerator->addSpace();
        
        // Add filters section if any filters are applied
        if (!empty($data['filters'])) {
            $this->pdfGenerator->addSubHeader("Applied Filters");
            
            if (isset($data['filters']['status'])) {
                $this->pdfGenerator->addLine("Status: " . $data['filters']['status']);
            }
            
            $this->pdfGenerator->addSpace();
        }
        
        // Define columns for transactions table
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Book', 'width' => 60],
            ['header' => 'Borrower', 'width' => 40],
            ['header' => 'Borrow Date', 'width' => 30],
            ['header' => 'Due Date', 'width' => 30],
            ['header' => 'Return Date', 'width' => 30],
            ['header' => 'Status', 'width' => 25]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['transactions'] as $transaction) {
            $returnDate = $transaction['return_date'] ? date('Y-m-d', strtotime($transaction['return_date'])) : 'Not Returned';
            $firstName = isset($transaction['first_name']) ? $transaction['first_name'] : '';
            $lastName = isset($transaction['last_name']) ? $transaction['last_name'] : '';
            
            $tableData[] = [
                $transaction['id'],
                $transaction['book_title'],
                trim($firstName . ' ' . $lastName),
                date('Y-m-d', strtotime($transaction['borrow_date'])),
                date('Y-m-d', strtotime($transaction['due_date'])),
                $returnDate,
                $transaction['status_label']
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }


private function generatePenaltiesPdf($data) {
        // Clear any previous output to avoid FPDF error
        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->pdfGenerator->setOrientation('L'); // Set landscape orientation
        $this->pdfGenerator->setTitle($data['title']);
        $this->pdfGenerator->setAuthor('Library Management System');
        
        // Add header
        $this->pdfGenerator->addHeader($data['title']);
        $this->pdfGenerator->addLine("Generated on: " . $data['generated_date']);
        $this->pdfGenerator->addLine("Period: " . $data['period']);
        $this->pdfGenerator->addLine("Total Penalties: " . $data['total_penalties']);
        $this->pdfGenerator->addLine("Total Amount: ₱" . number_format($data['total_amount'], 2));
        $this->pdfGenerator->addLine("Paid Amount: ₱" . number_format($data['total_paid'], 2));
        $this->pdfGenerator->addLine("Unpaid Amount: ₱" . number_format($data['total_unpaid'], 2));
        $this->pdfGenerator->addSpace();
        
        // Add filters section if any filters are applied
        if (!empty($data['filters'])) {
            $this->pdfGenerator->addSubHeader("Applied Filters");
            
            if (isset($data['filters']['is_paid'])) {
                $status = $data['filters']['is_paid'] == '1' ? "Paid" : "Unpaid";
                $this->pdfGenerator->addLine("Payment Status: {$status}");
            }
            
            $this->pdfGenerator->addSpace();
        }
        
        // Define columns for penalties table
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Book', 'width' => 50],
            ['header' => 'Borrower', 'width' => 40],
            ['header' => 'Borrow Date', 'width' => 30],
            ['header' => 'Due Date', 'width' => 30],
            ['header' => 'Return Date', 'width' => 30],
            ['header' => 'Days Overdue', 'width' => 25],
            ['header' => 'Amount', 'width' => 20],
            ['header' => 'Status', 'width' => 20]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['penalties'] as $penalty) {
            $userName = isset($penalty['user_name']) ? $penalty['user_name'] : 'Unknown';
            $borrowDate = isset($penalty['borrow_date']) && $penalty['borrow_date'] ? date('Y-m-d', strtotime($penalty['borrow_date'])) : 'N/A';
            $dueDate = isset($penalty['due_date']) && $penalty['due_date'] ? date('Y-m-d', strtotime($penalty['due_date'])) : 'N/A';
            $returnDate = isset($penalty['return_date']) && $penalty['return_date'] ? date('Y-m-d', strtotime($penalty['return_date'])) : 'N/A';
            $daysOverdue = isset($penalty['days_overdue']) ? $penalty['days_overdue'] : '0';
            $amount = isset($penalty['penalty_amount']) ? '₱' . number_format($penalty['penalty_amount'], 2) : '₱0.00';
            $status = isset($penalty['status']) && $penalty['status'] == 1 ? 'Paid' : 'Unpaid';

            $tableData[] = [
                $penalty['id'],
                $penalty['book_title'] ?? 'Unknown',
                $userName,
                $borrowDate,
                $dueDate,
                $returnDate,
                $daysOverdue,
                $amount,
                $status
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }

    public function getMonthlyActivitySummary($month = null) {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // New Books Added
        $newBooksAdded = $this->bookModel->query(
            "SELECT COUNT(*) as count FROM books WHERE DATE(created_at) BETWEEN ? AND ?",
            [$startDate, $endDate],
            true
        )['count'] ?? 0;
        
        // Books Borrowed
        $booksBorrowed = $this->transactionModel->query(
            "SELECT COUNT(*) as count FROM transaction WHERE status = 'borrowed' AND DATE(borrow_date) BETWEEN ? AND ?",
            [$startDate, $endDate],
            true
        )['count'] ?? 0;
        
        // Books Returned
        $booksReturned = $this->transactionModel->query(
            "SELECT COUNT(*) as count FROM transaction WHERE status = 'returned' AND DATE(return_date) BETWEEN ? AND ?",
            [$startDate, $endDate],
            true
        )['count'] ?? 0;
        
        // New Borrowers
        $newBorrowers = $this->userModel->query(
            "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) BETWEEN ? AND ?",
            [$startDate, $endDate],
            true
        )['count'] ?? 0;
        
        // Prepare summary data
        $summary = [
            'borrower_growth_text' => "Active borrowers compared to last month",
            'monthly' => [
                ['label' => 'New Books Added', 'value' => $newBooksAdded, 'bg' => '#edf2fc', 'dot' => '#0b76ef'],
                ['label' => 'Books Borrowed', 'value' => $booksBorrowed, 'bg' => '#f1ebfc', 'dot' => '#9d71ea'],
                ['label' => 'Books Returned', 'value' => $booksReturned, 'bg' => '#fff3e9', 'dot' => '#ec7211'],
                ['label' => 'New Borrowers', 'value' => $newBorrowers, 'bg' => '#ebfef6', 'dot' => '#0ca789']
            ]
        ];
        
        return $summary;
    }

 
    public function generateBookBorrowingHistoryReport($bookId, $toPdf = false) {
        if (!$bookId) {
            return [];
        }
        
        // Get transactions for the book
        $transactions = $this->transactionModel->getBookTransactionHistory($bookId);
        
        // Get book details
        $book = $this->bookModel->getBookWithCategory($bookId);
        $bookTitle = $book['title'] ?? 'Unknown Book';
        
        // Prepare report data
        $reportData = [
            'title' => 'Borrowing History Report',
            'generated_date' => date('Y-m-d H:i:s'),
            'book_id' => $bookId,
            'book_title' => $bookTitle,
            'transactions' => $transactions,
            'total_transactions' => count($transactions)
        ];
        
        if ($toPdf) {
            return $this->generateBookBorrowingHistoryPdf($reportData);
        }
        
        return $reportData;
    }


    private function generateBookBorrowingHistoryPdf($data) {
        // Clear any previous output to avoid FPDF error
        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->pdfGenerator->setOrientation('L'); // Landscape orientation
        $this->pdfGenerator->setTitle($data['title']);
        $this->pdfGenerator->setAuthor('Library Management System');
        
        // Add header
        $this->pdfGenerator->addHeader($data['title']);
        $this->pdfGenerator->addLine("Book: " . ($data['book_title'] ?? 'Unknown'));
        $this->pdfGenerator->addLine("Generated on: " . $data['generated_date']);
        $this->pdfGenerator->addLine("Total Transactions: " . $data['total_transactions']);
        $this->pdfGenerator->addSpace();
        
        // Define columns for transactions table
        $columns = [
            ['header' => 'Transaction ID', 'width' => 25],
            ['header' => 'Borrower', 'width' => 50],
            ['header' => 'Loan Date', 'width' => 30],
            ['header' => 'Due Date', 'width' => 30],
            ['header' => 'Return Date', 'width' => 30],
            ['header' => 'Status', 'width' => 30]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['transactions'] as $transaction) {
            $returnDate = $transaction['return_date'] ? date('Y-m-d', strtotime($transaction['return_date'])) : 'Not Returned';
            $status = 'Borrowed';
            if ($transaction['return_date']) {
                $status = 'Returned';
            } elseif ($transaction['due_date'] && strtotime($transaction['due_date']) < time()) {
                $status = 'Overdue';
            }
            $tableData[] = [
                $transaction['id'],
                $transaction['name'] ?? '',
                $transaction['borrow_date'] ? date('Y-m-d', strtotime($transaction['borrow_date'])) : '',
                $transaction['due_date'] ? date('Y-m-d', strtotime($transaction['due_date'])) : '',
                $returnDate,
                $status
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }
}
