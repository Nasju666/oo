-- ================================================================
-- SAFEST MIGRATION: Add columns, populate data, add indexes
-- (Skip foreign keys if they cause issues)
-- ================================================================

-- Run these ONE AT A TIME in phpMyAdmin SQL tab

-- ===== STEP 1: Add missing user_id columns =====
ALTER TABLE transactions ADD COLUMN user_id INT AFTER id;
ALTER TABLE transaction_details ADD COLUMN user_id INT AFTER id;


-- ===== STEP 2: Populate with existing user data =====
UPDATE transactions SET user_id = 1 WHERE user_id IS NULL;
UPDATE transaction_details SET user_id = 1 WHERE user_id IS NULL;
UPDATE tblproduct SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;
UPDATE tblexpense SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;


-- ===== STEP 3: Make all user_id columns NOT NULL =====
ALTER TABLE tblproduct MODIFY user_id INT NOT NULL;
ALTER TABLE tblexpense MODIFY user_id INT NOT NULL;
ALTER TABLE transactions MODIFY user_id INT NOT NULL;
ALTER TABLE transaction_details MODIFY user_id INT NOT NULL;


-- ===== STEP 4: Add Indexes for Performance =====
-- These are more important than foreign keys for multi-tenancy
ALTER TABLE tblproduct ADD INDEX idx_user_product (user_id, product_id);
ALTER TABLE tblexpense ADD INDEX idx_user_expense (user_id, id);
ALTER TABLE transactions ADD INDEX idx_user_transaction (user_id, id);
ALTER TABLE transaction_details ADD INDEX idx_user_details (user_id, transaction_id);


-- ===== STEP 5: Verify Migration Success =====

-- Check how many records belong to each user
SELECT 'tblproduct' as table_name, COUNT(*) as total_records, COUNT(DISTINCT user_id) as num_users
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

-- Check data distribution by user
SELECT u.id, u.username, 
  (SELECT COUNT(*) FROM tblproduct WHERE user_id = u.id) as products,
  (SELECT COUNT(*) FROM tblexpense WHERE user_id = u.id) as expenses,
  (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as transactions,
  (SELECT COUNT(*) FROM transaction_details WHERE user_id = u.id) as transaction_items
FROM `user_db`.`users` u;

-- Check indexes were created
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'db_product' 
  AND TABLE_NAME IN ('tblproduct', 'tblexpense', 'transactions', 'transaction_details')
  AND INDEX_NAME LIKE 'idx_user%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- ===== OPTIONAL: Add Foreign Keys (if needed) =====
-- Only run these if you want to enforce referential integrity
-- If you get errors, you can skip this and rely on application-level validation

/*
ALTER TABLE tblproduct ADD CONSTRAINT fk_product_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

ALTER TABLE tblexpense ADD CONSTRAINT fk_expense_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

ALTER TABLE transactions ADD CONSTRAINT fk_transaction_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

ALTER TABLE transaction_details ADD CONSTRAINT fk_transaction_details_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;
*/
