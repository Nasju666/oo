-- ================================================================
-- COMPLETE MIGRATION SCRIPT FOR MISSING COLUMNS
-- Run this in phpMyAdmin to finish multi-tenancy setup
-- ================================================================

-- Step 1: Add user_id to transactions (if not already added)
-- =======================================================
ALTER TABLE transactions ADD COLUMN user_id INT AFTER id;

-- Step 2: Add user_id to transaction_details (if not already added)
-- =======================================================
ALTER TABLE transaction_details ADD COLUMN user_id INT AFTER id;

-- Step 3: Populate existing data with user_id = 1
-- =======================================================
UPDATE transactions SET user_id = 1 WHERE user_id IS NULL;
UPDATE transaction_details SET user_id = 1 WHERE user_id IS NULL;

-- Also ensure tblproduct and tblexpense are populated
UPDATE tblproduct SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;
UPDATE tblexpense SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;

-- Step 4: Add NOT NULL constraint to all user_id columns
-- =======================================================
ALTER TABLE tblproduct MODIFY user_id INT NOT NULL;
ALTER TABLE tblexpense MODIFY user_id INT NOT NULL;
ALTER TABLE transactions MODIFY user_id INT NOT NULL;
ALTER TABLE transaction_details MODIFY user_id INT NOT NULL;

-- Step 5: Add Foreign Key Constraints (with CROSS-DATABASE references)
-- =======================================================
-- IMPORTANT: Users table is in user_db, but product tables are in db_product
-- We must specify the full database.table reference

-- Drop existing constraints if they exist (then recreate with correct syntax)
ALTER TABLE tblproduct DROP FOREIGN KEY IF EXISTS fk_product_user;
ALTER TABLE tblexpense DROP FOREIGN KEY IF EXISTS fk_expense_user;
ALTER TABLE transactions DROP FOREIGN KEY IF EXISTS fk_transaction_user;
ALTER TABLE transaction_details DROP FOREIGN KEY IF EXISTS fk_transaction_details_user;

-- Add constraint to tblproduct with correct cross-database reference
ALTER TABLE tblproduct ADD CONSTRAINT fk_product_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

-- Add constraint to tblexpense with correct cross-database reference
ALTER TABLE tblexpense ADD CONSTRAINT fk_expense_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

-- Add constraint to transactions with correct cross-database reference
ALTER TABLE transactions ADD CONSTRAINT fk_transaction_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

-- Add constraint to transaction_details with correct cross-database reference
ALTER TABLE transaction_details ADD CONSTRAINT fk_transaction_details_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

-- Step 6: Add Indexes for Performance
-- =======================================================

-- Index for product lookup by user (if not already exists)
ALTER TABLE tblproduct ADD INDEX idx_user_product (user_id, product_id);

-- Index for expense lookup by user (if not already exists)
ALTER TABLE tblexpense ADD INDEX idx_user_expense (user_id, id);

-- Index for transaction lookup by user
ALTER TABLE transactions ADD INDEX idx_user_transaction (user_id, id);

-- Index for transaction_details lookup by user
ALTER TABLE transaction_details ADD INDEX idx_user_details (user_id, transaction_id);

-- Step 7: Verify Migration Success
-- =======================================================
-- Run these SELECT statements to confirm everything is set up correctly:

SELECT 'tblproduct' as table_name, COUNT(*) as total_records, COUNT(DISTINCT user_id) as users
FROM tblproduct
UNION ALL
SELECT 'tblexpense', COUNT(*), COUNT(DISTINCT user_id)
FROM tblexpense
UNION ALL
SELECT 'transactions', COUNT(*), COUNT(DISTINCT user_id)
FROM transactions
UNION ALL
SELECT 'transaction_details', COUNT(*), COUNT(DISTINCT user_id)
FROM transaction_details;

-- Verify user relationships (from user_db database)
SELECT u.id, u.username, 
  COALESCE((SELECT COUNT(*) FROM db_product.tblproduct WHERE user_id = u.id), 0) as products,
  COALESCE((SELECT COUNT(*) FROM db_product.tblexpense WHERE user_id = u.id), 0) as expenses,
  COALESCE((SELECT COUNT(*) FROM db_product.transactions WHERE user_id = u.id), 0) as transactions
FROM `user_db`.`users` u;

-- Verify foreign keys were created
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'db_product' AND REFERENCED_TABLE_NAME = 'users'
ORDER BY TABLE_NAME;
