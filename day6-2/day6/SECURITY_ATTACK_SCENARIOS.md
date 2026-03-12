# Multi-Tenancy Security: Attack Scenarios & Defenses

## ATTACK SCENARIO 1: Direct ID Swapping

### The Attack

User A tries to access User B's products by changing the URL parameter:

```
// User A (ID: 1) logs in
// Sees URL: /fetch_products.php?user_id=1

// Attacker tries to change it to:
/fetch_products.php?user_id=2
// Hopes to see User B's products (ID: 2)
```

### ❌ VULNERABLE CODE (Old Implementation)

```php
$user_id = $_GET['user_id'];  // DIRECTLY FROM USER INPUT!
$query = "SELECT * FROM tblproduct WHERE user_id = $user_id";
$result = $conn->query($query);
```

### ✅ PROTECTED CODE (New Implementation)

```php
// 1. Ignore the URL parameter completely
// 2. Get user_id only from authenticated session
$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();  // Verified from database

// 3. Always include in WHERE clause
$stmt = $conn->prepare("SELECT * FROM tblproduct WHERE user_id = ?");
$stmt->bind_param("i", $user_id);  // Bound parameter, not concatenation
$stmt->execute();

// Result: User B's data cannot be accessed because the query
// will only return products WHERE user_id = 1 (User A's ID)
```

### Defense Layers

| Layer           | Protection                                                    |
| --------------- | ------------------------------------------------------------- |
| **Network**     | Ignore all user-supplied `user_id` parameters                 |
| **Application** | Get user_id only from TenantMiddleware                        |
| **Database**    | Query always includes `WHERE user_id = authenticated_user_id` |
| **Logging**     | Attempt is logged for audit trail                             |

---

## ATTACK SCENARIO 2: SQL Injection + ID Swapping

### The Attack

Attacker crafts a malicious query to bypass user_id filter:

```
GET /fetch_products.php?product_id=1 OR 1=1
// Tries to make WHERE clause evaluate to true for all records
```

### ❌ VULNERABLE CODE

```php
$product_id = $_GET['product_id'];
$query = "SELECT * FROM tblproduct WHERE product_id = $product_id";  // NO PREPARED STATEMENT
$result = $conn->query($query);

// SQL becomes: SELECT * FROM tblproduct WHERE product_id = 1 OR 1=1
// This returns ALL products, not just the intended one!
```

### ✅ PROTECTED CODE

```php
$product_id = $_GET['product_id'];

// Prepared statement prevents SQL injection
$stmt = $conn->prepare(
    "SELECT * FROM tblproduct WHERE product_id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();

// Even if attacker sends '1 OR 1=1', it's treated as a literal value
// SQL becomes: WHERE product_id = '1 OR 1=1' AND user_id = 1
// No match found, returns empty result
```

---

## ATTACK SCENARIO 3: API Parameter Manipulation

### The Attack

User modifies POST/GET parameters to request deletion of another user's product:

```
POST /delete_product.php
product_id=5&user_id=2
// Attacker is user_id 1, but sends user_id=2 to confuse the system
```

### ❌ VULNERABLE CODE

```php
$product_id = $_POST['product_id'];
$user_id = $_POST['user_id'];  // DIRECTLY FROM FORM!

$query = "DELETE FROM tblproduct WHERE product_id = $product_id AND user_id = $user_id";
```

### ✅ PROTECTED CODE

```php
// Step 1: Ignore the user_id from the request
// $user_id = $_POST['user_id'];  // NEVER DO THIS!

// Step 2: Get authenticated user_id only from session
$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();  // Returns 1 (actual authenticated user)

// Step 3: Validate product exists AND belongs to this user
$validator = new RequestValidator($middleware);
$product_id = $validator->validateProductAccess($conn, $product_id);

// Step 4: Double-check in DELETE query
$stmt = $conn->prepare(
    "DELETE FROM tblproduct WHERE product_id = ? AND user_id = ? LIMIT 1"
);
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();

// Result: Even though attacker sent user_id=2,
// the delete uses user_id=1, and product 5 belongs to user 2,
// so nothing is deleted. Returns 404.
```

---

## ATTACK SCENARIO 4: Session Hijacking

### The Attack

Attacker obtains another user's session cookie and uses it:

```
// Attacker steals User B's session cookie
// Sets it in their browser
// Now appears to be logged in as User B
```

### ❌ VULNERABLE CODE

```php
// If application only checks if session exists
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    // Allows access
    // But doesn't verify the user still exists!
}
```

### ✅ PROTECTED CODE

```php
class TenantMiddleware {
    private function validateSession() {
        // Check 1: Is session active?
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            die('Unauthorized');
        }

        // Check 2: Query database to VERIFY user still exists
        $stmt = $this->user_db->prepare(
            "SELECT id FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();

        // If user was deleted from database, deny access
        if ($stmt->get_result()->num_rows === 0) {
            session_destroy();
            die('User no longer exists');
        }
    }
}

// Result: Even if attacker has a valid session cookie,
// if the user was deleted from the database, access is denied.
```

---

## ATTACK SCENARIO 5: Data Exposure Through REST API

### The Attack

Attacker calls API endpoints trying different IDs:

```
GET /fetch_product.php?product_id=1
GET /fetch_product.php?product_id=2
GET /fetch_product.php?product_id=3
... iterating to find other users' products
```

### ❌ VULNERABLE CODE

```php
function getProduct($id) {
    $query = "SELECT * FROM tblproduct WHERE product_id = $id";
    return $conn->query($query);
    // Returns product regardless of who owns it!
}
```

### ✅ PROTECTED CODE

```php
function getProduct($product_id) {
    // Validate input
    $product_id = intval($product_id);
    $user_id = $middleware->getUserId();

    // ALWAYS require user_id in WHERE clause
    $stmt = $conn->prepare(
        "SELECT * FROM tblproduct WHERE product_id = ? AND user_id = ? LIMIT 1"
    );
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(404);
        return ['error' => 'Product not found'];
    }

    return $stmt->get_result()->fetch_assoc();
}

// Result: Only products belonging to authenticated user are returned.
// Attacker scanning IDs 1-1000 will get 404 for all except their own.
```

---

## ATTACK SCENARIO 6: Race Condition (Edit/Delete)

### The Attack

User A tries to delete Product X just as User A is editing it:

```
Thread 1: SELECT * FROM tblproduct WHERE product_id = 5
          // Gets product
Thread 2: DELETE FROM tblproduct WHERE product_id = 5
          // Deletes it
Thread 1: UPDATE tblproduct SET ... WHERE product_id = 5
          // Updates non-existent product?
```

### ✅ PROTECTED CODE

```php
// Always include user_id in UPDATE/DELETE queries
// This prevents touching someone else's record even if IDs align

// If User A deletes, only their product 5 is deleted
$stmt = $conn->prepare(
    "DELETE FROM tblproduct WHERE product_id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $product_id, $user_id);

// If User B tries to delete the same ID, their product 5 (different record) is deleted
// User A's product 5 (if it still exists) cannot be touched

// Result: Isolation prevents cross-user interference
```

---

## ATTACK SCENARIO 7: Privilege Escalation

### The Attack

User tries to perform admin actions they don't have permission for:

```
POST /edit_user.php?user_id=1
new_role=admin
// Attacker tries to make themselves admin
```

### ✅ PROTECTED CODE

```php
// Don't trust user_id parameter
$middleware = new TenantMiddleware($conn, $user_db);
$authenticated_user_id = $middleware->getUserId();

// Only allow editing your own profile
if (intval($_POST['user_id']) !== $authenticated_user_id) {
    http_response_code(403);
    die(['error' => 'Cannot modify another user']);
}

// Even if allowing self-edit, never trust the "role" parameter
// Instead, look it up from database
$user_data = getUserFromDatabase($authenticated_user_id);
// Role is read from database, not from user input
```

---

## DEFENSE CHECKLIST

### ✅ Authentication Layer (TenantMiddleware)

- [ ] User must be logged in (SESSION check)
- [ ] Username must exist in database (DB verification)
- [ ] User ID is retrieved from database, not client input
- [ ] Invalid sessions are destroyed

### ✅ Authorization Layer (RequestValidator)

- [ ] user_id in request is ignored (never used)
- [ ] Record ownership is verified before access
- [ ] Numeric IDs are validated as integers only
- [ ] String inputs are length-limited and sanitized

### ✅ Data Layer (SQL Queries)

- [ ] All queries use prepared statements with bind_param()
- [ ] No string concatenation with user input
- [ ] WHERE clauses include user_id filter
- [ ] UPDATE/DELETE queries also scope to user_id

### ✅ Response Layer (Error Handling)

- [ ] 404 returned if record not found OR doesn't belong to user
- [ ] No SQL errors shown to user (logged only)
- [ ] No data leakage in error messages
- [ ] Changes in affected_rows indicate success/failure

### ✅ Audit Layer (Logging)

- [ ] All access attempts are logged
- [ ] Failed accesses are logged with IP address
- [ ] Data modifications are logged
- [ ] Suspicious patterns trigger alerts

---

## Testing Checklist

### Basic Isolation Tests

- [ ] Login as User A, can see their products
- [ ] Login as User B, cannot see User A's products
- [ ] Try URL with User A's product_id while logged in as User B → 404
- [ ] Try POST to delete User A's product while logged in as User B → 404

### SQL Injection Tests

- [ ] Send `product_id=1 OR 1=1` → Doesn't return all products
- [ ] Send `product_id=1; DROP TABLE;` → Query fails safely
- [ ] Send `product_id=1' OR '1'='1` → Doesn't bypass filtering

### Parameter Manipulation Tests

- [ ] Send `user_id=999` in request → Ignored, uses session user_id
- [ ] Send `user_id=` (empty) in request → Returns authenticated user_id
- [ ] Modify any ID parameter in URL → Only authenticated user's data returned

### Session Tests

- [ ] Delete user from database while session exists → Next request denies access
- [ ] Use expired session → Redirects to login
- [ ] Use fabricated session cookie → Session validation fails

---

## Security Maturity Levels

### Level 1 (Basic) ✅

- Session-based authentication
- user_id stored in session
- Basic privilege checks in code

### Level 2 (Intermediate) ✅ ← YOU ARE HERE

- Prepared statements
- Row-level user_id verification
- RequestValidator for input sanitization
- Foreign key constraints in database

### Level 3 (Advanced)

- Request signing/tokens
- Rate limiting
- Audit log analysis
- Anomaly detection
- Regular penetration testing

### Level 4 (Enterprise)

- End-to-end encryption
- Zero-trust architecture
- Hardware security modules
- Compliance certifications

---

**Your implementation now protects against:**

- ✅ Direct ID swapping
- ✅ SQL injection
- ✅ Parameter manipulation
- ✅ Unauthorized data access
- ✅ Session hijacking (with DB verification)
- ✅ API enumeration
- ✅ Privilege escalation (within user's scope)

**Remaining considerations (for future enhancement):**

- Rate limiting (prevent brute force)
- Encryption at rest (sensitive data)
- HTTPS/TLS (in transit)
- Regular security audits
- Vulnerability scanning
