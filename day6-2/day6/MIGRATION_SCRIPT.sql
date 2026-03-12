-- ================================================================
-- MULTI-TENANCY DATABASE MIGRATION SCRIPT
-- Use this to migrate your existing schema to multi-tenant
-- ================================================================

-- Step 1: Add user_id columns to existing tables (ONLY WHERE MISSING)
-- =======================================================

-- Add user_id to transactions (if it doesn't exist)
ALTER TABLE transactions ADD COLUMN user_id INT AFTER id;

-- Add user_id to transaction_details (if it doesn't exist)
ALTER TABLE transaction_details ADD COLUMN user_id INT AFTER id;

-- Note: tblproduct and tblexpense already have user_id column

-- Step 2: Migrate existing data to user_id = 1
-- =======================================================
-- This assumes all your existing data belongs to the first user (nasju)
-- Adjust the user_id value if needed

UPDATE tblproduct SET user_id = 1 WHERE user_id IS NULL;
UPDATE tblexpense SET user_id = 1 WHERE user_id IS NULL;
UPDATE transactions SET user_id = 1 WHERE user_id IS NULL;
UPDATE transaction_details SET user_id = 1 WHERE user_id IS NULL;

-- Step 3: Add foreign key constraints
-- =======================================================

-- Add constraint to tblproduct
ALTER TABLE tblproduct ADD CONSTRAINT fk_product_user
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add constraint to tblexpense
ALTER TABLE tblexpense ADD CONSTRAINT fk_expense_user
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add constraint to transactions
ALTER TABLE transactions ADD CONSTRAINT fk_transaction_user
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add constraint to transaction_details
ALTER TABLE transaction_details ADD CONSTRAINT fk_transaction_details_user
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Step 4: Add indexes for performance
-- =======================================================
-- These composite indexes help queries filter by user_id efficiently

-- Index for product lookup by user
ALTER TABLE tblproduct ADD INDEX idx_user_product (user_id, product_id);

-- Index for expense lookup by user
ALTER TABLE tblexpense ADD INDEX idx_user_expense (user_id, id);

-- Index for transaction lookup by user
ALTER TABLE transactions ADD INDEX idx_user_transaction (user_id, id);

-- Index for transaction_details lookup by user
ALTER TABLE transaction_details ADD INDEX idx_user_details (user_id, transaction_id);

-- Step 5: Add NOT NULL constraint (after data migration)
-- =======================================================
-- IMPORTANT: Only run AFTER all data has been migrated to a user_id

ALTER TABLE tblproduct MODIFY user_id INT NOT NULL;
ALTER TABLE tblexpense MODIFY user_id INT NOT NULL;
ALTER TABLE transactions MODIFY user_id INT NOT NULL;
ALTER TABLE transaction_details MODIFY user_id INT NOT NULL;

-- Step 6: Verify migration
-- =======================================================
-- Run these queries to verify the data is correctly migrated

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

-- Verify user relationships
SELECT u.id, u.username, 
  (SELECT COUNT(*) FROM tblproduct WHERE user_id = u.id) as products,
  (SELECT COUNT(*) FROM tblexpense WHERE user_id = u.id) as expenses,
  (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as transactions
FROM users u;

-- Step 7: Optional - Create audit log table for security tracking
-- =======================================================

CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,
  table_name VARCHAR(50) NOT NULL,
  record_id INT,
  old_value LONGTEXT,
  new_value LONGTEXT,
  ip_address VARCHAR(45),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_action (user_id, action),
  INDEX idx_timestamp (timestamp)
);

-- Step 8: Verify foreign keys were created correctly
-- =======================================================

SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'users' AND TABLE_SCHEMA = 'db_product';

-- ================================================================
-- ROLLBACK STEPS (if needed)
-- ================================================================
-- DO NOT run these unless you need to undo the migration

/*
-- Remove constraints
ALTER TABLE tblproduct DROP FOREIGN KEY fk_product_user;
ALTER TABLE tblexpense DROP FOREIGN KEY fk_expense_user;
ALTER TABLE transactions DROP FOREIGN KEY fk_transaction_user;
ALTER TABLE transaction_details DROP FOREIGN KEY fk_transaction_details_user;

-- Remove columns
ALTER TABLE tblproduct DROP COLUMN user_id;
ALTER TABLE tblexpense DROP COLUMN user_id;
ALTER TABLE transactions DROP COLUMN user_id;
ALTER TABLE transaction_details DROP COLUMN user_id;

-- Remove indexes
ALTER TABLE tblproduct DROP INDEX idx_user_product;
ALTER TABLE tblexpense DROP INDEX idx_user_expense;
ALTER TABLE transactions DROP INDEX idx_user_transaction;
ALTER TABLE transaction_details DROP INDEX idx_user_details;

-- Drop audit table
DROP TABLE IF EXISTS audit_log;
*/
