SET FOREIGN_KEY_CHECKS=0;
USE lorinims_db;

-- Drop all tables except products and raw_materials
SOURCE C:/xampp/htdocs/lorinims/drop_non_inventory_tables.sql;

-- Re-import the core database schema/data from the dump
SOURCE C:/xampp/htdocs/lorinims/lorinims_db.sql;

-- Restore products/raw_materials from the backup (overwrites if needed)
SOURCE C:/xampp/htdocs/lorinims/backup_products_raw_materials.sql;

SET FOREIGN_KEY_CHECKS=1;
