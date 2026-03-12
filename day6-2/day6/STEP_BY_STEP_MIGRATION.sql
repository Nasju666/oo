-- ================================================================
-- STEP-BY-STEP MIGRATION (Run these one at a time in phpMyAdmin)
-- ================================================================

-- ===== STEP 1: Add missing columns =====
ALTER TABLE transactions ADD COLUMN user_id INT AFTER id;
ALTER TABLE transaction_details ADD COLUMN user_id INT AFTER id;


-- ===== STEP 2: Populate existing data =====
UPDATE transactions SET user_id = 1 WHERE user_id IS NULL;
UPDATE transaction_details SET user_id = 1 WHERE user_id IS NULL;
UPDATE tblproduct SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;
UPDATE tblexpense SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;


-- ===== STEP 3: Make user_id NOT NULL =====
ALTER TABLE tblproduct MODIFY user_id INT NOT NULL;
ALTER TABLE tblexpense MODIFY user_id INT NOT NULL;
ALTER TABLE transactions MODIFY user_id INT NOT NULL;
ALTER TABLE transaction_details MODIFY user_id INT NOT NULL;


-- ===== STEP 4: Drop any existing constraints (if they exist) =====
ALTER TABLE tblproduct DROP FOREIGN KEY IF EXISTS fk_product_user;
ALTER TABLE tblexpense DROP FOREIGN KEY IF EXISTS fk_expense_user;
ALTER TABLE transactions DROP FOREIGN KEY IF EXISTS fk_transaction_user;
ALTER TABLE transaction_details DROP FOREIGN KEY IF EXISTS fk_transaction_details_user;


-- ===== STEP 5: Add Foreign Key Constraints (CROSS-DATABASE) =====
-- The users table is in user_db, so we reference it with backticks
ALTER TABLE tblproduct ADD CONSTRAINT fk_product_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

ALTER TABLE tblexpense ADD CONSTRAINT fk_expense_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

ALTER TABLE transactions ADD CONSTRAINT fk_transaction_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;

ALTER TABLE transaction_details ADD CONSTRAINT fk_transaction_details_user
  FOREIGN KEY (user_id) REFERENCES `user_db`.`users`(id) ON DELETE CASCADE;


-- ===== STEP 6: Add Indexes for Performance =====
ALTER TABLE tblproduct ADD INDEX idx_user_product (user_id, product_id);
ALTER TABLE tblexpense ADD INDEX idx_user_expense (user_id, id);
ALTER TABLE transactions ADD INDEX idx_user_transaction (user_id, id);
ALTER TABLE transaction_details ADD INDEX idx_user_details (user_id, transaction_id);


-- ===== STEP 7: Verify Everything =====
-- Run these to confirm setup is complete

-- Check record counts and user distribution
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

-- Check what data belongs to which user
SELECT u.id, u.username, 
  COALESCE((SELECT COUNT(*) FROM db_product.tblproduct WHERE user_id = u.id), 0) as products,
  COALESCE((SELECT COUNT(*) FROM db_product.tblexpense WHERE user_id = u.id), 0) as expenses,
  COALESCE((SELECT COUNT(*) FROM db_product.transactions WHERE user_id = u.id), 0) as transactions
FROM `user_db`.`users` u;

-- Check foreign keys were created correctly
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE (TABLE_NAME IN ('tblproduct', 'tblexpense', 'transactions', 'transaction_details'))
  AND TABLE_SCHEMA = 'db_product'
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
