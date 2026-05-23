-- Clear all data from all tables in lorinims_db except products, raw_materials, and users.
-- WARNING: This will DELETE ALL DATA in the affected tables and cannot be undone.

SET FOREIGN_KEY_CHECKS = 0;

SET @schema = 'lorinims_db';

DROP PROCEDURE IF EXISTS truncate_all_except;

DELIMITER $$
CREATE PROCEDURE truncate_all_except()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE tbl VARCHAR(255);
  DECLARE cur CURSOR FOR
    SELECT table_name
    FROM information_schema.tables
    WHERE table_schema = @schema
      AND table_type = 'BASE TABLE'
      AND table_name NOT IN ('products', 'raw_materials', 'users');
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO tbl;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET @s = CONCAT('TRUNCATE TABLE `', tbl, '`');
    PREPARE stmt FROM @s;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END LOOP;
  CLOSE cur;
END$$
DELIMITER ;

CALL truncate_all_except();
DROP PROCEDURE IF EXISTS truncate_all_except;

SET FOREIGN_KEY_CHECKS = 1;
