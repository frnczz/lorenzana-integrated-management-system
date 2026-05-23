-- ============================================================
-- Sales Fulfillment System - Database Migration
-- Adds missing delivery_person_id column for order fulfillment
-- ============================================================

-- Add delivery_person_id column to sales_orders
ALTER TABLE `sales_orders` 
ADD COLUMN `delivery_person_id` INT NULL AFTER `fulfillment_type`;

-- Add foreign key constraint for delivery_person_id
ALTER TABLE `sales_orders`
ADD CONSTRAINT `fk_sales_orders_delivery_person` 
FOREIGN KEY (`delivery_person_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Ensure status enum includes pickup states (required for "Picked Up" workflow)
SET @status_enum = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'status');

SET @needs_update = (NOT (LOCATE('Picked Up', @status_enum) > 0)) OR (NOT (LOCATE('Ready for Pickup', @status_enum) > 0));

SET @alter_sql = IF(
    @needs_update,
    "ALTER TABLE `sales_orders` MODIFY COLUMN `status` ENUM('Pending','Confirmed','Dispatched','Delivered','Cancelled','Ready for Pickup','Picked Up') DEFAULT 'Pending'",
    "SELECT 1"
);

PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create index for faster fulfillment type queries
CREATE INDEX `idx_fulfillment_type_status` ON `sales_orders` (`fulfillment_type`, `status`);
