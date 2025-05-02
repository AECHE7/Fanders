<?php
/**
 * CategoryModel - Represents a Book Category
 */
class CategoryModel extends BaseModel {
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'description', 'created_at', 'updated_at'];

    /**
     * Get category with book count
     * 
     * @param int $id
     * @return array|bool
     */
    public function getCategoryWithBookCount($id) {
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c
                LEFT JOIN books b ON c.id = b.category_id
                WHERE c.id = ?
                GROUP BY c.id";
                
        return $this->db->single($sql, [$id]);
    }

    /**
     * Get all categories with book count
     * 
     * @return array|bool
     */
    public function getAllCategoriesWithBookCount() {
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c
                LEFT JOIN books b ON c.id = b.category_id
                GROUP BY c.id
                ORDER BY c.name ASC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Check if category name exists
     * 
     * @param string $name
     * @param int $excludeId
     * @return bool
     */
    public function categoryNameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Get most popular categories
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getMostPopularCategories($limit = 5) {
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c
                JOIN books b ON c.id = b.category_id
                GROUP BY c.id
                ORDER BY book_count DESC
                LIMIT ?";
                
        return $this->db->resultSet($sql, [$limit]);
    }

    /**
     * Get categories with books availability
     * 
     * @return array|bool
     */
    public function getCategoriesWithAvailability() {
        $sql = "SELECT c.*, 
                COUNT(b.id) as total_books,
                SUM(b.available_copies) as available_books
                FROM {$this->table} c
                LEFT JOIN books b ON c.id = b.category_id
                GROUP BY c.id
                ORDER BY c.name ASC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Get category books count
     * 
     * @param int $id
     * @return int
     */
    public function getCategoryBooksCount($id) {
        $sql = "SELECT COUNT(*) as count FROM books WHERE category_id = ?";
        $result = $this->db->single($sql, [$id]);
        return $result ? $result['count'] : 0;
    }
}
