<?php
/**
 * BookModel - Represents a Book
 */
class BookModel extends BaseModel {
    protected $table = 'books';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title', 'author', 'category_id', 'published_year',
        'total_copies', 'available_copies', 'created_at', 'updated_at', 'deleted_at'
    ];

// Get book with category
    public function getBookWithCategory($id) {
        $sql = "SELECT b.*, c.category_name 
                FROM {$this->table} b
                LEFT JOIN book_categories c ON b.category_id = c.id
                WHERE b.id = ?";
                
        return $this->db->single($sql, [$id]);
    }

// Get all books with categories
    public function getAllBooksWithCategories() {
        $sql = "SELECT b.*, c.category_name 
                FROM {$this->table} b
                LEFT JOIN book_categories c ON b.category_id = c.id
                WHERE b.deleted_at IS NULL
                ORDER BY b.title ASC";
                
        return $this->db->resultSet($sql);
    }

//Search books
    public function searchBooks($term) {
        $sql = "SELECT b.*, c.category_name 
                FROM {$this->table} b
                LEFT JOIN book_categories c ON b.category_id = c.id
                WHERE b.title LIKE ? OR b.author LIKE ?
                ORDER BY b.title ASC";
                
        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm];
        
        return $this->db->resultSet($sql, $params);
    }

//Get books by category
    public function getBooksByCategory($categoryId) {
        $sql = "SELECT b.*, c.category_name 
                FROM {$this->table} b
                LEFT JOIN book_categories c ON b.category_id = c.id
                WHERE b.category_id = ? AND b.deleted_at IS NULL
                ORDER BY b.title ASC";
                
        return $this->db->resultSet($sql, [$categoryId]);
    }
//Get available books
    public function getAvailableBooks($limit = 5) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM books b 
                LEFT JOIN book_categories c ON b.category_id = c.id 
                WHERE b.available_copies > 0 AND b.deleted_at IS NULL
                ORDER BY b.created_at DESC 
                LIMIT ?";
        return $this->db->resultSet($sql, [$limit]);
    }
//Get all available books with categories (available copies > 0)
    public function getAllAvailableBooksWithCategories() {
        $sql = "SELECT b.*, c.name as category_name 
                FROM books b 
                LEFT JOIN book_categories c ON b.category_id = c.id 
                WHERE b.available_copies > 0 AND b.deleted_at IS NULL
                ORDER BY b.created_at DESC";
        return $this->db->resultSet($sql);
    }

// Check if book is available
    public function isBookAvailable($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND available_copies > 0";
        $result = $this->db->single($sql, [$id]);
        return $result ? true : false;
    }

//Update available copies
    public function updateAvailableCopies($id, $availableCopies) {
        $sql = "UPDATE {$this->table} SET available_copies = ? WHERE id = ?";
        return $this->db->query($sql, [$availableCopies, $id]) ? true : false;
    }

//Increment available copies
    public function incrementAvailableCopies($id) {
        $sql = "UPDATE {$this->table} SET 
                available_copies = available_copies + 1
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }

//Decrement available copies
    public function decrementAvailableCopies($id) {
        $sql = "UPDATE {$this->table} SET 
                available_copies = available_copies - 1
                WHERE id = ? AND available_copies > 0";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }

//Get total number of books
    public function getTotalBooks() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        return $result ? $result['count'] : 0;
    }

//Get count of borrowed books
    public function getBorrowedBooksCount() {
        $sql = "SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL";
        $result = $this->db->single($sql);
        return $result ? $result['count'] : 0;
    }

//Get recently added books
    public function getRecentlyAddedBooks($limit = 5) {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ?";
        return $this->db->resultSet($sql, [$limit]);
    }

// Get most borrowed books
    public function getMostBorrowedBooks($limit = 10) {
        $sql = "SELECT b.*, c.category_name, COUNT(t.id) as borrow_count
                FROM {$this->table} b
                LEFT JOIN book_categories c ON b.category_id = c.id
                LEFT JOIN transaction t ON b.id = t.book_id
                GROUP BY b.id, c.category_name
                ORDER BY borrow_count DESC
                LIMIT ?";
                
        return $this->db->resultSet($sql, [$limit]);
    }

//Check if book title and author combination exists
    public function bookTitleAuthorExists($title, $author, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE title = ? AND author = ?";
        $params = [$title, $author];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

//Archive a book
    public function archiveBook($id) {
        // Check if book has any active borrowings
        $sql = "SELECT COUNT(*) as count FROM transaction 
                WHERE book_id = ? AND return_date IS NULL";
        $result = $this->db->single($sql, [$id]);
        
        if ($result && $result['count'] > 0) {
            return false; // Book has active borrowings
        }
        
        $sql = "UPDATE {$this->table} SET 
                deleted_at = NOW()
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }


    public function getArchivedBooks() {
        $sql = "SELECT b.*, c.id as category_name 
                FROM books b
                LEFT JOIN book_categories c ON b.category_id = c.id
                WHERE b.deleted_at IS NOT NULL
                ORDER BY b.deleted_at DESC";
        
        return $this->db->resultSet($sql);
    }

 //Get deleted_at values for archived books
    public function getDeletedAtValues() {
        $sql = "SELECT id, deleted_at FROM {$this->table} WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        return $this->db->resultSet($sql);
    }

//Restore an archived book
    public function restoreBook($id) {
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = ?";
        return $this->db->query($sql, [$id]) ? true : false;
    }

// Permanently delete a book
    public function permanentlyDeleteBook($id) {
        // Check if book has transaction history first
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE book_id = ?";
        $result = $this->db->single($sql, [$id]);
        
        if ($result && $result['count'] > 0) {
            return false; // Cannot delete books with transaction history
        }
        
        // If no transaction history, proceed with deletion
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]) ? true : false;
    }

//Check if book is archived
    public function isBookArchived($id) {
        $sql = "SELECT deleted_at FROM {$this->table} WHERE id = ?";
        $result = $this->db->single($sql, [$id]);
        return $result && $result['deleted_at'] !== null;
    }

    public function bulkRestoreBooks($ids) {
        if (empty($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE id IN ($placeholders)";
        
        return $this->db->query($sql, $ids) ? true : false;
    }

    public function bulkPermanentlyDeleteBooks($ids) {
        if (empty($ids)) {
            return ['success' => [], 'failed' => []];
        }
        
        $results = ['success' => [], 'failed' => []];
        
        // Check each book individually
        foreach ($ids as $id) {
            if ($this->permanentlyDeleteBook($id)) {
                $results['success'][] = $id;
            } else {
                $results['failed'][] = $id;
            }
        }
        
        return $results;
    }


}
