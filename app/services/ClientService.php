<?php
/**
 * ClientService - Handles client (borrower) operations for Fanders Microfinance.
 * This service layer implements the business logic for creating, viewing, and
 * managing the status of client accounts.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/../utilities/CacheUtility.php';
require_once __DIR__ . '/../utilities/PaginationUtility.php';
// Note: LoanModel/PaymentModel are not directly included here; they should be
// included in LoanService or a dedicated Reporting Service for separation of concerns.

class ClientService extends BaseService {
    private $clientModel;
    private $cache;
    private $pagination;

    public function __construct() {
        parent::__construct();
        $this->clientModel = new ClientModel();
        $this->cache = new CacheUtility();
        $this->setModel($this->clientModel);
    }

    /**
     * Retrieves a single client with aggregated loan summary data.
     * @param int $id Client ID.
     * @return array|false
     */
    public function getClientWithSummary($id) {
        // Uses the model function to aggregate loan counts and amounts
        return $this->clientModel->getClientWithLoanSummary($id);
    }

    /**
     * Retrieves all client records.
     * @return array
     */
    public function getAllClients($page = null, $limit = null, $filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        // Sanitize and validate filters
        $filters = FilterUtility::sanitizeFilters($filters, [
            'allowed_statuses' => ['active', 'inactive', 'blacklisted']
        ]);
        $filters = FilterUtility::validateDateRange($filters);
        
        // If pagination parameters are provided, use paginated method
        if ($page !== null && $limit !== null) {
            return $this->clientModel->getAllClientsPaginated($limit, ($page - 1) * $limit, $filters);
        }
        
        // Otherwise, get all clients with filters
        return $this->clientModel->getAllClients($filters);
    }

    /**
     * Enhanced method to get all clients formatted for select dropdowns with caching
     * @param array $filters Filter parameters (defaults to active clients only)
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getAllForSelect($filters = [], $useCache = true) {
        // Default to active clients only for dropdowns
        if (empty($filters['status'])) {
            $filters['status'] = ClientModel::STATUS_ACTIVE;
        }

        if (!$useCache) {
            $clients = $this->getAllClients($filters);
        } else {
            require_once __DIR__ . '/../utilities/CacheUtility.php';

            $cacheKey = CacheUtility::generateKey('clients_dropdown', $filters);

            $clients = $this->cache->remember($cacheKey, 600, function() use ($filters) {
                return $this->getAllClients($filters);
            });
        }

        // Ensure $clients is an array
        if (!is_array($clients)) {
            $clients = [];
        }

        $formatted = [];

        foreach ($clients as $client) {
            $formatted[] = [
                'id' => $client['id'],
                'name' => $client['name'],
                'status' => $client['status']
            ];
        }

        return $formatted;
    }

    /**
     * Get client statistics with caching
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getClientStats($useCache = true) {
        if (!$useCache) {
            return $this->clientModel->getClientStats();
        }

        require_once __DIR__ . '/../utilities/CacheUtility.php';

        $cacheKey = CacheUtility::generateKey('client_stats');

        return $this->cache->remember($cacheKey, 300, function() {
            return $this->clientModel->getClientStats();
        });
    }

    /**
     * Invalidate client-related caches
     */
    public function invalidateCache() {
        // Invalidate client statistics cache
        $this->cache->delete(CacheUtility::generateKey('client_stats'));
        
        // Invalidate dropdown cache (all variations)
        $filters = [
            ['status' => 'active'],
            ['status' => 'inactive'],
            ['status' => 'blacklisted'],
            [] // Default filter
        ];
        
        foreach ($filters as $filter) {
            $this->cache->delete(CacheUtility::generateKey('clients_dropdown', $filter));
        }
        
        // Clean expired entries if method exists
        if (method_exists($this->cache, 'cleanExpired')) {
            $this->cache->cleanExpired();
        }
    }

    /**
     * Enhanced search clients with filtering
     * @param string $term Search term
     * @param array $additionalFilters Additional filters
     * @return array
     */
    public function searchClients($term, $additionalFilters = []) {
        $result = $this->clientModel->searchClients($term, $additionalFilters);
        return is_array($result) ? $result : [];
    }

    /**
     * Enhanced method to get clients by status with additional filtering
     * @param string $status Client status
     * @param array $additionalFilters Additional filters
     * @return array
     */
    public function getClientsByStatus($status, $additionalFilters = []) {
        $result = $this->clientModel->getClientsByStatus($status, $additionalFilters);
        return is_array($result) ? $result : [];
    }

    /**
     * Enhanced method to get active clients with filtering
     * @param array $filters Additional filters
     * @return array
     */
    public function getActiveClients($filters = []) {
        $result = $this->clientModel->getActiveClients($filters);
        return is_array($result) ? $result : [];
    }

    /**
     * Get total count of clients for pagination
     * @param array $filters Filter parameters
     * @return int
     */
    public function getTotalClientsCount($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        $filters = FilterUtility::sanitizeFilters($filters);
        $filters = FilterUtility::validateDateRange($filters);
        
        return $this->clientModel->getTotalClientsCount($filters);
    }



    /**
     * Fetches all loan records for a given client.
     * NOTE: Outstanding balance calculation must be handled in the dedicated LoanService.
     * @param int $clientId
     * @return array
     */
    public function getClientLoanHistory($clientId) {
        return $this->clientModel->getClientLoanHistory($clientId);
    }

    /**
     * Fetches currently active loan records for a given client.
     * @param int $clientId
     * @return array
     */
    public function getClientCurrentLoans($clientId) {
        return $this->clientModel->getClientCurrentLoans($clientId);
    }

    /**
     * Creates a new client record after business validation.
     * @param array $clientData
     * @param int $createdBy User ID who created the client
     * @return int|false New client ID on success.
     */
    public function createClient($clientData, $createdBy = null) {
        // 1. Validate client data (uniqueness, required fields, age)
        if (!$this->validateClientData($clientData)) {
            return false;
        }

        // 2. Set default status and create timestamps via BaseModel/Model's create override
        $clientData['status'] = $clientData['status'] ?? ClientModel::STATUS_ACTIVE;
        
        // 3. Convert empty strings to NULL for unique fields (PostgreSQL unique constraint compatibility)
        // Empty strings violate unique constraints, but NULL values don't
        if (isset($clientData['email']) && trim($clientData['email']) === '') {
            $clientData['email'] = null;
        }
        if (isset($clientData['identification_number']) && trim($clientData['identification_number']) === '') {
            $clientData['identification_number'] = null;
        }

        // 4. Create client
        $newId = $this->clientModel->create($clientData);

        if (!$newId) {
             $modelError = $this->clientModel->getLastError();
             $dbError = $this->db->getError();
             
             // Construct a detailed error message
             $errorDetails = [];
             if ($modelError) $errorDetails[] = "Model: $modelError";
             if ($dbError) $errorDetails[] = "Database: $dbError";
             
             $errorMessage = !empty($errorDetails) 
                 ? 'Failed to create client: ' . implode('; ', $errorDetails)
                 : 'Failed to create client due to unknown database error.';
             
             $this->setErrorMessage($errorMessage);
             
             // Log for debugging
             error_log("ClientService::createClient failed - " . $errorMessage);
             
             return false;
        }

        // 5. Clear relevant caches
        $this->cache->delete('client_stats');

        // 6. Log transaction for audit trail
        if (class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, client_id)
            $transactionService->logClientTransaction('created', $createdBy, $newId, [
                'client_data' => $clientData
            ]);
        }

        return $newId;
    }

    /**
     * Updates an existing client's record.
     * @param int $id
     * @param array $clientData
     * @return bool
     */
    public function updateClient($id, $clientData) {
        // 1. Get existing client
        $existingClient = $this->clientModel->findById($id);

        if (!$existingClient) {
            $this->setErrorMessage('Client not found.');
            return false;
        }

        // 2. Validate client data (excluding current ID from uniqueness checks)
        if (!$this->validateClientData($clientData, $id)) {
            return false;
        }

        // 3. Convert empty strings to NULL for unique fields (PostgreSQL unique constraint compatibility)
        if (isset($clientData['email']) && trim($clientData['email']) === '') {
            $clientData['email'] = null;
        }
        if (isset($clientData['identification_number']) && trim($clientData['identification_number']) === '') {
            $clientData['identification_number'] = null;
        }

        // 4. Update client
        $result = $this->clientModel->update($id, $clientData);
        
        // 5. Log client update transaction
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, client_id)
            $transactionService->logClientTransaction('updated', $_SESSION['user_id'] ?? null, $id, [
                'client_id' => $id,
                'updated_fields' => array_keys($clientData)
            ]);
        }
        
        return $result;
    }

    /**
     * Activates an inactive client account.
     * @param int $id
     * @return bool
     */
    public function activateClient($id) {
        $result = $this->clientModel->updateStatus($id, ClientModel::STATUS_ACTIVE);
        
        // Log client activation
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, client_id)
            $transactionService->logClientTransaction('status_changed', $_SESSION['user_id'] ?? null, $id, [
                'client_id' => $id,
                'new_status' => 'active',
                'action' => 'activated'
            ]);
        }
        
        return $result;
    }

    /**
     * Deactivates a client account to preserve historical records (Section 4 Requirement).
     * @param int $id
     * @return bool
     */
    public function deactivateClient($id) {
        $result = $this->clientModel->updateStatus($id, ClientModel::STATUS_INACTIVE);
        
        // Log client deactivation
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, client_id)
            $transactionService->logClientTransaction('status_changed', $_SESSION['user_id'] ?? null, $id, [
                'client_id' => $id,
                'new_status' => 'inactive',
                'action' => 'deactivated'
            ]);
        }
        
        return $result;
    }

    /**
     * Marks a client as blacklisted.
     * @param int $id
     * @return bool
     */
    public function blacklistClient($id) {
        $result = $this->clientModel->updateStatus($id, ClientModel::STATUS_BLACKLISTED);
        
        // Log client blacklisting
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, client_id)
            $transactionService->logClientTransaction('status_changed', $_SESSION['user_id'] ?? null, $id, [
                'client_id' => $id,
                'new_status' => 'blacklisted',
                'action' => 'blacklisted'
            ]);
        }
        
        return $result;
    }

    /**
     * Deletes a client record. Only possible if no active loans exist (database FK enforces this).
     * Since we must preserve historical loan records, we prefer DEACTIVATE.
     * @param int $id
     * @return bool
     */
    public function deleteClient($id) {
        // Although Deactivate is preferred, we keep delete functionality for cleanup if no loans exist.
        
        // Step 1: Check for active/pending loans
        $activeLoans = $this->clientModel->getClientCurrentLoans($id);
        if (!empty($activeLoans)) {
            $loanStatuses = array_unique(array_column($activeLoans, 'status'));
            $statusList = implode(', ', $loanStatuses);
            $this->setErrorMessage("Cannot delete client with active/pending loans (Status: {$statusList}). Only clients with Completed or Defaulted loans can be deleted. Consider deactivating the client instead.");
            return false;
        }
        
        // Step 2: Check for ANY loan history (including completed/defaulted)
        $allLoans = $this->clientModel->getClientLoanHistory($id);
        if (!empty($allLoans)) {
            $this->setErrorMessage("Cannot delete client with loan history. Client has " . count($allLoans) . " loan record(s) that must be preserved for audit purposes. Consider deactivating the client instead.");
            return false;
        }
        
        // Step 3: Verify client exists before attempting deletion
        $client = $this->clientModel->findById($id);
        if (!$client) {
            $this->setErrorMessage("Client not found with ID: {$id}");
            return false;
        }
        
        // Step 4: Attempt deletion with proper error handling
        try {
            $result = $this->clientModel->delete($id);
            
            if (!$result) {
                $this->setErrorMessage("Failed to delete client due to database constraints or related records. Consider deactivating the client instead.");
                return false;
            }
            
            // Log successful deletion
            if (class_exists('TransactionService')) {
                $transactionService = new TransactionService();
                // Correct parameter order: (action, acting_user_id, client_id)
                $transactionService->logClientTransaction('deleted', $_SESSION['user_id'] ?? null, $id, [
                    'client_id' => $id,
                    'client_name' => $client['name'] ?? 'Unknown'
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Client deletion failed for ID {$id}: " . $e->getMessage());
            
            // Check if it's a foreign key constraint error
            if (strpos(strtolower($e->getMessage()), 'foreign key') !== false || 
                strpos(strtolower($e->getMessage()), 'constraint') !== false) {
                $this->setErrorMessage("Cannot delete client due to related records in the system (payments, documents, etc.). For data integrity, consider deactivating the client instead.");
            } else {
                $this->setErrorMessage("Database error occurred during deletion: " . $e->getMessage());
            }
            
            return false;
        }
    }
    
    // --- Validation Logic (Based on Client Creation/Update Rules) ---

    private function validateClientData($clientData, $excludeId = null) {
        // Check required fields
        $requiredFields = ['name', 'phone_number', 'address', 'identification_type', 'identification_number'];
        foreach ($requiredFields as $field) {
            // Note: Email/DOB are not strictly required by this existing validation block, but are highly recommended.
            if (!isset($clientData[$field]) || empty(trim($clientData[$field]))) {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }

        // Validate email format and uniqueness (if provided)
        if (!empty($clientData['email'])) {
            if (!filter_var($clientData['email'], FILTER_VALIDATE_EMAIL)) {
                $this->setErrorMessage('Invalid email format.');
                return false;
            }
            if ($this->clientModel->emailExists($clientData['email'], $excludeId)) {
                $this->setErrorMessage('Email address already registered.');
                return false;
            }
        }

        // Validate phone number format and uniqueness
        if (!preg_match('/^\d{8,15}$/', $clientData['phone_number'])) {
            $this->setErrorMessage('Phone number must be 8-15 digits.');
            return false;
        }
        if ($this->clientModel->phoneNumberExists($clientData['phone_number'], $excludeId)) {
            $this->setErrorMessage('Phone number already exists.');
            return false;
        }

        // Check if identification number already exists
        if ($this->clientModel->identificationExists($clientData['identification_number'], $excludeId)) {
            $this->setErrorMessage('Identification number already exists.');
            return false;
        }

        // Validate date of birth and age requirement
        if (!empty($clientData['date_of_birth'])) {
            $dob = DateTime::createFromFormat('Y-m-d', $clientData['date_of_birth']);
            $today = new DateTime();
            // Basic date format validation and age check (must be at least 18)
            if (!$dob || $dob->format('Y-m-d') !== $clientData['date_of_birth'] || $today->diff($dob)->y < 18) {
                $this->setErrorMessage('Client must be at least 18 years old and Date of Birth must be valid (Y-m-d).');
                return false;
            }
        }

        return true;
    }
}
