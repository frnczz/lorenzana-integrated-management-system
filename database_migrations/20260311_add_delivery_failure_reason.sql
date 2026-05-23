-- Migration: add failure_reason field to delivery_assignments for tracking why a delivery failed

ALTER TABLE delivery_assignments
    ADD COLUMN IF NOT EXISTS failure_reason VARCHAR(255) NULL AFTER proof_of_delivery;
