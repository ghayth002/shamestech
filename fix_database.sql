-- SQL script to fix the "Unknown column 't0.name' in 'field list'" error

-- First check if the 'name' column exists in the 'question' table and add it if it doesn't
SET @column_exists = 0;
SELECT 1 INTO @column_exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'question' AND column_name = 'name';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE question ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT "Default Name"', 
    'SELECT "Column name already exists in question table" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Next check if the 'email' column exists in the 'question' table and add it if it doesn't
SET @column_exists = 0;
SELECT 1 INTO @column_exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'question' AND column_name = 'email';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE question ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT "default@example.com", ADD UNIQUE INDEX UNIQ_B6F7494EE7927C74 (email)', 
    'SELECT "Column email already exists in question table" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if the 'username' column exists in the 'reponse' table and add it if it doesn't
SET @column_exists = 0;
SELECT 1 INTO @column_exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'reponse' AND column_name = 'username';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE reponse ADD COLUMN username VARCHAR(100) NOT NULL DEFAULT "Default User"', 
    'SELECT "Column username already exists in reponse table" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if the 'role' column exists in the 'reponse' table and add it if it doesn't
SET @column_exists = 0;
SELECT 1 INTO @column_exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'reponse' AND column_name = 'role';

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE reponse ADD COLUMN role VARCHAR(100) NOT NULL DEFAULT "user"', 
    'SELECT "Column role already exists in reponse table" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop all foreign keys on message table to avoid conflicts
SELECT CONCAT('ALTER TABLE message DROP FOREIGN KEY ', CONSTRAINT_NAME, ';')
FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
AND TABLE_NAME = 'message' 
AND TABLE_SCHEMA = DATABASE();

-- Re-add the correct foreign keys that reference the proper column names
-- You may need to execute these separately if the previous ones fail
ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id);
ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCF18BB82 FOREIGN KEY (reponse_id) REFERENCES reponse (id);
