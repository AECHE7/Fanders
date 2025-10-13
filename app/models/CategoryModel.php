<?php
/**
 * CategoryModel - Handles book category operations
 */
class CategoryModel extends BaseModel {
    protected $table = 'book_categories';
 
    public function getAllCategoriesWithCount() {
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c 
                LEFT JOIN books b ON c.id = b.category_id 
                GROUP BY c.id 
                ORDER BY c.name ASC";
        return $this->db->resultSet($sql);
    }
    
  
    public function getCategoryWithCount($categoryId) {
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c 
                LEFT JOIN books b ON c.id = b.category_id 
                WHERE c.id = :id 
                GROUP BY c.id";
        return $this->db->single($sql, ['id' => $categoryId]);
    }

    // In CategoryModel.php
    public function getCategoryBooksCount($categoryId) {
    $sql = "SELECT COUNT(*) as count FROM books WHERE category_id = ?";
    $result = $this->query($sql, [$categoryId], true);
    return $result ? $result['count'] : 0;
    }
    
 
    public function getBooksByCategory($categoryId, $limit = 10, $offset = 0) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM books b 
                JOIN {$this->table} c ON b.category_id = c.id 
                WHERE c.id = :category_id 
                ORDER BY b.title ASC 
                LIMIT :limit OFFSET :offset";
        return $this->db->resultSet($sql, [
            'category_id' => $categoryId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
  
    public function getCategoryStats() {
        $stats = [];
        
        // Get total categories
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_categories'] = $result ? $result['count'] : 0;
        
        // Get categories with book counts
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c 
                LEFT JOIN books b ON c.id = b.category_id 
                GROUP BY c.id 
                ORDER BY book_count DESC";
        $stats['categories'] = $this->db->resultSet($sql);
        
        // Get most used categories
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM {$this->table} c 
                JOIN books b ON c.id = b.category_id 
                GROUP BY c.id 
                ORDER BY book_count DESC 
                LIMIT 5";
        $stats['most_used_categories'] = $this->db->resultSet($sql);
        
        return $stats;
    }
    

    public function addCategory($data) {
        $sql = "INSERT INTO {$this->table} (name, description) 
                VALUES (:name, :description)";
        return $this->db->query($sql, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);
    }
 
    public function updateCategory($categoryId, $data) {
        $sql = "UPDATE {$this->table} 
                SET name = :name, 
                    description = :description 
                WHERE id = :id";
        return $this->db->query($sql, [
            'id' => $categoryId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);
    }
    
    public function deleteCategory($categoryId) {
        // First check if category has books
        $sql = "SELECT COUNT(*) as count FROM books WHERE category_id = :id";
        $result = $this->db->single($sql, ['id' => $categoryId]);
        
        if ($result && $result['count'] > 0) {
            return false; // Cannot delete category with books
        }
        
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        return $this->db->query($sql, ['id' => $categoryId]);
    }
    

    public function getAllForSelect() {
        try {
            $sql = "SELECT id, name, name as category_name FROM {$this->table} WHERE status = 'active' ORDER BY name ASC";
            $result = $this->db->query($sql);
            
            if ($result) {
                $categories = [];
                while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                    $categories[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'category_name' => $row['category_name']
                    ];
                }
                return $categories;
            }
            
            return [];
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return [];
        }
    }
    

    public function getAllCategoriesWithBookCount() {
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM book_categories c 
                LEFT JOIN books b ON c.id = b.category_id 
                GROUP BY c.id 
                ORDER BY c.category_name ASC";
                
        return $this->db->resultSet($sql);
    }
 
    public function categoryNameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE name = :name";
        $params = ['name' => $name];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }
}
