<?php
/**
 * BaseModel - Base class for all models
 * Following strict OOP approach with inheritance and encapsulation
 */
class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fields = [];
    protected $fillable = [];
    protected $hidden = [];

    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     * 
     * @param int $id
     * @return array|bool
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->single($sql, [$id]);
    }

    /**
     * Get all records
     * 
     * @param string $orderBy
     * @param string $direction
     * @return array|bool
     */
    public function getAll($orderBy = null, $direction = 'ASC') {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }
        
        return $this->db->resultSet($sql);
    }

    /**
     * Create a new record
     * 
     * @param array $data
     * @return int|bool
     */
    public function create($data) {
        // Filter data to only include fillable fields
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        // Prepare SQL statement
        $fields = array_keys($filteredData);
        $fieldStr = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$fieldStr}) VALUES ({$placeholders})";
        
        // Execute query
        $result = $this->db->query($sql, array_values($filteredData));
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update a record
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        // Filter data to only include fillable fields
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        // Prepare SQL statement
        $fields = array_keys($filteredData);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        
        // Add ID to the end of values array
        $values = array_values($filteredData);
        $values[] = $id;
        
        // Execute query
        return $this->db->query($sql, $values) ? true : false;
    }

    /**
     * Delete a record
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Find records by field value
     * 
     * @param string $field
     * @param mixed $value
     * @return array|bool
     */
    public function findByField($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->resultSet($sql, [$value]);
    }

    /**
     * Find a single record by field value
     * 
     * @param string $field
     * @param mixed $value
     * @return array|bool
     */
    public function findOneByField($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->single($sql, [$value]);
    }

    /**
     * Count records
     * 
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count($where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->single($sql, $params);
        return $result ? $result['count'] : 0;
    }

    /**
     * Custom query
     * 
     * @param string $sql
     * @param array $params
     * @param bool $single
     * @return mixed
     */
    public function query($sql, $params = [], $single = false) {
        if ($single) {
            return $this->db->single($sql, $params);
        } else {
            return $this->db->resultSet($sql, $params);
        }
    }

    /**
     * Filter hidden fields from data
     * 
     * @param array $data
     * @return array
     */
    protected function filterHidden($data) {
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        
        // If it's a single record
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as &$record) {
                foreach ($this->hidden as $field) {
                    if (isset($record[$field])) {
                        unset($record[$field]);
                    }
                }
            }
        } else {
            // It's a single record
            foreach ($this->hidden as $field) {
                if (isset($data[$field])) {
                    unset($data[$field]);
                }
            }
        }
        
        return $data;
    }
}
