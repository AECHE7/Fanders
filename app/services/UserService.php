<?php
/**
 * UserService - Handles user-related operations
 */
class UserService extends BaseService {
    private $userModel;
    private $bookModel;
    private $transactionModel;
    private $passwordHash;
    private $validRoles;
    private $validStatuses;
    private $session;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->bookModel = new BookModel();
        $this->transactionModel = new TransactionModel();
        $this->passwordHash = new PasswordHash();
        $this->session = new Session();
        $this->setModel($this->userModel);
        
        // Define valid roles and statuses
        $this->validRoles = [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_STUDENT,
            UserModel::$ROLE_STAFF,
            UserModel::$ROLE_OTHER
        ];
        
        $this->validStatuses = [
            UserModel::$STATUS_ACTIVE,
            UserModel::$STATUS_INACTIVE,
            UserModel::$STATUS_SUSPENDED
        ];
    }

 
    public function getAllUsersWithRoleNames($roles = []) {
        return $this->userModel->getAllUsersWithRoleNames($roles);
    }

    public function getUserWithRoleName($id) {
        return $this->userModel->getUserWithRoleName($id);
    }


    public function addUser($userData) {
        try {
            // Ensure role is present and valid
            if (!isset($userData['role']) || !in_array($userData['role'], $this->validRoles)) {
                $this->setErrorMessage('Invalid or missing user role.');
                return false;
            }

            // Validate user data
            if (!$this->validateUserData($userData)) {
                return false;
            }

            // Hash password
            $userData['password'] = $this->passwordHash->hash($userData['password']);

            // Create user based on role
            switch ($userData['role']) {
                case UserModel::$ROLE_ADMIN:
                    return $this->userModel->createAdmin($userData);
                case UserModel::$ROLE_STUDENT:
                case UserModel::$ROLE_STAFF:
                case UserModel::$ROLE_OTHER:
                    return $this->userModel->createBorrower($userData);
                case UserModel::$ROLE_SUPER_ADMIN:
                    return $this->userModel->create($userData);
                default:
                    $this->setErrorMessage('Invalid user role.');
                    return false;
            }
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }


    public function updateUser($id, $userData) {
        try {
            // Get existing user
            $existingUser = $this->userModel->findById($id);

            if (!$existingUser) {
                $this->setErrorMessage('User not found.');
                return false;
            }

            // Validate user data for update
            if (!$this->validateUserDataForUpdate($userData, $id)) {
                return false;
            }

            // Hash password if provided
            if (isset($userData['password']) && !empty($userData['password'])) {
                $userData['password'] = $this->passwordHash->hash($userData['password']);
            } else {
                unset($userData['password']); // Remove password if not provided
            }

            // Update user
            return $this->userModel->update($id, $userData);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

   
    public function deleteUser($id) {
        try {
            return $this->userModel->delete($id);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }


    public function deactivateUser($id) {
        return $this->userModel->update($id, [
            'status' => UserModel::$STATUS_INACTIVE,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getUserStats() {
        return $this->userModel->getUserStats();
    }

    public function getSystemStats() {
        return $this->userModel->getSystemStats();
    }

    public function getAdminStats() {
        $stats = [];
        
        // Get book statistics
        $bookModel = new BookModel();
        $stats['total_books'] = $bookModel->getTotalBooks();
        $stats['borrowed_books'] = $bookModel->getBorrowedBooksCount();
        
        // Get transaction statistics
        $transactionModel = new TransactionModel();
        $stats['overdue_returns'] = $transactionModel->getOverdueLoansCount();
        $stats['total_penalties'] = $transactionModel->getTotalUnpaidPenalties();
        
        // Get user statistics based on role
        $userRole = $this->session->get('user_role');
        
        if ($userRole === UserModel::$ROLE_SUPER_ADMIN) {
            // Super Admin sees all user types
            $stats['total_students'] = $this->userModel->getUsersCountByRole(UserModel::$ROLE_STUDENT);
            $stats['total_staff'] = $this->userModel->getUsersCountByRole(UserModel::$ROLE_STAFF);
            $stats['total_admins'] = $this->userModel->getUsersCountByRole(UserModel::$ROLE_ADMIN);
            $stats['total_others'] = $this->userModel->getUsersCountByRole(UserModel::$ROLE_OTHER);
        } else {
            // Admin sees only total borrowers
            $stats['total_borrowers'] = $this->userModel->getTotalBorrowersCount();
        }
        
        // Get recent activity
        $stats['recent_transactions'] = $transactionModel->getRecentTransactions(5);
        $stats['recently_added_books'] = $bookModel->getRecentlyAddedBooks(5);
        
        return $stats;
    }

  
    public function getAllBorrowers() {
        return $this->userModel->getAllUsersWithRoleNames([
            UserModel::$ROLE_STUDENT,
            UserModel::$ROLE_STAFF,
            UserModel::$ROLE_OTHER
        ]);
    }

    private function validateUserData($userData) {
        // Check required fields
        $requiredFields = ['name', 'email', 'password', 'role', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || trim($userData[$field]) === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        
        // Check if email exists
        if ($this->userModel->emailExists($userData['email'])) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Validate phone number (basic: digits and length)
        if (!preg_match('/^[0-9]{8,15}$/', $userData['phone_number'])) {
            $this->setErrorMessage('Phone Number must be numeric and 8-15 digits.');
            return false;
        }
        
        // Check if phone number exists
        if ($this->userModel->phoneNumberExists($userData['phone_number'])) {
            $this->setErrorMessage('Phone Number already exists.');
            return false;
        }
        
        // Validate password (at least 8 characters)
        if (strlen($userData['password']) < 8) {
            $this->setErrorMessage('Password must be at least 8 characters long.');
            return false;
        }
        
        // Password Confirmation: required and must match
        if (!isset($userData['password_confirmation']) || trim($userData['password_confirmation']) === '') {
            $this->setErrorMessage('Password confirmation is required.');
            return false;
        }
        
        if ($userData['password'] !== $userData['password_confirmation']) {
            $this->setErrorMessage('Password and Password Confirmation do not match.');
            return false;
        }
        
        // Validate role
        if (!in_array($userData['role'], $this->validRoles)) {
            $this->setErrorMessage('Invalid role.');
            return false;
        }
        
        return true;
    }


    private function validateUserDataForUpdate($userData, $userId) {
        // Check required fields
        $requiredFields = ['name', 'email', 'role', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || trim($userData[$field]) === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        
        // Check if email exists (excluding current user)
        if ($this->userModel->emailExists($userData['email'], $userId)) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Validate phone number (basic: digits and length)
        if (!preg_match('/^[0-9]{8,15}$/', $userData['phone_number'])) {
            $this->setErrorMessage('Phone Number must be numeric and 8-15 digits.');
            return false;
        }
        
        // Check if phone number exists (excluding current user)
        if ($this->userModel->phoneNumberExists($userData['phone_number'], $userId)) {
            $this->setErrorMessage('Phone Number already exists.');
            return false;
        }
        
        // Validate password if provided
        if (isset($userData['password']) && !empty($userData['password'])) {
            if (strlen($userData['password']) < 8) {
                $this->setErrorMessage('Password must be at least 8 characters long.');
                return false;
            }
            
            // Password Confirmation: required and must match
            if (!isset($userData['password_confirmation']) || trim($userData['password_confirmation']) === '') {
                $this->setErrorMessage('Password confirmation is required.');
                return false;
            }
            
            if ($userData['password'] !== $userData['password_confirmation']) {
                $this->setErrorMessage('Password and Password Confirmation do not match.');
                return false;
            }
        }
        
        // Validate role
        if (!in_array($userData['role'], $this->validRoles)) {
            $this->setErrorMessage('Invalid role.');
            return false;
        }
        
        return true;
    }


    public function getBorrowerStats($userId) {
        $stats = [];
        
        // Get total borrowed books
        $stats['total_borrowed'] = $this->transactionModel->getTotalBorrowedBooks($userId);
        
        // Get currently borrowed books
        $stats['current_borrowed'] = $this->transactionModel->getCurrentBorrowedBooks($userId);
        
        // Get overdue books count
        $stats['overdue_count'] = $this->transactionModel->getOverdueBooksCount($userId);
        
        // Get total penalties
        $stats['total_penalties'] = $this->transactionModel->getTotalUnpaidPenalties($userId);
        
        // Get loan history
        $loanHistory = $this->transactionModel->getUserTransactionHistory($userId);
        $stats['loan_history'] = is_array($loanHistory) ? $loanHistory : [];
        
        // Filter active loans from loan history where return_date is null
        $activeLoans = array_filter($stats['loan_history'], function($loan) {
            return empty($loan['return_date']);
        });
        $stats['active_loans'] = $activeLoans;
        
        // Get available books using BookService to match books/index.php logic
        $bookService = new BookService();
        $allBooks = $bookService->getAllBooksWithCategories();
        // Filter out books with no available copies
        $availableBooks = array_filter($allBooks, function($book) {
            return isset($book['available_copies']) && $book['available_copies'] > 0;
        });
        error_log("Available books fetched after filtering: " . print_r($availableBooks, true));
        $stats['available_books'] = is_array($availableBooks) ? $availableBooks : [];
        
        return $stats;
    }

    public function activateUser($userId) {
        try {
            return $this->userModel->update($userId, [
                'status' => UserModel::$STATUS_ACTIVE,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }


    public function getUsersByRole($role) {
        return $this->userModel->getUsersByRole($role);
    }

    public function getUsersByStatus($status) {
        return $this->userModel->getUsersByStatus($status);
    }

 
    public function getActiveBorrowers() {
        return $this->userModel->getUsersByStatus('active');
    }

    // Reset password for a user
    public function resetPassword($id) {
        try {
            $user = $this->model->findById($id);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Generate random password
            $newPassword = $this->generateRandomPassword();
            
            // Hash and update
            $hashedPassword = $this->passwordHash->hash($newPassword);
            $result = $this->model->update($id, ['password' => $hashedPassword]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'password' => $newPassword
                ];
            }
                
            return ['success' => false, 'message' => 'Failed to reset password'];
    
            } catch (PDOException $e) {
                die('Query failed: ' . $e->getMessage());
            }
        }
        
        // Generate random password
        private function generateRandomPassword($length = 12) {
            try {
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
                $password = '';
                
                for ($i = 0; $i < $length; $i++) {
                    $password .= $chars[rand(0, strlen($chars) - 1)];
                }
                
                return $password;
            } catch (Exception $e) {
                die('Error generating password: ' . $e->getMessage());
            }
        }
}
