-- Production flow: For Inspection status, sales order from production
-- Run once on lorinims_db. If a statement fails (e.g. column already exists), skip it and continue.

-- 1. Add 'For Inspection' to production_requests.status
ALTER TABLE production_requests 
MODIFY COLUMN status ENUM('Pending','In Progress','For Inspection','Completed','Cancelled') DEFAULT 'Pending';

-- 2. Link sales_orders created from production (finished customer orders). Skip if already present.
ALTER TABLE sales_orders ADD COLUMN from_production_request TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE sales_orders ADD COLUMN request_group_id VARCHAR(50) NULL;

-- 3. production_batches.request_id and phase (run if your schema doesn't have them yet)
-- ALTER TABLE production_batches ADD COLUMN request_id INT NULL, ADD KEY (request_id);
-- ALTER TABLE production_batches ADD COLUMN phase VARCHAR(50) DEFAULT 'Planned';
