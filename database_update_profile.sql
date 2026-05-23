-- Database Update for Profile Enhancement
-- Run this SQL to add additional profile fields to the users table
-- This will NOT delete existing data, only add new columns

USE lorinims_db;

-- Add additional profile fields if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) AFTER email,
ADD COLUMN IF NOT EXISTS address TEXT AFTER phone_number,
ADD COLUMN IF NOT EXISTS birth_date DATE AFTER address,
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) AFTER birth_date,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL AFTER updated_at;

-- Note: The IF NOT EXISTS syntax might not work in all MySQL versions
-- If you get an error, run these one by one and ignore errors for columns that already exist

-- Alternative approach (run these individually if the above doesn't work):
-- ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) AFTER email;
-- ALTER TABLE users ADD COLUMN address TEXT AFTER phone_number;
-- ALTER TABLE users ADD COLUMN birth_date DATE AFTER address;
-- ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) AFTER birth_date;
-- ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at;
