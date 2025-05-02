<?php
/**
 * BookModel - Represents a Book
 */
class BookModel extends BaseModel {
    protected $table = 'books';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title', 'author', 'isbn', 'description', 'publication_year',
        'publisher', 'category_id', 'total_copies', 'available_copies',
        'is_available', 'added_by', 'created_at', 'updated_at'
    ];

    /**
     * Get book with category
     * 
     * @param int $id
     * @return array|bool
     */
    public function getBookWithCategory($id) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.id = ?";
                
        return $this->db->single($sql, [$id]);
    }

    /**
     * Get all books with categories
     * 
     * @return array|bool
     */
    public function getAllBooksWithCategories() {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                ORDER BY b.title ASC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Search books
     * 
     * @param string $term
     * @return array|bool
     */
    public function searchBooks($term) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.description LIKE ?
                ORDER BY b.title ASC";
                
        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        return $this->db->resultSet($sql, $params);
    }

    /**
     * Get books by category
     * 
     * @param int $categoryId
     * @return array|bool
     */
    public function getBooksByCategory($categoryId) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.category_id = ?
                ORDER BY b.title ASC";
                
        return $this->db->resultSet($sql, [$categoryId]);
    }

    /**
     * Get available books
     * 
     * @return array|bool
     */
    public function getAvailableBooks() {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.is_available = 1 AND b.available_copies > 0
                ORDER BY b.title ASC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Check if book is available
     * 
     * @param int $id
     * @return bool
     */
    public function isBookAvailable($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND is_available = 1 AND available_copies > 0";
        $result = $this->db->single($sql, [$id]);
        return $result ? true : false;
    }

    /**
     * Update book availability
     * 
     * @param int $id
     * @param bool $isAvailable
     * @return bool
     */
    public function updateAvailability($id, $isAvailable) {
        $sql = "UPDATE {$this->table} SET is_available = ? WHERE id = ?";
        return $this->db->query($sql, [$isAvailable ? 1 : 0, $id]) ? true : false;
    }

    /**
     * Increment available copies
     * 
     * @param int $id
     * @return bool
     */
    public function incrementAvailableCopies($id) {
        $sql = "UPDATE {$this->table} SET 
                available_copies = available_copies + 1,
                is_available = 1
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Decrement available copies
     * 
     * @param int $id
     * @return bool
     */
    public function decrementAvailableCopies($id) {
        $sql = "UPDATE {$this->table} SET 
                available_copies = available_copies - 1,
                is_available = CASE WHEN available_copies - 1 <= 0 THEN 0 ELSE 1 END
                WHERE id = ? AND available_copies > 0";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Get recently added books
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getRecentlyAddedBooks($limit = 10) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                ORDER BY b.created_at DESC
                LIMIT ?";
                
        return $this->db->resultSet($sql, [$limit]);
    }

    /**
     * Get most borrowed books
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getMostBorrowedBooks($limit = 10) {
        $sql = "SELECT b.*, c.name as category_name, COUNT(t.id) as borrow_count
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN transactions t ON b.id = t.book_id
                GROUP BY b.id
                ORDER BY borrow_count DESC
                LIMIT ?";
                
        return $this->db->resultSet($sql, [$limit]);
    }

    /**
     * Check if ISBN exists
     * 
     * @param string $isbn
     * @param int $excludeId
     * @return bool
     */
    public function isbnExists($isbn, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE isbn = ?";
        $params = [$isbn];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }
}
