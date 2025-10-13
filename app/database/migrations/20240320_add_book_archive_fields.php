<?php
/**
 * Migration to add archive-related fields to books table
 */

class Migration_20240320_add_book_archive_fields extends Migration {
    public function up() {
        $sql = "ALTER TABLE books
                ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0,
                ADD COLUMN archived_at DATETIME NULL DEFAULT NULL,
                ADD COLUMN archive_reason TEXT NULL DEFAULT NULL";
                
        return $this->db->query($sql);
    }
    
    public function down() {
        $sql = "ALTER TABLE books
                DROP COLUMN is_archived,
                DROP COLUMN archived_at,
                DROP COLUMN archive_reason";
                
        return $this->db->query($sql);
    }
} 