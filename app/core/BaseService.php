<?php
/**
 * BaseService - Base class for all services
 * Following strict OOP approach with inheritance and encapsulation
 */
class BaseService {
    protected $db;
    protected $model;
    protected $errorMessage;

    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Set model for the service
     * 
     * @param BaseModel $model
     * @return void
     */
    public function setModel($model) {
        $this->model = $model;
    }

    /**
     * Get all records
     * 
     * @param string $orderBy
     * @param string $direction
     * @return array|bool
     */
    public function getAll($orderBy = null, $direction = 'ASC') {
        return $this->model->getAll($orderBy, $direction);
    }

    /**
     * Get record by ID
     * 
     * @param int $id
     * @return array|bool
     */
    public function getById($id) {
        return $this->model->findById($id);
    }

    /**
     * Create new record
     * 
     * @param array $data
     * @return int|bool
     */
    public function create($data) {
        return $this->model->create($data);
    }

    /**
     * Update record
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    /**
     * Delete record
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        return $this->model->delete($id);
    }

    /**
     * Get records by field
     * 
     * @param string $field
     * @param mixed $value
     * @return array|bool
     */
    public function getByField($field, $value) {
        return $this->model->findByField($field, $value);
    }

    /**
     * Get single record by field
     * 
     * @param string $field
     * @param mixed $value
     * @return array|bool
     */
    public function getOneByField($field, $value) {
        return $this->model->findOneByField($field, $value);
    }

    /**
     * Get error message
     * 
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Set error message
     * 
     * @param string $message
     * @return void
     */
    protected function setErrorMessage($message) {
        $this->errorMessage = $message;
    }

    /**
     * Validate data against rules
     * 
     * @param array $data
     * @param array $rules
     * @return bool
     */
    protected function validate($data, $rules) {
        foreach ($rules as $field => $rule) {
            if (strpos($rule, 'required') !== false && (!isset($data[$field]) || empty($data[$field]))) {
                $this->setErrorMessage("{$field} is required");
                return false;
            }
            
            if (isset($data[$field])) {
                // Email validation
                if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->setErrorMessage("{$field} must be a valid email");
                    return false;
                }
                
                // Numeric validation
                if (strpos($rule, 'numeric') !== false && !is_numeric($data[$field])) {
                    $this->setErrorMessage("{$field} must be numeric");
                    return false;
                }
                
                // Min length validation
                if (preg_match('/min:(\d+)/', $rule, $matches)) {
                    $min = $matches[1];
                    if (strlen($data[$field]) < $min) {
                        $this->setErrorMessage("{$field} must be at least {$min} characters");
                        return false;
                    }
                }
                
                // Max length validation
                if (preg_match('/max:(\d+)/', $rule, $matches)) {
                    $max = $matches[1];
                    if (strlen($data[$field]) > $max) {
                        $this->setErrorMessage("{$field} must not exceed {$max} characters");
                        return false;
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Execute in transaction
     * 
     * @param callable $callback
     * @return mixed
     */
    protected function transaction($callback) {
        try {
            $this->db->beginTransaction();
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }
}
