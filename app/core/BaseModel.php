<?php
/**
 * BaseModel provides common CRUD and utility methods for all application models.
 * It enforces data filtering and uses the Database class for secure interactions.
 */
class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $lastError = null;
    protected $fields = []; // Not strictly enforced but good for documentation
    protected $fillable = []; // Fields allowed for mass assignment (create/update)
    protected $hidden = ['password']; // Fields to exclude from output results


    public function __construct() {
        // Initializes the Database singleton instance
        set_time_limit(300); // Increase time limit to 5 minutes for database operations
        $this->db = Database::getInstance();
    }


    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = $this->db->single($sql, [$id]);
        return $this->filterHidden($result); // Apply filtering after fetch
    }

    public function getAll($orderBy = null, $direction = 'ASC') {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            // Note: ORDER BY column and direction must be safe/whitelisted in a real app
            $safeDirection = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$orderBy} {$safeDirection}";
        }
        
        $results = $this->db->resultSet($sql);
        return $this->filterHidden($results); // Apply filtering after fetch
    }


    public function create($data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
            
            if (empty($filteredData)) {
                $this->setLastError('No valid data provided for creation.');
                error_log("BaseModel::create failed - No valid fillable data. Table: {$this->table}, Data keys: " . implode(', ', array_keys($data)));
                return false;
            }
            
            // Prepare SQL statement (uses '?' placeholders for security)
            $fields = array_keys($filteredData);
            $fieldStr = implode(', ', $fields);
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$fieldStr}) VALUES ({$placeholders})";
            
            // Execute query
            $result = $this->db->query($sql, array_values($filteredData));
            
            if ($result) {
                return (int) $this->db->lastInsertId();
            }
            
            // Get database error if available
            $dbError = $this->db->getError();
            $errorMsg = 'Failed to create record' . ($dbError ? ": $dbError" : '.');
            $this->setLastError($errorMsg);
            error_log("BaseModel::create failed - Table: {$this->table}, Error: {$errorMsg}");
            return false;
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $this->setLastError($errorMsg);
            error_log("BaseModel::create exception - Table: {$this->table}, Error: {$errorMsg}");
            return false;
        }
    }


    public function update($id, $data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = array_intersect_key($data, array_flip($this->fillable));

            if (empty($filteredData)) {
                $this->setLastError('No valid data provided for update.');
                return false;
            }
            
            // Prepare SQL statement
            $fields = array_keys($filteredData);
            // Create SET clause: field1 = ?, field2 = ?, ...
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
            
            // Add ID to the end of values array
            $values = array_values($filteredData);
            $values[] = $id;
            
            // Execute query
            return $this->db->query($sql, $values) ? true : false;

        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Updates a single status field (e.g., 'active' or 'Overdue').
     * @param int $id Record ID.
     * @param string $statusValue The new status value.
     * @param string $statusField The name of the status field (defaults to 'status').
     * @return bool
     */
    public function updateStatus($id, $statusValue, $statusField = 'status') {
        $sql = "UPDATE {$this->table} SET {$statusField} = ? WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql, [$statusValue, $id]) ? true : false;
    }


    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->query($sql, [$id]);
            
            if ($stmt === false) {
                $this->setLastError("Failed to execute delete query for {$this->table} with ID: {$id}");
                error_log("BaseModel::delete failed - Query execution failed. Table: {$this->table}, ID: {$id}");
                return false;
            }
            
            // Check if any rows were actually affected
            $rowsAffected = $stmt->rowCount();
            if ($rowsAffected === 0) {
                $this->setLastError("No records found to delete in {$this->table} with ID: {$id}");
                error_log("BaseModel::delete warning - No rows affected. Table: {$this->table}, ID: {$id}");
                return false;
            }
            
            error_log("BaseModel::delete success - Table: {$this->table}, ID: {$id}, Rows affected: {$rowsAffected}");
            return true;
            
        } catch (Exception $e) {
            $this->setLastError("Delete operation failed: " . $e->getMessage());
            error_log("BaseModel::delete exception - Table: {$this->table}, ID: {$id}, Error: " . $e->getMessage());
            throw $e; // Re-throw so calling code can handle it
        }
    }

    public function findByField($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        $results = $this->db->resultSet($sql, [$value]);
        return $this->filterHidden($results);
    }

    public function findOneByField($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        $result = $this->db->single($sql, [$value]);
        return $this->filterHidden($result);
    }


    public function count($field = null, $value = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if ($field && $value !== null) {
            $sql .= " WHERE {$field} = ?";
            $params[] = $value;
        }
        
        $result = $this->db->single($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }

 
    public function query($sql, $params = [], $single = false) {
        if ($single) {
            $result = $this->db->single($sql, $params);
            return $this->filterHidden($result);
        } else {
            $results = $this->db->resultSet($sql, $params);
            return $this->filterHidden($results);
        }
    }

    /**
     * Internal method to remove fields defined in $this->hidden from the result set.
     */
    protected function filterHidden($data) {
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        
        // Handle array of records (resultSet)
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $key => $record) {
                foreach ($this->hidden as $field) {
                    if (isset($data[$key][$field])) {
                        unset($data[$key][$field]);
                    }
                }
            }
        } else {
            // Handle single record (single)
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

    public function getTable() {
        return $this->table;
    }
    
    // safeQuery logic is now covered by the global try/catch in the public methods.
}
