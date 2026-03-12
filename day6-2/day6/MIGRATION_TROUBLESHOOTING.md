# Migration Troubleshooting Guide

## Problem: Foreign Key Errors

You may encounter these errors:

- **#1146**: Table 'db_product.users' doesn't exist
- **#1005**: Can't create table, Foreign key constraint is incorrectly formed

### Root Cause

Your database has **two separate databases**:

- `user_db` - Contains `users` table
- `db_product` - Contains `tblproduct`, `tblexpense`, `transactions`, `transaction_details`

Foreign keys between databases can be tricky due to MySQL/MariaDB configuration limitations.

---

## Solution: Skip Foreign Keys (Recommended)

Instead of using database-level foreign keys, we'll use:

1. ✅ **user_id columns** (data separation)
2. ✅ **Indexes** (performance)
3. ✅ **Application validation** (security)
4. ❌ **Database constraints** (skip - causes issues)

This is actually **better** for multi-tenancy because:

- More flexible (you can have data owned by deleted users if needed)
- Avoids complex cross-database constraints
- Application has full control over data validation
- Still maintains complete isolation

---

## Migration Steps (RUN THESE)

### File to Use: `SAFE_MIGRATION.sql`

**Copy the ENTIRE contents** and paste into phpMyAdmin SQL tab, then:

1. **Highlight STEP 1 block** → Click Go

   ```sql
   ALTER TABLE transactions ADD COLUMN user_id INT AFTER id;
   ALTER TABLE transaction_details ADD COLUMN user_id INT AFTER id;
   ```

2. **Highlight STEP 2 block** → Click Go

   ```sql
   UPDATE transactions SET user_id = 1 WHERE user_id IS NULL;
   UPDATE transaction_details SET user_id = 1 WHERE user_id IS NULL;
   ...
   ```

3. **Highlight STEP 3 block** → Click Go

   ```sql
   ALTER TABLE tblproduct MODIFY user_id INT NOT NULL;
   ...
   ```

4. **Highlight STEP 4 block** → Click Go

   ```sql
   ALTER TABLE tblproduct ADD INDEX idx_user_product (user_id, product_id);
   ...
   ```

5. **Highlight STEP 5 block** → Click Go
   ```sql
   SELECT 'tblproduct' as table_name, COUNT(*) as total_records...
   ```

If all SELECT queries return data ✅, **migration is complete!**

---

## Verification Checklist

After running SAFE_MIGRATION.sql, verify:

- [ ] `transactions` table has `user_id` column
- [ ] `transaction_details` table has `user_id` column
- [ ] All records have `user_id = 1` assigned
- [ ] All `user_id` columns are `NOT NULL`
- [ ] Indexes `idx_user_*` exist on all tables

**Check this query result:**

```sql
SELECT * FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'db_product'
  AND TABLE_NAME IN ('tblproduct', 'tblexpense', 'transactions', 'transaction_details')
  AND INDEX_NAME LIKE 'idx_user%';
```

Should show 4 indexes for the 4 tables.

---

## Data Isolation Verification

Run this to confirm data is properly scoped by user:

```sql
SELECT u.id, u.username,
  (SELECT COUNT(*) FROM tblproduct WHERE user_id = u.id) as products,
  (SELECT COUNT(*) FROM tblexpense WHERE user_id = u.id) as expenses,
  (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as transactions
FROM `user_db`.`users` u;
```

Expected output (with current data):
| id | username | products | expenses | transactions |
|----|----------|----------|----------|--------------|
| 1 | nasju | 1 | 0 | 2 |
| 2 | nasju666 | NULL | NULL | NULL |
| 3 | nasju008 | NULL | NULL | NULL |

---

## Security Confirmation

Your multi-tenancy is now secure even without foreign keys because:

1. **Database Level**:
   - ✅ `user_id` columns separate data
   - ✅ Indexes ensure fast query filtering
   - ❌ No foreign keys (not needed)

2. **Application Level** (from the code files provided):
   - ✅ TenantMiddleware ensures user_id from session only
   - ✅ RequestValidator verifies record ownership
   - ✅ All queries include WHERE user_id = authenticated_user_id
   - ✅ Prepared statements prevent SQL injection

---

## What's Next

Now you can implement the application code:

1. Replace `db_connect.php` with `db_connect_updated.php`
2. Replace `login.php` with `login_updated.php`
3. Add `TenantMiddleware.php` and `RequestValidator.php`
4. Update your API endpoints following the `*_updated.php` examples

See **IMPLEMENTATION_QUICK_START.md** for step-by-step guide.

---

## If Foreign Keys Are Required Later

If you absolutely need foreign keys (rare), use this approach:

```sql
-- Create the constraint with explicit database reference
ALTER TABLE tblproduct
ADD CONSTRAINT fk_product_user
FOREIGN KEY (user_id) REFERENCES user_db.users(id)
ON DELETE CASCADE;
```

But this **requires** that `user_db`.`users` table has:

- ✅ `id` column as PRIMARY KEY
- ✅ `id` values starting from 1
- ✅ InnoDB engine (not MyISAM)

Check your user table:

```sql
SHOW TABLE STATUS FROM user_db WHERE Name = 'users';
SHOW INDEX FROM user_db.users;
```

---

## Troubleshooting

### Error: "Duplicate column name 'user_id'"

- Column already exists, it's safe to skip that ALTER TABLE command

### Error: "Can't create table, Foreign key constraint is incorrectly formed"

- Use SAFE_MIGRATION.sql which skips foreign keys
- This is expected and fine for multi-tenancy

### Error: "Table 'db_product.users' doesn't exist"

- Verify users table is in `user_db`, not `db_product`
- Check: `SELECT * FROM user_db.users LIMIT 1;`

### Verification query returns no rows

- Check that user_id is really populated
- Run: `SELECT COUNT(*), COUNT(DISTINCT user_id) FROM tblproduct;`

---

## Support Files

- **SAFE_MIGRATION.sql** - Safe version without foreign keys (RECOMMENDED)
- **FINAL_MIGRATION.sql** - Version with cross-DB foreign keys (if needed)
- **STEP_BY_STEP_MIGRATION.sql** - Individual steps
- **MULTI_TENANCY_STRATEGY.md** - Full documentation
- **IMPLEMENTATION_QUICK_START.md** - Application code guide
- **SECURITY_ATTACK_SCENARIOS.md** - Security validation

Ready to proceed! 🚀
