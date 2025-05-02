<?php
/**
 * BookService - Handles book-related operations
 */
class BookService extends BaseService {
    private $bookModel;
    private $categoryModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->bookModel = new BookModel();
        $this->categoryModel = new CategoryModel();
        $this->setModel($this->bookModel);
    }

    /**
     * Get book with category
     * 
     * @param int $id
     * @return array|bool
     */
    public function getBookWithCategory($id) {
        return $this->bookModel->getBookWithCategory($id);
    }

    /**
     * Get all books with categories
     * 
     * @return array|bool
     */
    public function getAllBooksWithCategories() {
        return $this->bookModel->getAllBooksWithCategories();
    }

    /**
     * Search books
     * 
     * @param string $term
     * @return array|bool
     */
    public function searchBooks($term) {
        return $this->bookModel->searchBooks($term);
    }

    /**
     * Get books by category
     * 
     * @param int $categoryId
     * @return array|bool
     */
    public function getBooksByCategory($categoryId) {
        return $this->bookModel->getBooksByCategory($categoryId);
    }

    /**
     * Get available books
     * 
     * @return array|bool
     */
    public function getAvailableBooks() {
        return $this->bookModel->getAvailableBooks();
    }

    /**
     * Add new book
     * 
     * @param array $bookData
     * @param int $addedBy
     * @return int|bool
     */
    public function addBook($bookData, $addedBy) {
        // Validate book data
        if (!$this->validateBookData($bookData)) {
            return false;
        }
        
        // Check if ISBN exists
        if ($this->bookModel->isbnExists($bookData['isbn'])) {
            $this->setErrorMessage('A book with this ISBN already exists.');
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

    /**
     * Update book
     * 
     * @param int $id
     * @param array $bookData
     * @return bool
     */
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

    /**
     * Delete book
     * 
     * @param int $id
     * @return bool
     */
    public function deleteBook($id) {
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
        
        // Delete book
        return $this->bookModel->delete($id);
    }

    /**
     * Get all categories
     * 
     * @return array|bool
     */
    public function getAllCategories() {
        return $this->categoryModel->getAll('name', 'ASC');
    }

    /**
     * Get all categories with book count
     * 
     * @return array|bool
     */
    public function getAllCategoriesWithBookCount() {
        return $this->categoryModel->getAllCategoriesWithBookCount();
    }

    /**
     * Add new category
     * 
     * @param array $categoryData
     * @return int|bool
     */
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

    /**
     * Update category
     * 
     * @param int $id
     * @param array $categoryData
     * @return bool
     */
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

    /**
     * Delete category
     * 
     * @param int $id
     * @return bool
     */
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

    /**
     * Get recently added books
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getRecentlyAddedBooks($limit = 10) {
        return $this->bookModel->getRecentlyAddedBooks($limit);
    }

    /**
     * Get most borrowed books
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getMostBorrowedBooks($limit = 10) {
        return $this->bookModel->getMostBorrowedBooks($limit);
    }

    /**
     * Validate book data
     * 
     * @param array $bookData
     * @param int $excludeId
     * @return bool
     */
    private function validateBookData($bookData, $excludeId = null) {
        // Check required fields
        $requiredFields = ['title', 'author', 'isbn', 'category_id', 'total_copies', 'available_copies'];
        foreach ($requiredFields as $field) {
            if (!isset($bookData[$field]) || $bookData[$field] === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate ISBN (simple validation)
        if (!preg_match('/^[0-9-]{10,17}$/', $bookData['isbn'])) {
            $this->setErrorMessage('ISBN must be a valid format (10-17 digits with optional hyphens).');
            return false;
        }
        
        // Check if ISBN exists (excluding current book on update)
        if ($this->bookModel->isbnExists($bookData['isbn'], $excludeId)) {
            $this->setErrorMessage('A book with this ISBN already exists.');
            return false;
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
}
