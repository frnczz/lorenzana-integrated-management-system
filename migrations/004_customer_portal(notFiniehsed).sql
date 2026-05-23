-- Customer self-service portal login (optional columns; safe to run once)
ALTER TABLE `customers`
  ADD COLUMN `portal_username` VARCHAR(100) NULL DEFAULT NULL AFTER `customer_code`,
  ADD COLUMN `portal_password_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `portal_username`;

ALTER TABLE `customers`
  ADD UNIQUE KEY `uk_portal_username` (`portal_username`);
