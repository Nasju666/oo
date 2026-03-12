# Multi-Tenancy Implementation Quick Start Guide

## 1. DATABASE SETUP (5 minutes)

### Step 1.1: Run Migration Script

Copy the contents of `MIGRATION_SCRIPT.sql` into phpMyAdmin and execute it:

- Adds `user_id` column to all tables
- Creates foreign key constraints
- Adds performance indexes
- Migrates existing data to user_id=1

### Step 1.2: Verify Migration

In phpMyAdmin, run this query to confirm:

```sql
DESCRIBE tblproduct;
```

You should see a `user_id` column as NOT NULL.

---

## 2. APPLICATION FILES SETUP (10 minutes)

### Step 2.1: Replace Core Files

Replace these files with the `_updated` versions:

| Old File         | New File                 | Purpose                               |
| ---------------- | ------------------------ | ------------------------------------- |
| `db_connect.php` | `db_connect_updated.php` | Database connection with user context |
| `login.php`      | `login_updated.php`      | Secure login with user_id storage     |

### Step 2.2: Add New Utility Files

Add these new files to your `php/` folder:

- `TenantMiddleware.php` - Validates user sessions and enforces isolation
- `RequestValidator.php` - Validates and sanitizes user input
- `MULTI_TENANCY_STRATEGY.md` - Full documentation

### Step 2.3: Update Other Endpoints

For all your other PHP files that interact with data, follow the pattern in:

- `fetch_products_updated.php` - Example SELECT
- `delete_product_updated.php` - Example DELETE with ownership verification
- `addproduct_updated.php` - Example INSERT with user_id

---

## 3. CODE PATTERN FOR EACH ENDPOINT

### SELECT Query Pattern

```php
session_start();
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

// Initialize middleware - validates user and gets user_id
$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

// Query with automatic user_id filtering
$stmt = $conn->prepare("SELECT * FROM tblproduct WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
```

### INSERT Query Pattern

```php
$user_id = $middleware->getUserId();

// Insert with user_id
$stmt = $conn->prepare(
    "INSERT INTO tblproduct (user_id, product_name, ...)
     VALUES (?, ?, ...)"
);
$stmt->bind_param("is...", $user_id, $product_name, ...);
$stmt->execute();
```

### UPDATE Query Pattern

```php
$user_id = $middleware->getUserId();

// Update with ownership check
$stmt = $conn->prepare(
    "UPDATE tblproduct SET product_name = ?
     WHERE product_id = ? AND user_id = ?"
);
$stmt->bind_param("sii", $product_name, $product_id, $user_id);
$stmt->execute();
```

### DELETE Query Pattern

```php
$user_id = $middleware->getUserId();

// Delete with ownership check
$stmt = $conn->prepare(
    "DELETE FROM tblproduct
     WHERE product_id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
```

---

## 4. SECURITY CHECKLIST

- [ ] **Never use `$_GET['user_id']` or `$_POST['user_id']`**
  - Only get user_id from TenantMiddleware
  - Get it from verified session, not user input

- [ ] **Always include `WHERE user_id = ?` in queries**
  - Even for single-record operations
  - Even for deletes

- [ ] **Use prepared statements with bind_param()**
  - Never concatenate user input into queries
  - Protects against SQL injection

- [ ] **Validate record ownership before modification**
  - Use `$validator->validateProductAccess()` or similar
  - Returns 404 if record doesn't belong to user

- [ ] **Test ID Swapping**
  - Try changing product_id in URL to another user's product
  - Should return 404 or "Access Denied"

---

## 5. TESTING YOUR IMPLEMENTATION

### 5.1: Test with Two Users

Create a second test user:

```sql
INSERT INTO users (username, email, password)
VALUES ('testuser', 'test@example.com', PASSWORD_HASH_HERE);
```

Use phpMyAdmin to find the user_id of this new user (likely 4).

### 5.2: Create Test Data

Login as user 1 (nasju), add a product.
Then manually insert a product for user 4:

```sql
INSERT INTO tblproduct (user_id, product_name, category, cost_price, selling_price, stock)
VALUES (4, 'Test Product', 'Test Category', 10.00, 15.00, 5);
```

### 5.3: Test Isolation

- Login as `nasju` - should see only their products
- Login as new user - should NOT see nasju's products
- Try changing product_id in URL - should get 404

### 5.4: Test Error Cases

```
GET /fetch_products.php                     # Should work
GET /fetch_products.php?user_id=2           # Should be ignored, user_id comes from session only
GET /delete_product.php?product_id=999      # Should return 404 (doesn't exist)
GET /delete_product.php?product_id=1        # If product 1 belongs to another user, should return 404
```

---

## 6. FILES TO UPDATE BY CATEGORY

### View/Dashboard Files

- [ ] dashboard.php
- [ ] viewinventory.php
- [ ] viewproduct.php

### Add/Edit Files

- [ ] addproduct.php (use `addproduct_updated.php` as template)
- [ ] addexpense.php
- [ ] edit_product.php

### Delete Files

- [ ] delete_product.php (already provided)

### Fetch/API Files

- [ ] fetch_products.php (use `fetch_products_updated.php` as template)
- [ ] fetch_product.php
- [ ] fetch_sales.php
- [ ] fetch_transaction_details.php

### Report Files

- [ ] monthlysales.php
- [ ] topselling.php
- [ ] stockalert.php
- [ ] history.php
- [ ] transaction.php

### Transaction Files

- [ ] save_transaction.php
- [ ] db_addexpense.php
- [ ] db_addproduct.php

---

## 7. COMMON MISTAKES TO AVOID

❌ **WRONG:**

```php
$user_id = $_GET['user_id'];  // Never trust user input!
```

❌ **WRONG:**

```php
$stmt = $conn->prepare("SELECT * FROM tblproduct WHERE product_id = ?");
$stmt->bind_param("i", $_REQUEST['product_id']);
```

❌ **WRONG:**

```php
$query = "SELECT * FROM tblproduct WHERE user_id = $user_id";  // SQL Injection!
```

✅ **CORRECT:**

```php
$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

$stmt = $conn->prepare("SELECT * FROM tblproduct WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
```

---

## 8. DEBUGGING TIPS

### Enable Query Logging

Add to `db_connect_updated.php`:

```php
// Enable MySQLi error logging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
```

### Check User Context

```php
echo $middleware->getUserId();  // Should show user_id
echo $middleware->getUsername();  // Should show username
```

### Verify user_id in Queries

```php
error_log("Query: " . $query);
error_log("User ID: " . $user_id);
```

### Check Database Values

```sql
-- See all products by user
SELECT * FROM tblproduct WHERE user_id = 1;

-- See specific product with user_id
SELECT * FROM tblproduct WHERE product_id = 5 AND user_id = 1;

-- Count products per user
SELECT user_id, COUNT(*) FROM tblproduct GROUP BY user_id;
```

---

## 9. NEXT STEPS

1. **Start with the core files:**
   - Replace `db_connect.php`
   - Replace `login.php`
   - Add `TenantMiddleware.php` and `RequestValidator.php`

2. **Update one endpoint at a time:**
   - Pick a simple one like `fetch_products.php`
   - Test thoroughly
   - Move to next

3. **Test after each change:**
   - Log in as different users
   - Verify data isolation
   - Check error handling

4. **Review the strategy document:**
   - Read `MULTI_TENANCY_STRATEGY.md` for deeper understanding
   - Reference pattern tables
   - Study security sections

---

## 10. SUPPORT RESOURCES

- **Full Documentation:** See `MULTI_TENANCY_STRATEGY.md`
- **Code Examples:** See `*_updated.php` files
- **Database Schema:** See `MIGRATION_SCRIPT.sql`
- **Request Validation:** See `RequestValidator.php` class methods
- **Session Management:** See `TenantMiddleware.php` class methods

---

## Emergency Rollback

If something goes wrong, you can:

1. **Restore from backup** (before migration)
2. **Run rollback SQL** (see MIGRATION_SCRIPT.sql bottom section)
3. **Restore original files** (keep your originals as backup)

---

**Remember:** Security is not an afterthought. Test thoroughly before deploying to production!
