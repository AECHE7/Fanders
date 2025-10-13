<?php
/**
 * BookService - Handles book-related operations
 */
class BookService extends BaseService {
    private $bookModel;
    private $categoryModel;


    public function __construct() {
        parent::__construct();
        $this->bookModel = new BookModel();
        $this->categoryModel = new CategoryModel();
        $this->setModel($this->bookModel);
    }


    public function getBookWithCategory($id) {
        return $this->bookModel->getBookWithCategory($id);
    }


    public function getAllBooksWithCategories() {
        return $this->bookModel->getAllBooksWithCategories();
    }


    public function searchBooks($term) {
        return $this->bookModel->searchBooks($term);
    }


    public function getBooksByCategory($categoryId) {
        return $this->bookModel->getBooksByCategory($categoryId);
    }

 
    public function getAvailableBooks($limit = 5) {
        return $this->bookModel->getAvailableBooks($limit);
    }

    public function getAllAvailableBooksWithCategories() {
        return $this->bookModel->getAllAvailableBooksWithCategories();
    }

    public function addBook($bookData, $addedBy) {
        // Validate book data
        if (!$this->validateBookData($bookData)) {
            return false;
        }
        
        
        // Set additional fields
        $bookData['added_by'] = $addedBy;
        $bookData['is_available'] = $bookData['available_copies'] > 0 ? 1 : 0;
        $bookData['created_at'] = date('Y-m-d H:i:s');
        $bookData['updated_at'] = date('Y-m-d H:i:s');
        
        // Create book
        return $this->bookModel->create($bookData);
    }

    public function updateBook($id, $bookData) {
        // Get existing book
        $existingBook = $this->bookModel->findById($id);
        
        if (!$existingBook) {
            $this->setErrorMessage('Book not found.');
            return false;
        }
        
        // Validate book data
        if (!$this->validateBookData($bookData, $id)) {
            return false;
        }
        
        // Set availability based on available copies
        $bookData['is_available'] = $bookData['available_copies'] > 0 ? 1 : 0;
        $bookData['updated_at'] = date('Y-m-d H:i:s');
        
        // Update book
        return $this->bookModel->update($id, $bookData);
    }

  
    public function deleteBook($id, $role) {
        // Check if book exists
        $book = $this->bookModel->findById($id);
        
        if (!$book) {
            $this->setErrorMessage('Book not found.');
            return false;
        }
        
        // Check if book has active loans
        $transactionModel = new TransactionModel();
        $activeLoans = $transactionModel->query(
            "SELECT COUNT(*) as count FROM transactions WHERE book_id = ? AND return_date IS NULL",
            [$id],
            true
        );
        
        if ($activeLoans && $activeLoans['count'] > 0) {
            $this->setErrorMessage('Cannot delete book with active loans.');
            return false;
        }
        
        if ($role === 'admin') {
            // Soft delete (archive)
            if ($this->bookModel->archiveBook($id)) {
                return true;
            } else {
                $this->setErrorMessage('Failed to archive the book.');
                return false;
            }
        } elseif ($role === 'super-admin') {
            // Hard delete
            return $this->permanentlyDeleteBook($id, $role);
        } else {
            $this->setErrorMessage('Unauthorized role for deleting book.');
            return false;
        }
    }

    public function getAllCategories() {
        return $this->categoryModel->getAll('name', 'ASC');
    }

    public function getAllCategoriesWithBookCount() {
        return $this->categoryModel->getAllCategoriesWithBookCount();
    }

    public function addCategory($categoryData) {
        // Validate category data
        if (!isset($categoryData['name']) || empty($categoryData['name'])) {
            $this->setErrorMessage('Category name is required.');
            return false;
        }
        
        // Check if category name exists
        if ($this->categoryModel->categoryNameExists($categoryData['name'])) {
            $this->setErrorMessage('Category name already exists.');
            return false;
        }
        
        // Set timestamps
        $categoryData['created_at'] = date('Y-m-d H:i:s');
        $categoryData['updated_at'] = date('Y-m-d H:i:s');
        
        // Create category
        return $this->categoryModel->create($categoryData);
    }


    public function updateCategory($id, $categoryData) {
        // Validate category data
        if (!isset($categoryData['name']) || empty($categoryData['name'])) {
            $this->setErrorMessage('Category name is required.');
            return false;
        }
        
        // Check if category name exists
        if ($this->categoryModel->categoryNameExists($categoryData['name'], $id)) {
            $this->setErrorMessage('Category name already exists.');
            return false;
        }
        
        // Set timestamps
        $categoryData['updated_at'] = date('Y-m-d H:i:s');
        
        // Update category
        return $this->categoryModel->update($id, $categoryData);
    }

    public function deleteCategory($id) {
        // Check if category has books
        $bookCount = $this->categoryModel->getCategoryBooksCount($id);
        
        if ($bookCount > 0) {
            $this->setErrorMessage("Cannot delete category with {$bookCount} associated books.");
            return false;
        }
        
        // Delete category
        return $this->categoryModel->delete($id);
    }


    public function getRecentlyAddedBooks($limit = 10) {
        return $this->bookModel->getRecentlyAddedBooks($limit);
    }

    public function getUserBorrowedBooks($userId) {
        $sql = "SELECT 
                    b.title,
                    b.author,
                    b.published_year,
                    c.id as category_id,
                    c.name as category_name,
                    t.borrow_date,
                    t.due_date,
                    t.return_date,
                    CASE 
                        WHEN t.return_date IS NOT NULL THEN 'returned'
                        WHEN t.due_date < CURRENT_DATE THEN 'overdue'
                        ELSE 'borrowed'
                    END as status
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                JOIN categories c ON b.category_id = c.id
                WHERE t.user_id = ?
                ORDER BY t.borrow_date DESC";
        
        return $this->bookModel->query($sql, [$userId]);
    }

  
    public function getMostBorrowedBooks($limit = 10) {
        return $this->bookModel->getMostBorrowedBooks($limit);
    }

    private function validateBookData($bookData, $excludeId = null) {
        // Check required fields
        $requiredFields = ['title', 'author', 'category_id', 'total_copies', 'available_copies'];
        foreach ($requiredFields as $field) {
            if (!isset($bookData[$field]) || $bookData[$field] === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
    
        
        // Validate category exists
        if (!$this->categoryModel->findById($bookData['category_id'])) {
            $this->setErrorMessage('Selected category does not exist.');
            return false;
        }
        
        // Validate numeric fields
        $numericFields = ['total_copies', 'available_copies', 'publication_year'];
        foreach ($numericFields as $field) {
            if (isset($bookData[$field]) && $bookData[$field] !== '' && !is_numeric($bookData[$field])) {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' must be numeric.');
                return false;
            }
        }
        
        // Validate copies
        if ($bookData['total_copies'] < 0) {
            $this->setErrorMessage('Total copies cannot be negative.');
            return false;
        }
        
        if ($bookData['available_copies'] < 0) {
            $this->setErrorMessage('Available copies cannot be negative.');
            return false;
        }
        
        if ($bookData['available_copies'] > $bookData['total_copies']) {
            $this->setErrorMessage('Available copies cannot exceed total copies.');
            return false;
        }
        
        return true;
    }


    public function permanentlyDeleteBook($id, $role = 'super-admin') {
        if ($role !== 'super-admin') {
            $this->setErrorMessage('Unauthorized role for permanent deletion.');
            return false;
        }
        
        // Check if book exists
        $book = $this->bookModel->findById($id);
        
        if (!$book) {
            $this->setErrorMessage('Book not found.');
            return false;
        }
        
        // Check if book is archived
        if (!$this->bookModel->isBookArchived($id)) {
            $this->setErrorMessage('Book must be archived before permanent deletion.');
            return false;
        }
        
        // Permanently delete book
        if ($this->bookModel->permanentlyDeleteBook($id)) {
            return true;
        }
        
        $this->setErrorMessage('Cannot delete book with transaction history.');
        return false;
    }

    public function restoreBook($id, $role = 'super-admin') {
        if ($role !== 'super-admin') {
            $this->setErrorMessage('Unauthorized role for restoring book.');
            return false;
        }
        
        // Check if book exists
        $book = $this->bookModel->findById($id);
        
        if (!$book) {
            $this->setErrorMessage('Book not found.');
            return false;
        }
        
        // Check if book is archived
        if (!$this->bookModel->isBookArchived($id)) {
            $this->setErrorMessage('Book is not archived.');
            return false;
        }
        
        // Restore book
        if ($this->bookModel->restoreBook($id)) {
            return true;
        }
        
        $this->setErrorMessage('Failed to restore the book.');
        return false;
    }

    public function getBooksByPublishedYear($year) {
        $sql = "SELECT * FROM books WHERE YEAR(published_date) = ?";
        return $this->db->resultSet($sql, [$year]);
    }

    // In app/services/BookService.php

    public function getAllPublishedYears() {
        $sql = "SELECT DISTINCT YEAR(published_date) AS year FROM books ORDER BY year DESC";
        return $this->db->resultSet($sql);
    }

 
    public function archiveBook($id) {
        // Check if book has active loans
        $transactionModel = new TransactionModel();
        $activeLoans = $transactionModel->query(
            "SELECT COUNT(*) as count FROM transaction WHERE book_id = ? AND return_date IS NULL",
            [$id],
            true
        );
        
        if ($activeLoans && $activeLoans['count'] > 0) {
            $this->setErrorMessage('Cannot archive book with active loans.');
            return false;
        }

        if ($this->bookModel->archiveBook($id)) {
            return true;
        } else {
            $this->setErrorMessage('Failed to archive the book.');
            return false;
        }
    }

 
    public function getArchivedBooks() {
        // Fetch archived books with deleted_at values
        $archivedBooks = $this->bookModel->getArchivedBooks();
        $deletedAtValues = $this->bookModel->getDeletedAtValues();

        // Map deleted_at values by book id
        $deletedAtMap = [];
        if ($deletedAtValues && is_array($deletedAtValues)) {
            foreach ($deletedAtValues as $item) {
                $deletedAtMap[$item['id']] = $item['deleted_at'];
            }
        }

        // Add deleted_at to archived books if missing
        if ($archivedBooks && is_array($archivedBooks)) {
            foreach ($archivedBooks as &$book) {
                if (isset($deletedAtMap[$book['id']])) {
                    $book['deleted_at'] = $deletedAtMap[$book['id']];
                }
            }
            unset($book);
        }

        return $archivedBooks;
    }
}
