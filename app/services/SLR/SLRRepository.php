<?php
/**
 * SLRRepository - Data access layer for SLR documents
 */

namespace App\Services\SLR;

require_once __DIR__ . '/../../constants/SLRConstants.php';

use App\Constants\SLRConstants;

class SLRRepository {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create SLR database record
     * 
     * @param array $data
     * @return int|false SLR ID or false on failure
     */
    public function createSLR(array $data) {
        $sql = "INSERT INTO slr_documents (
                    loan_id, document_number, generated_by, generation_trigger,
                    file_path, file_name, file_size, content_hash,
                    client_signature_required, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['loan_id'],
            $data['document_number'],
            $data['generated_by'],
            $data['generation_trigger'],
            $data['file_path'],
            $data['file_name'],
            $data['file_size'],
            $data['content_hash'],
            $data['client_signature_required'] ? 1 : 0,
            SLRConstants::STATUS_ACTIVE
        ]);
        
        return $success ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Get SLR by ID with related data
     * 
     * @param int $slrId
     * @return array|null
     */
    public function getSLRById(int $slrId): ?array {
        $sql = "SELECT s.*, l.client_id, l.principal, l.total_loan_amount,
                       c.name as client_name, u.name as generated_by_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON s.generated_by = u.id
                WHERE s.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slrId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get SLR by loan ID (returns most recent active)
     * 
     * @param int $loanId
     * @return array|null
     */
    public function getSLRByLoanId(int $loanId): ?array {
        $sql = "SELECT s.*, l.client_id, l.principal, l.total_loan_amount,
                       c.name as client_name, u.name as generated_by_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON s.generated_by = u.id
                WHERE s.loan_id = ? AND s.status = ?
                ORDER BY s.generated_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loanId, SLRConstants::STATUS_ACTIVE]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * List all SLR documents with filters
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function listSLRDocuments(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "SELECT s.*, l.client_id, l.principal, l.total_loan_amount,
                       c.name as client_name, u.name as generated_by_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON s.generated_by = u.id";
        
        $conditions = [];
        $params = [];
        
        if (!empty($filters['loan_id'])) {
            $conditions[] = 's.loan_id = ?';
            $params[] = $filters['loan_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['client_id'])) {
            $conditions[] = 'l.client_id = ?';
            $params[] = $filters['client_id'];
        }
        
        if (!empty($filters['generated_by'])) {
            $conditions[] = 's.generated_by = ?';
            $params[] = $filters['generated_by'];
        }
        
        if (!empty($filters['trigger'])) {
            $conditions[] = 's.generation_trigger = ?';
            $params[] = $filters['trigger'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = 's.generated_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = 's.generated_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        $sql .= ' ORDER BY s.generated_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Update SLR status
     * 
     * @param int $slrId
     * @param string $status
     * @param string|null $filePath
     * @param string|null $reason
     * @return bool
     */
    public function updateSLRStatus(int $slrId, string $status, ?string $filePath = null, ?string $reason = null): bool {
        $sql = "UPDATE slr_documents 
                SET status = ?, 
                    file_path = COALESCE(?, file_path),
                    replacement_reason = COALESCE(?, replacement_reason),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $filePath, $reason, $slrId]);
    }
    
    /**
     * Update download statistics
     * 
     * @param int $slrId
     * @param int $userId
     * @return bool
     */
    public function updateDownloadStats(int $slrId, int $userId): bool {
        $sql = "UPDATE slr_documents 
                SET download_count = download_count + 1,
                    last_downloaded_at = CURRENT_TIMESTAMP,
                    last_downloaded_by = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $slrId]);
    }
    
    /**
     * Log SLR access to slr_access_log table
     * 
     * @param int $slrId
     * @param string $accessType
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function logAccess(int $slrId, string $accessType, int $userId, string $reason = ''): bool {
        $sql = "INSERT INTO slr_access_log (
                    slr_document_id, access_type, accessed_by, 
                    access_reason, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $slrId, $accessType, $userId, $reason, $ipAddress, $userAgent
        ]);
    }
    
    /**
     * Get SLR access history
     * 
     * @param int $slrId
     * @param int $limit
     * @return array
     */
    public function getAccessHistory(int $slrId, int $limit = 50): array {
        $sql = "SELECT sal.*, u.name as user_name
                FROM slr_access_log sal
                LEFT JOIN users u ON sal.accessed_by = u.id
                WHERE sal.slr_document_id = ?
                ORDER BY sal.accessed_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slrId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Count SLR documents by filters
     * 
     * @param array $filters
     * @return int
     */
    public function countSLRDocuments(array $filters = []): int {
        $sql = "SELECT COUNT(*) as total
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id";
        
        $conditions = [];
        $params = [];
        
        if (!empty($filters['loan_id'])) {
            $conditions[] = 's.loan_id = ?';
            $params[] = $filters['loan_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['client_id'])) {
            $conditions[] = 'l.client_id = ?';
            $params[] = $filters['client_id'];
        }
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
}
