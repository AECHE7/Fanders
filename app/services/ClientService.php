<?php
/**
 * ClientService - Handles client (borrower) operations for Fanders Microfinance.
 * This service layer implements the business logic for creating, viewing, and
 * managing the status of client accounts.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/ClientModel.php';
// Note: LoanModel/PaymentModel are not directly included here; they should be 
// included in LoanService or a dedicated Reporting Service for separation of concerns.

class ClientService extends BaseService {
    private $clientModel;

    public function __construct() {
        parent::__construct();
        $this->clientModel = new ClientModel();
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
     * Enhanced method to get all clients with filtering support
     * @param array $filters Filter parameters
     * @return array
     */
    public function getAllClients($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        // Validate and sanitize filters
        $filters = FilterUtility::sanitizeFilters($filters, [
            'allowed_statuses' => [
                ClientModel::STATUS_ACTIVE,
                ClientModel::STATUS_INACTIVE,
                ClientModel::STATUS_BLACKLISTED
            ]
        ]);
        
        $filters = FilterUtility::validateDateRange($filters);
        
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
            $service = $this;
            
            $clients = CacheUtility::remember($cacheKey, function() use ($filters, $service) {
                return $service->getAllClients($filters);
            }, 600); // Cache for 10 minutes
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
        $clientModel = $this->clientModel;
        
        return CacheUtility::remember($cacheKey, function() use ($clientModel) {
            return $clientModel->getClientStats();
        }, 300); // Cache for 5 minutes
    }

    /**
     * Invalidate client-related caches
     */
    public function invalidateCache() {
        // Invalidate client statistics cache
        CacheUtility::forget(CacheUtility::generateKey('client_stats'));
        
        // Invalidate dropdown cache (all variations)
        $filters = [
            ['status' => 'active'],
            ['status' => 'inactive'],
            ['status' => 'blacklisted'],
            [] // Default filter
        ];
        
        foreach ($filters as $filter) {
            CacheUtility::forget(CacheUtility::generateKey('clients_dropdown', $filter));
        }
        
        // Clean expired entries
        CacheUtility::cleanExpired();
    }

    /**
     * Enhanced search clients with filtering
     * @param string $term Search term
     * @param array $additionalFilters Additional filters
     * @return array
     */
    public function searchClients($term, $additionalFilters = []) {
        return $this->clientModel->searchClients($term, $additionalFilters);
    }

    /**
     * Enhanced method to get clients by status with additional filtering
     * @param string $status Client status
     * @param array $additionalFilters Additional filters
     * @return array
     */
    public function getClientsByStatus($status, $additionalFilters = []) {
        return $this->clientModel->getClientsByStatus($status, $additionalFilters);
    }

    /**
     * Enhanced method to get active clients with filtering
     * @param array $filters Additional filters
     * @return array
     */
    public function getActiveClients($filters = []) {
        return $this->clientModel->getActiveClients($filters);
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
     * Get paginated client data with metadata
     * @param array $filters Filter parameters
     * @return array
     */
    public function getPaginatedClients($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        // Get total count first
        $totalCount = $this->getTotalClientsCount($filters);
        
        // Get paginated data
        $clients = $this->getAllClients($filters);
        
        // Get pagination info
        $paginationInfo = FilterUtility::getPaginationInfo($filters, $totalCount);
        
        return [
            'data' => $clients,
            'pagination' => $paginationInfo,
            'filters' => $filters
        ];
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

        // 3. Create client
        $newId = $this->clientModel->create($clientData);

        if (!$newId) {
             $this->setErrorMessage($this->clientModel->getLastError() ?: 'Failed to create client due to unknown database error.');
             return false;
        }

        // 4. Log transaction for audit trail
        if (class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            $transactionService->logClientTransaction('created', $newId, $createdBy, [
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

        // 3. Update client
        return $this->clientModel->update($id, $clientData);
    }

    /**
     * Activates an inactive client account.
     * @param int $id
     * @return bool
     */
    public function activateClient($id) {
        return $this->clientModel->updateStatus($id, ClientModel::STATUS_ACTIVE);
    }

    /**
     * Deactivates a client account to preserve historical records (Section 4 Requirement).
     * @param int $id
     * @return bool
     */
    public function deactivateClient($id) {
        return $this->clientModel->updateStatus($id, ClientModel::STATUS_INACTIVE);
    }

    /**
     * Marks a client as blacklisted.
     * @param int $id
     * @return bool
     */
    public function blacklistClient($id) {
        return $this->clientModel->updateStatus($id, ClientModel::STATUS_BLACKLISTED);
    }

    /**
     * Deletes a client record. Only possible if no active loans exist (database FK enforces this).
     * Since we must preserve historical loan records, we prefer DEACTIVATE.
     * @param int $id
     * @return bool
     */
    public function deleteClient($id) {
         // Although Deactivate is preferred, we keep delete functionality for cleanup if no loans exist.
        $activeLoans = $this->clientModel->getClientCurrentLoans($id);
        if (!empty($activeLoans)) {
            $this->setErrorMessage('Cannot delete client with active loans. Please deactivate instead.');
            return false;
        }
        
        return $this->clientModel->delete($id);
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
