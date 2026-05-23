-- Add vehicle_type column to users table for delivery/driver role
-- Run this if the column is not auto-created (profile page will auto-add on first load)
ALTER TABLE users ADD COLUMN vehicle_type VARCHAR(50) DEFAULT NULL;