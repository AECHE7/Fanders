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

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->bookModel = new BookModel();
        $this->userModel = new UserModel();
        $this->transactionModel = new TransactionModel();
        $this->penaltyModel = new PenaltyModel();
        $this->pdfGenerator = new PDFGenerator();
    }

    /**
     * Generate books report
     * 
     * @param array $filters Optional filters
     * @param bool $toPdf Whether to generate PDF or return data
     * @return mixed Data array or PDF output
     */
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
        
        // Filter by availability if specified
        if ($availability !== null && $books) {
            $filtered = [];
            foreach ($books as $book) {
                if (($availability == 1 && $book['is_available'] == 1) || 
                    ($availability == 0 && $book['is_available'] == 0)) {
                    $filtered[] = $book;
                }
            }
            $books = $filtered;
        }
        
        // Get categories for the report header
        $categories = $this->bookModel->query(
            "SELECT * FROM categories ORDER BY name",
            [],
            false
        );
        
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

    /**
     * Generate users report
     * 
     * @param array $filters Optional filters
     * @param bool $toPdf Whether to generate PDF or return data
     * @return mixed Data array or PDF output
     */
    public function generateUsersReport($filters = [], $toPdf = false) {
        // Apply filters
        $roleId = isset($filters['role_id']) ? $filters['role_id'] : null;
        $isActive = isset($filters['is_active']) ? $filters['is_active'] : null;
        
        // Get users data
        if ($roleId) {
            $users = $this->userModel->getUsersByRole($roleId);
        } else {
            $users = $this->userModel->getAllUsersWithRoleNames();
        }
        
        // Filter by active status if specified
        if ($isActive !== null && $users) {
            $filtered = [];
            foreach ($users as $user) {
                if ($user['is_active'] == $isActive) {
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
                return $carry + ($user['is_active'] ? 1 : 0);
            }, 0),
            'total_inactive' => array_reduce($users, function($carry, $user) {
                return $carry + (!$user['is_active'] ? 1 : 0);
            }, 0)
        ];
        
        if ($toPdf) {
            return $this->generateUsersPdf($reportData);
        }
        
        return $reportData;
    }

    /**
     * Generate transactions report
     * 
     * @param array $filters Optional filters
     * @param bool $toPdf Whether to generate PDF or return data
     * @return mixed Data array or PDF output
     */
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

    /**
     * Generate penalties report
     * 
     * @param array $filters Optional filters
     * @param bool $toPdf Whether to generate PDF or return data
     * @return mixed Data array or PDF output
     */
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
                $totalAmount += $penalty['amount'];
                if ($penalty['is_paid']) {
                    $totalPaid += $penalty['amount'];
                } else {
                    $totalUnpaid += $penalty['amount'];
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

    /**
     * Generate PDF for books report
     * 
     * @param array $data Report data
     * @return string PDF output
     */
    private function generateBooksPdf($data) {
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
        
        // Define columns for books table
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Title', 'width' => 60],
            ['header' => 'Author', 'width' => 50],
            ['header' => 'ISBN', 'width' => 30],
            ['header' => 'Category', 'width' => 30],
            ['header' => 'Available Copies', 'width' => 30],
            ['header' => 'Status', 'width' => 25]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['books'] as $book) {
            $tableData[] = [
                $book['id'],
                $book['title'],
                $book['author'],
                $book['isbn'],
                $book['category_name'],
                $book['available_copies'] . '/' . $book['total_copies'],
                $book['is_available'] ? 'Available' : 'Not Available'
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }

    /**
     * Generate PDF for users report
     * 
     * @param array $data Report data
     * @return string PDF output
     */
    private function generateUsersPdf($data) {
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
                    case ROLE_SUPER_ADMIN:
                        $roleName = "Super Admin";
                        break;
                    case ROLE_ADMIN:
                        $roleName = "Admin";
                        break;
                    case ROLE_BORROWER:
                        $roleName = "Borrower";
                        break;
                }
                $this->pdfGenerator->addLine("Role: {$roleName}");
            }
            
            if (isset($data['filters']['is_active'])) {
                $status = $data['filters']['is_active'] ? "Active" : "Inactive";
                $this->pdfGenerator->addLine("Status: {$status}");
            }
            
            $this->pdfGenerator->addSpace();
        }
        
        // Define columns for users table
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Username', 'width' => 30],
            ['header' => 'Name', 'width' => 50],
            ['header' => 'Email', 'width' => 60],
            ['header' => 'Role', 'width' => 30],
            ['header' => 'Status', 'width' => 25],
            ['header' => 'Created', 'width' => 30]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['users'] as $user) {
            $tableData[] = [
                $user['id'],
                $user['username'],
                $user['first_name'] . ' ' . $user['last_name'],
                $user['email'],
                $user['role_name'] ?? 'Unknown',
                $user['is_active'] ? 'Active' : 'Inactive',
                date('Y-m-d', strtotime($user['created_at']))
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }

    /**
     * Generate PDF for transactions report
     * 
     * @param array $data Report data
     * @return string PDF output
     */
    private function generateTransactionsPdf($data) {
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
            
            $tableData[] = [
                $transaction['id'],
                $transaction['book_title'],
                $transaction['first_name'] . ' ' . $transaction['last_name'],
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

    /**
     * Generate PDF for penalties report
     * 
     * @param array $data Report data
     * @return string PDF output
     */
    private function generatePenaltiesPdf($data) {
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
                $status = $data['filters']['is_paid'] ? "Paid" : "Unpaid";
                $this->pdfGenerator->addLine("Payment Status: {$status}");
            }
            
            $this->pdfGenerator->addSpace();
        }
        
        // Define columns for penalties table
        $columns = [
            ['header' => 'ID', 'width' => 15],
            ['header' => 'Book', 'width' => 50],
            ['header' => 'Borrower', 'width' => 40],
            ['header' => 'Due Date', 'width' => 30],
            ['header' => 'Return Date', 'width' => 30],
            ['header' => 'Days Overdue', 'width' => 25],
            ['header' => 'Amount', 'width' => 20],
            ['header' => 'Status', 'width' => 20]
        ];
        
        // Prepare table data
        $tableData = [];
        foreach ($data['penalties'] as $penalty) {
            $tableData[] = [
                $penalty['id'],
                $penalty['book_title'],
                $penalty['first_name'] . ' ' . $penalty['last_name'],
                date('Y-m-d', strtotime($penalty['due_date'])),
                date('Y-m-d', strtotime($penalty['return_date'])),
                $penalty['days_overdue'],
                '₱' . number_format($penalty['amount'], 2),
                $penalty['is_paid'] ? 'Paid' : 'Unpaid'
            ];
        }
        
        // Add table to PDF
        $this->pdfGenerator->addTable($columns, $tableData);
        
        // Generate and return PDF
        return $this->pdfGenerator->output();
    }
}
