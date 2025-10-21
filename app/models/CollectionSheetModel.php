<?php
/**
 * CollectionSheetModel - Data access for collection_sheets table
 */
require_once __DIR__ . '/../core/BaseModel.php';

class CollectionSheetModel extends BaseModel {
    protected $table = 'collection_sheets';
    protected $primaryKey = 'id';
    protected $fillable = [
        'officer_id', 'sheet_date', 'status', 'total_amount', 'created_at', 'updated_at'
    ];
    protected $hidden = [];

    public function getByOfficerAndDate($officerId, $date) {
        $sql = "SELECT * FROM {$this->table} WHERE officer_id = ? AND sheet_date = ? ORDER BY id DESC LIMIT 1";
        return $this->db->single($sql, [$officerId, $date]);
    }

    // Ensure signature matches BaseModel::updateStatus($id, $statusValue, $statusField = 'status')
    public function updateStatus($id, $statusValue, $statusField = 'status') {
        return parent::updateStatus($id, $statusValue, $statusField);
    }

    public function recalcTotal($sheetId) {
        $sql = "SELECT COALESCE(SUM(amount),0) AS total FROM collection_sheet_items WHERE sheet_id = ?";
        $row = $this->db->single($sql, [$sheetId]);
        $total = (float)($row ? $row['total'] : 0);
        $this->update($sheetId, [
            'total_amount' => $total,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        return $total;
    }

    public function listSheets($filters = [], $limit = 20) {
        $sql = "SELECT cs.*, u.name AS officer_name
                FROM {$this->table} cs
                JOIN users u ON cs.officer_id = u.id";
        $conditions = [];
        $params = [];
        if (!empty($filters['officer_id'])) { $conditions[] = 'cs.officer_id = ?'; $params[] = $filters['officer_id']; }
        if (!empty($filters['status'])) { $conditions[] = 'cs.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['date'])) { $conditions[] = 'cs.sheet_date = ?'; $params[] = $filters['date']; }
        if (!empty($conditions)) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
        $sql .= ' ORDER BY cs.sheet_date DESC, cs.id DESC LIMIT ?';
        $params[] = $limit;
        return $this->db->resultSet($sql, $params);
    }
}
