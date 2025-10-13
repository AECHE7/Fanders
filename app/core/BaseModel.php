<?php

class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $lastError = null;
    protected $fields = [];
    protected $fillable = [];
    protected $hidden = [];


    public function __construct() {
        $this->db = Database::getInstance();
    }


    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->single($sql, [$id]);
    }

    public function getAll($orderBy = null, $direction = 'ASC') {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }
        
        return $this->db->resultSet($sql);
    }


    public function create($data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
            
            if (empty($filteredData)) {
                $this->setLastError('No valid data provided for creation.');
                return false;
            }
            
            // Prepare SQL statement
            $fields = array_keys($filteredData);
            $fieldStr = implode(', ', $fields);
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$fieldStr}) VALUES ({$placeholders})";
            
            // Execute query
            $result = $this->db->query($sql, array_values($filteredData));
            
            if ($result) {
                return (int) $this->db->lastInsertId();
            }
            
            $this->setLastError('Failed to create record.');
            return false;
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }


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


    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql, [$id]) ? true : false;
    }

    public function findByField($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->resultSet($sql, [$value]);
    }

    public function findOneByField($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->single($sql, [$value]);
    }


    public function count($field = null, $value = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if ($field && $value !== null) {
            $sql .= " WHERE {$field} = ?";
            $params[] = $value;
        }
        
        $result = $this->db->single($sql, $params);
        return $result ? $result['count'] : 0;
    }

 
    public function query($sql, $params = [], $single = false) {
        if ($single) {
            return $this->db->single($sql, $params);
        } else {
            return $this->db->resultSet($sql, $params);
        }
    }

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

  
    protected function setLastError($msg) {
        $this->lastError = $msg;
    }


    public function getLastError() {
        return $this->lastError;
    }

    protected function safeQuery($sql, $params = []) {
        try {
            $result = $this->db->query($sql, $params);
            if (!$result) {
                $this->setLastError('Unknown DB error.');
            }
            return $result;
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }
}
