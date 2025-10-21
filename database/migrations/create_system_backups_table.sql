-- Create system_backups table for automated backup system
-- Part of Phase 3: Reporting, Administration & Final Polish

CREATE TABLE IF NOT EXISTS system_backups (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'manual' CHECK (type IN ('full', 'incremental', 'scheduled', 'manual')),
    size BIGINT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed')),
    cloud_url VARCHAR(500),
    created_by VARCHAR(100) NOT NULL DEFAULT 'system',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_restored_at TIMESTAMP WITH TIME ZONE,
    restore_count INTEGER NOT NULL DEFAULT 0,
    notes TEXT
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_system_backups_type ON system_backups(type);
CREATE INDEX IF NOT EXISTS idx_system_backups_status ON system_backups(status);
CREATE INDEX IF NOT EXISTS idx_system_backups_created_at ON system_backups(created_at);
CREATE INDEX IF NOT EXISTS idx_system_backups_created_by ON system_backups(created_by);

-- Add initial data
INSERT INTO system_backups (filename, filepath, type, size, status, created_by, notes) VALUES
('initial_setup_backup.dump', '/var/backups/fanders/initial.dump', 'manual', 1024000, 'completed', 'system', 'Initial system setup backup');
