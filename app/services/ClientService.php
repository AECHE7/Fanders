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
     * Retrieves all client records.
     * @return array
     */
    public function getAllClients() {
        return $this->clientModel->getAll('name', 'ASC');
    }

    /**
     * Searches clients by name, email, or phone number.
     * @param string $term
     * @return array
     */
    public function searchClients($term) {
        return $this->clientModel->searchClients($term);
    }

    /**
     * Retrieves clients filtered by status.
     * @param string $status
     * @return array
     */
    public function getClientsByStatus($status) {
        return $this->clientModel->getClientsByStatus($status);
    }

    /**
     * Retrieves only active clients.
     * @return array
     */
    public function getActiveClients() {
        return $this->clientModel->getActiveClients();
    }

    /**
     * Retrieves general statistics about the client base (counts by status, recent clients).
     * @return array
     */
    public function getClientStats() {
        return $this->clientModel->getClientStats();
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
     * @return int|false New client ID on success.
     */
    public function createClient($clientData) {
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
            // Basic date format validation and age check (must be at least 18)
            if (!$dob || $dob->format('Y-m-d') !== $clientData['date_of_birth'] || $today->diff($dob)->y < 18) {
                $this->setErrorMessage('Client must be at least 18 years old and Date of Birth must be valid (Y-m-d).');
                return false;
            }
        }

        return true;
    }
}
