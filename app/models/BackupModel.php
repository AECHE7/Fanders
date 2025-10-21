<?php
/**
 * BackupModel - Handles backup metadata operations
 */

require_once __DIR__ . '/../core/BaseModel.php';

class BackupModel extends BaseModel {
    protected $table = 'system_backups';

    /**
     * Get backups with pagination
     */
    public function getBackups($filters = [], $page = 1, $limit = 20) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        // Pagination
        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total backup count
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Create backup record
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table}
                (filename, filepath, type, size, status, created_by, cloud_url, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['filename'],
            $data['filepath'],
            $data['type'],
            $data['size'],
            $data['status'],
            $data['created_by'] ?? 'system',
            $data['cloud_url'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update backup record
     */
    public function update($id, $data) {
        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete backup record
     */
    public function delete($id) {
        // Also delete the physical file if it exists
        $backup = $this->findById($id);
        if ($backup && file_exists($backup['filepath'])) {
            unlink($backup['filepath']);
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Get backup statistics
     */
    public function getStats() {
        $sql = "
            SELECT
                COUNT(*) as total_backups,
                SUM(CASE WHEN type = 'scheduled' THEN 1 ELSE 0 END) as scheduled_backups,
                SUM(CASE WHEN type = 'manual' THEN 1 ELSE 0 END) as manual_backups,
                SUM(CASE WHEN type = 'full' THEN 1 ELSE 0 END) as full_backups,
                SUM(size) as total_size_bytes,
                AVG(size) as avg_size_bytes,
                MAX(created_at) as last_backup_date,
                MIN(created_at) as first_backup_date
            FROM {$this->table}
            WHERE status = 'completed'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if backup exists today
     */
    public function hasBackupToday($type = 'scheduled') {
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE DATE(created_at) = ? AND type = ? AND status = 'completed'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$today, $type]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Clean up old backups
     */
    public function cleanupOldBackups($retentionDays = 30, $keepMonthly = 10) {
        // Delete backups older than retention period
        $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));

        // But keep the last N backups of each month
        $sql = "
            DELETE FROM {$this->table}
            WHERE created_at < ?
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY YEAR(created_at), MONTH(created_at)
                                             ORDER BY created_at DESC) as rn
                    FROM {$this->table}
                    WHERE created_at < ?
                ) ranked WHERE rn <= ?
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cutoffDate, $cutoffDate, $keepMonthly]);

        return $stmt->rowCount();
    }
}
