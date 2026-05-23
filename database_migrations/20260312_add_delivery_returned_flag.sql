-- Migration: add failure reason + returned-to-inventory flags to delivery_assignments
-- This migration is safe to run even if previous migrations were not applied.

ALTER TABLE delivery_assignments
    ADD COLUMN IF NOT EXISTS failure_reason VARCHAR(255) NULL;

ALTER TABLE delivery_assignments
    ADD COLUMN IF NOT EXISTS returned_to_inventory TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE delivery_assignments
    ADD COLUMN IF NOT EXISTS returned_at DATETIME NULL;
