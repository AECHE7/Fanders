<?php
/**
 * BaseService - Base class for all services
 * Following strict OOP approach with inheritance and encapsulation.
 * Provides model access, validation, and transactional support.
 */
// Ensure the Database class is available
require_once __DIR__ . '/Database.php';

class BaseService {
    protected $db;
    protected $model;
    protected $errorMessage;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Sets the model instance that the service will interact with.
     * This is useful for dependency injection or dynamic model setting.
     * @param object $model
     */
    public function setModel($model) {
        $this->model = $model;
    }


    // --- CRUD Delegation Methods ---

    public function getAll($orderBy = null, $direction = 'ASC') {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        return $this->model->getAll($orderBy, $direction);
    }

    public function getById($id) {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        return $this->model->findById($id);
    }


    public function create($data) {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        // The specific service (e.g., LoanService) should perform validation BEFORE calling this.
        $result = $this->model->create($data);
        
        // Invalidate related cache after successful creation
        if ($result) {
            $this->invalidateCache();
        }
        
        return $result;
    }

    public function update($id, $data) {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        $result = $this->model->update($id, $data);
        
        // Invalidate related cache after successful update
        if ($result) {
            $this->invalidateCache();
        }
        
        return $result;
    }

    public function delete($id) {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        $result = $this->model->delete($id);
        
        // Invalidate related cache after successful deletion
        if ($result) {
            $this->invalidateCache();
        }
        
        return $result;
    }

    /**
     * Invalidate relevant cache entries when data changes
     * Override in specific services to handle cache invalidation
     */
    protected function invalidateCache() {
        // Base implementation - can be overridden in specific services
        if (class_exists('CacheUtility')) {
            require_once __DIR__ . '/../utilities/CacheUtility.php';
            // Clean expired entries as a basic cleanup
            CacheUtility::cleanExpired();
        }
    }

    public function getByField($field, $value) {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        return $this->model->findByField($field, $value);
    }

    public function getOneByField($field, $value) {
        if (!$this->model) { $this->setErrorMessage("Model not set."); return false; }
        return $this->model->findOneByField($field, $value);
    }

    // --- Error & Utility Methods ---

    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Enhanced error message setting with logging
     * @param string $message Error message
     * @param array $context Additional context for logging
     */
    public function setErrorMessage($message, $context = []) {
        $this->errorMessage = $message;
        
        // Log the error for debugging
        if (class_exists('ErrorHandler')) {
            require_once __DIR__ . '/../utilities/ErrorHandler.php';
            ErrorHandler::error($message, array_merge($context, [
                'service_class' => get_class($this)
            ]));
        }
    }

    /**
     * General purpose validation helper.
     * @param array $data Input data to validate.
     * @param array $rules Validation rules (e.g., ['name' => 'required|min:3', 'email' => 'required|email']).
     * @return bool True if validation passes, false otherwise (sets error message).
     */
    protected function validate($data, $rules) {
        foreach ($rules as $field => $rule) {
            
            // Check if field is required but missing or empty
            if (strpos($rule, 'required') !== false && (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === ''))) {
                $this->setErrorMessage(ucfirst($field) . " is required.");
                return false;
            }
            
            if (isset($data[$field])) {
                $value = $data[$field];

                // Email validation
                if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->setErrorMessage(ucfirst($field) . " must be a valid email address.");
                    return false;
                }
                
                // Numeric validation
                if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                    $this->setErrorMessage(ucfirst($field) . " must be a number.");
                    return false;
                }

                // Positive numeric validation (Crucial for loans/payments)
                if (strpos($rule, 'positive') !== false && (float)$value <= 0) {
                    $this->setErrorMessage(ucfirst($field) . " must be a positive value.");
                    return false;
                }
                
                // Min length validation
                if (preg_match('/min:(\d+)/', $rule, $matches)) {
                    $min = $matches[1];
                    if (is_numeric($value)) {
                        if ((float)$value < $min) {
                            $this->setErrorMessage(ucfirst($field) . " must be at least {$min}.");
                            return false;
                        }
                    } else {
                        if (strlen($value) < $min) {
                            $this->setErrorMessage(ucfirst($field) . " must be at least {$min} characters.");
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }

        /**
     * Enhanced transaction wrapper with better error handling
     * @param callable $callback The database operations to execute
     * @return mixed Result of the transaction
     * @throws Exception If transaction fails
     */
    public function transaction($callback) {
        try {
            $this->db->beginTransaction();
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            
            // Use ErrorHandler for better logging
            require_once __DIR__ . '/../utilities/ErrorHandler.php';
            $userMessage = ErrorHandler::handleDatabaseError('transaction', $e, [
                'service_class' => get_class($this)
            ]);
            
            $this->setErrorMessage($userMessage);
            return false;
        }
    }
}
