<?php
/**
 * CollectionSheetItemModel - Data access for collection_sheet_items table
 */
require_once __DIR__ . '/../core/BaseModel.php';

class CollectionSheetItemModel extends BaseModel {
    protected $table = 'collection_sheet_items';
    protected $primaryKey = 'id';
    protected $fillable = [
        'sheet_id','client_id','loan_id','amount','notes','status','posted_at','posted_by','created_at','updated_at'
    ];
    protected $hidden = [];

    public function getItemsBySheet($sheetId) {
        $sql = "SELECT i.*, c.name AS client_name, l.status AS loan_status
                FROM {$this->table} i
                JOIN loans l ON i.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                WHERE i.sheet_id = ?
                ORDER BY i.id ASC";
        return $this->db->resultSet($sql, [$sheetId]);
    }

    public function updateStatusBySheet($sheetId, $status) {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = ? WHERE sheet_id = ?";
        return $this->db->query($sql, [$status, date('Y-m-d H:i:s'), $sheetId]) ? true : false;
    }
}
