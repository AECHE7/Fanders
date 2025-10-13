<?php
/**
 * BaseService - Base class for all services
 * Following strict OOP approach with inheritance and encapsulation
 */
require_once __DIR__ . '/Database.php';

class BaseService {
    protected $db;
    protected $model;
    protected $errorMessage;

    public function __construct() {
        $this->db = Database::getInstance();
    }


    public function setModel($model) {
        $this->model = $model;
    }


    public function getAll($orderBy = null, $direction = 'ASC') {
        return $this->model->getAll($orderBy, $direction);
    }

    public function getById($id) {
        return $this->model->findById($id);
    }


    public function create($data) {
        return $this->model->create($data);
    }

    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    public function delete($id) {
        return $this->model->delete($id);
    }

 
    public function getByField($field, $value) {
        return $this->model->findByField($field, $value);
    }

    public function getOneByField($field, $value) {
        return $this->model->findOneByField($field, $value);
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }

    protected function setErrorMessage($message) {
        $this->errorMessage = $message;
    }

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
