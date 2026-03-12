# Multi-Tenancy Isolation Strategy for POS System

## Executive Summary

This document outlines a complete strategy for implementing user-level data isolation in your Sari-Sari Store POS system. This ensures each user sees and modifies only their own data.

---

## 1. SCHEMA DESIGN

### 1.1 Database Modifications

Add `user_id` foreign key to all data tables:

```sql
-- Modify tblproduct table
ALTER TABLE tblproduct ADD COLUMN user_id INT NOT NULL AFTER product_id;
ALTER TABLE tblproduct ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE tblproduct ADD INDEX idx_user_product (user_id, product_id);

-- Modify tblexpense table
ALTER TABLE tblexpense ADD COLUMN user_id INT NOT NULL AFTER id;
ALTER TABLE tblexpense ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE tblexpense ADD INDEX idx_user_expense (user_id, id);

-- Modify transactions table
ALTER TABLE transactions ADD COLUMN user_id INT NOT NULL AFTER id;
ALTER TABLE transactions ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE transactions ADD INDEX idx_user_transaction (user_id, id);

-- Modify transaction_details (implicit through transactions relationship)
-- But add direct reference for safety
ALTER TABLE transaction_details ADD COLUMN user_id INT AFTER id;
ALTER TABLE transaction_details ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE transaction_details ADD INDEX idx_user_details (user_id, transaction_id);
```

### 1.2 Schema Diagram

```
users (user_db)
├── id (PK)
├── username (UNIQUE)
├── email (UNIQUE)
└── password

tblproduct (db_product) ──┐
├── product_id (PK)       │
├── user_id (FK) ◄────────┤
├── product_name          │
├── category              │  All linked to
├── cost_price            │  user_id
└── stock                 │

tblexpense (db_product) ──┤
├── id (PK)               │
├── user_id (FK) ◄────────┤
├── expense_category      │
├── amount                │
└── notes                 │

transactions (db_product) ┤
├── id (PK)               │
├── user_id (FK) ◄────────┤
├── total_quantity        │
└── total_price           │

transaction_details ──────┤
├── id (PK)               │
├── user_id (FK) ◄────────┘
├── transaction_id
└── product_id
```

---

## 2. FILTERING LOGIC

### 2.1 Core Principles

Every query MUST include a WHERE clause filtering by authenticated user's ID:

```
Basic Pattern: WHERE user_id = $authenticated_user_id
```

### 2.2 Implementation Methods

#### Method A: Centralized Query Wrapper (RECOMMENDED)

Create a query builder class that automatically adds user context:

```php
// QueryBuilder.php
class SecureQueryBuilder {
    private $conn;
    private $user_id;
    private $user_db;

    public function __construct($conn, $user_id, $user_db) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->user_db = $user_db;

        if (!$this->validateUserExists()) {
            throw new Exception("Invalid user context");
        }
    }

    /**
     * Validates user exists in user_db
     */
    private function validateUserExists() {
        $stmt = $this->user_db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Get user ID with validation
     */
    public function getUserId() {
        return $this->user_id;
    }

    /**
     * Build scoped SELECT query
     */
    public function select($table, $columns = "*", $where = "") {
        $where_clause = "user_id = " . intval($this->user_id);
        if ($where) {
            $where_clause .= " AND " . $where;
        }
        return "SELECT {$columns} FROM {$table} WHERE {$where_clause}";
    }

    /**
     * Build scoped UPDATE query with validation
     */
    public function update($table, $set, $where = "") {
        $where_clause = "user_id = " . intval($this->user_id);
        if ($where) {
            $where_clause .= " AND " . $where;
        }
        return "UPDATE {$table} SET {$set} WHERE {$where_clause}";
    }

    /**
     * Build scoped DELETE query with validation
     */
    public function delete($table, $where = "") {
        $where_clause = "user_id = " . intval($this->user_id);
        if ($where) {
            $where_clause .= " AND " . $where;
        }
        return "DELETE FROM {$table} WHERE {$where_clause}";
    }

    /**
     * Execute prepared statement with automatic user_id
     */
    public function executeSelect($query_template, $params = [], $param_types = "") {
        // Inject user_id into the query
        if (strpos($query_template, "?") !== false) {
            // Prepend user_id parameter
            array_unshift($params, $this->user_id);
            $param_types = "i" . $param_types;
        }

        // Replace first ? with user_id
        $query = preg_replace('/WHERE\s+/i', 'WHERE user_id = ? AND ', $query_template, 1);

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query Error: " . $this->conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }
}
```

#### Method B: Middleware Validation Wrapper

```php
// TenantMiddleware.php
class TenantMiddleware {
    private $conn;
    private $user_db;
    private $user_id;

    public function __construct($conn, $user_db) {
        $this->conn = $conn;
        $this->user_db = $user_db;
        $this->validateSession();
    }

    /**
     * Ensure user is authenticated and retrieve verified ID
     */
    public function validateSession() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            die(json_encode(['error' => 'Unauthorized']));
        }

        // Get user_id from users table using username from session
        $stmt = $this->user_db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            session_destroy();
            http_response_code(401);
            die(json_encode(['error' => 'User session invalid']));
        }

        $row = $result->fetch_assoc();
        $this->user_id = intval($row['id']);
        $stmt->close();
    }

    public function getUserId() {
        return $this->user_id;
    }

    /**
     * Prevent direct access to unscoped endpoints
     */
    public function enforceUserContext($requested_user_id) {
        if ((int)$requested_user_id !== $this->user_id) {
            http_response_code(403);
            die(json_encode(['error' => 'Access Denied - User ID Mismatch']));
        }
        return true;
    }
}
```

---

## 3. SECURITY: PREVENTING ID SWAPPING

### 3.1 The ID Swapping Attack

**Vulnerability**: User changes a parameter from `?user_id=1` to `?user_id=2` and gains access to another user's data.

**Examples of vulnerable code**:

```php
// BAD: Direct parameter usage
$user_id = $_GET['user_id'];  // Direct parameter - INSECURE
$query = "SELECT * FROM tblproduct WHERE user_id = $user_id";

// BAD: User modifies request
// GET /fetch_products.php?user_id=999
// Attacker tries to access user 999's products
```

### 3.2 Defense Strategy

#### 3.2.1 NEVER Trust Client-Supplied User ID

```php
// GOOD: Get user ID from session only
session_start();
$authenticated_user_id = $_SESSION['user_id'];  // From session, not request

// Get user_id from database using authenticated username
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$user_id = $stmt->get_result()->fetch_assoc()['id'];

// Now use this verified user_id
$query = $conn->prepare("SELECT * FROM tblproduct WHERE user_id = ?");
$query->bind_param("i", $user_id);
```

#### 3.2.2 Implement Row-Level Verification

```php
// When user requests a specific resource, verify ownership BEFORE returning

class SecureResource {
    private $conn;
    private $authenticated_user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->authenticated_user_id = intval($user_id);
    }

    /**
     * Get product - ONLY if it belongs to authenticated user
     */
    public function getProduct($product_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM tblproduct
             WHERE product_id = ? AND user_id = ?
             LIMIT 1"
        );
        $stmt->bind_param("ii", $product_id, $this->authenticated_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Product either doesn't exist OR doesn't belong to this user
            http_response_code(404);
            die(json_encode(['error' => 'Product not found']));
        }

        return $result->fetch_assoc();
    }

    /**
     * Verify transaction belongs to user before modification
     */
    public function verifyTransactionOwnership($transaction_id) {
        $stmt = $this->conn->prepare(
            "SELECT id FROM transactions
             WHERE id = ? AND user_id = ?
             LIMIT 1"
        );
        $stmt->bind_param("ii", $transaction_id, $this->authenticated_user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Transaction access denied");
        }
    }

    /**
     * Delete product - only if owned by user
     */
    public function deleteProduct($product_id) {
        // First verify ownership
        $stmt = $this->conn->prepare(
            "DELETE FROM tblproduct
             WHERE product_id = ? AND user_id = ?
             LIMIT 1"
        );
        $stmt->bind_param("ii", $product_id, $this->authenticated_user_id);

        if (!$stmt->execute()) {
            throw new Exception("Delete failed: " . $stmt->error);
        }

        // Check if anything was actually deleted
        if ($this->conn->affected_rows === 0) {
            http_response_code(404);
            die(json_encode(['error' => 'Product not found or access denied']));
        }
    }
}
```

#### 3.2.3 Prevent Parameter Manipulation

```php
// Whitelist and validate all incoming parameters

class RequestValidator {
    private $authenticated_user_id;

    public function __construct($user_id) {
        $this->authenticated_user_id = intval($user_id);
    }

    /**
     * Validate product_id and ensure it belongs to authenticated user
     */
    public function validateProductAccess($conn, $product_id) {
        $product_id = intval($product_id);

        // Verify product exists and belongs to user
        $stmt = $conn->prepare(
            "SELECT 1 FROM tblproduct
             WHERE product_id = ? AND user_id = ?
             LIMIT 1"
        );
        $stmt->bind_param("ii", $product_id, $this->authenticated_user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            die(json_encode(['error' => 'Unauthorized']));
        }

        return $product_id;
    }

    /**
     * Validate numeric parameter
     */
    public function validateNumeric($param, $name, $min = 1) {
        if (!is_numeric($param) || $param < $min) {
            http_response_code(400);
            die(json_encode(['error' => "Invalid {$name}"]));
        }
        return intval($param);
    }

    /**
     * Validate string parameter
     */
    public function validateString($param, $name, $max_length = 255) {
        if (!is_string($param) || strlen($param) > $max_length || empty($param)) {
            http_response_code(400);
            die(json_encode(['error' => "Invalid {$name}"]));
        }
        return trim($param);
    }
}
```

#### 3.2.4 API Request Signing (Advanced)

```php
// For sensitive operations, require request signature
class RequestSigner {
    private $secret_key;

    public function __construct($secret_key) {
        $this->secret_key = $secret_key;
    }

    /**
     * Generate signature for request
     */
    public function generateSignature($user_id, $action, $timestamp) {
        $data = "{$user_id}:{$action}:{$timestamp}";
        return hash_hmac('sha256', $data, $this->secret_key);
    }

    /**
     * Verify request signature
     */
    public function verifySignature($user_id, $action, $timestamp, $signature) {
        // Check timestamp is recent (within 5 minutes)
        if (abs(time() - intval($timestamp)) > 300) {
            throw new Exception("Request expired");
        }

        $expected = $this->generateSignature($user_id, $action, $timestamp);

        // Use constant-time comparison to prevent timing attacks
        return hash_equals($expected, $signature);
    }
}
```

---

## 4. IMPLEMENTATION CHECKLIST

### Phase 1: Database Migration

- [ ] Add `user_id` columns to all tables
- [ ] Add foreign key constraints
- [ ] Add composite indexes on (user_id, primary_key)
- [ ] Update existing data with user_id (if any)
- [ ] Test foreign key constraints

### Phase 2: Session Management

- [ ] Store `user_id` in session after login verification
- [ ] Update login.php to fetch and store user_id
- [ ] Create session validation middleware
- [ ] Add session timeout and validation checks

### Phase 3: Query Updates

- [ ] Replace all SELECT queries (add WHERE user_id = ?)
- [ ] Replace all UPDATE queries (add AND user_id = ?)
- [ ] Replace all DELETE queries (add AND user_id = ?)
- [ ] Use prepared statements with bind_param
- [ ] Remove any hardcoded or user-supplied user_id values

### Phase 4: Security Hardening

- [ ] Implement TenantMiddleware in all endpoints
- [ ] Add row-level verification for resource access
- [ ] Implement RequestValidator
- [ ] Add comprehensive logging
- [ ] Test ID swapping attempts

### Phase 5: Testing

- [ ] Unit tests for user isolation
- [ ] Integration tests for multi-user scenarios
- [ ] Security penetration testing
- [ ] Query performance testing with indexes

---

## 5. MIGRATION GUIDE

### 5.1 For Existing Users

If you have existing data, migrate it to user 1:

```sql
-- Start transaction
START TRANSACTION;

-- Update tblproduct
UPDATE tblproduct SET user_id = 1 WHERE user_id IS NULL;

-- Update tblexpense
UPDATE tblexpense SET user_id = 1 WHERE user_id IS NULL;

-- Update transactions
UPDATE transactions SET user_id = 1 WHERE user_id IS NULL;

-- Update transaction_details
UPDATE transaction_details SET user_id = 1 WHERE user_id IS NULL;

-- Add NOT NULL constraint
ALTER TABLE tblproduct MODIFY user_id INT NOT NULL;
ALTER TABLE tblexpense MODIFY user_id INT NOT NULL;
ALTER TABLE transactions MODIFY user_id INT NOT NULL;
ALTER TABLE transaction_details MODIFY user_id INT NOT NULL;

COMMIT;
```

### 5.2 Testing the Migration

```php
// Test script to verify isolation
$user1_products = $conn->query(
    "SELECT COUNT(*) as count FROM tblproduct WHERE user_id = 1"
)->fetch_assoc();

$user2_products = $conn->query(
    "SELECT COUNT(*) as count FROM tblproduct WHERE user_id = 2"
)->fetch_assoc();

echo "User 1 Products: " . $user1_products['count'] . "\n";
echo "User 2 Products: " . $user2_products['count'] . "\n";
```

---

## 6. LOGGING & AUDIT TRAIL

```php
// Create audit log table
CREATE TABLE audit_log (
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

// Log all modifications
class AuditLogger {
    private $conn;

    public function log($user_id, $action, $table, $record_id, $old_val, $new_val) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        $stmt = $this->conn->prepare(
            "INSERT INTO audit_log
            (user_id, action, table_name, record_id, old_value, new_value, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issiiss",
            $user_id, $action, $table, $record_id,
            $old_val, $new_val, $ip
        );

        $stmt->execute();
        $stmt->close();
    }
}
```

---

## 7. SUMMARY TABLE: Query Patterns

| Operation                | Pattern                             | Security Level           |
| ------------------------ | ----------------------------------- | ------------------------ |
| SELECT                   | WHERE user_id = ?                   | ✅ Secure                |
| INSERT                   | VALUES (?, ?, ..., ?) with user_id  | ✅ Secure                |
| UPDATE                   | WHERE record_id = ? AND user_id = ? | ✅ Secure                |
| DELETE                   | WHERE record_id = ? AND user_id = ? | ✅ Secure                |
| SELECT with user param   | Always validate param               | ⚠️ Risk if not validated |
| Direct $\_GET['user_id'] | NEVER use                           | ❌ CRITICAL              |

---

## Key Takeaways

1. **Never trust client input for user_id**
   - Always retrieve from session/authenticated context
   - Verify against users table

2. **All queries must be scoped**
   - Use prepared statements with ? placeholders
   - Always include WHERE user_id = authenticated_user_id

3. **Defense in depth**
   - Database level: Foreign keys, constraints
   - Application level: Middleware, validators
   - Row level: Verify ownership before access
   - Audit level: Log all access attempts

4. **Test thoroughly**
   - Try accessing other users' resources
   - Monitor for SQL injection attempts
   - Review audit logs regularly
