-- ============================================================
-- LORINIMS Event-Driven Architecture - Database Migration
-- Run after database_schema.sql and database_id_sequences.sql
-- ============================================================

-- 1. System Events Table (for trigger-based auto-generation)
CREATE TABLE IF NOT EXISTS system_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payload JSON NULL,
    processed TINYINT(1) NOT NULL DEFAULT 0,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entity_event (entity_type, entity_id, event_type),
    INDEX idx_processed (processed),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Sales: Add time-bound reservation (default 48 hours)
-- If column exists, comment out the next line:
ALTER TABLE sales_orders ADD COLUMN reservation_expires_at DATETIME NULL;

-- 3. Production: Add phase column for stage-based workflow
-- Valid: Planned, In Progress, Output Pending QC, Completed, Rejected
ALTER TABLE production_batches ADD COLUMN phase VARCHAR(50) DEFAULT 'Planned';

-- Migrate existing status to phase where applicable
UPDATE production_batches SET phase = 
    CASE 
        WHEN status IN ('Processing', 'In Progress') THEN 'In Progress'
        WHEN status = 'Ready' THEN 'Output Pending QC'
        WHEN status IN ('Completed', 'completed') THEN 'Completed'
        WHEN status = 'Rejected' THEN 'Rejected'
        ELSE 'Planned'
    END
WHERE phase IS NULL OR phase = '';
