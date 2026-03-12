<?php
/**
 * UPDATED: db_connect.php with Multi-Tenancy Support
 * This file now initializes database connections with user context
 */

// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";

// Two separate databases
$product_db = "db_product";
$user_db_name = "user_db";

// Connection to product database
$conn = new mysqli($servername, $username, $password, $product_db);
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'error' => 'Product database connection failed: ' . $conn->connect_error
    ]));
}
$conn->set_charset("utf8mb4");

// Connection to user database
$user_db = new mysqli($servername, $username, $password, $user_db_name);
if ($user_db->connect_error) {
    die(json_encode([
        'success' => false,
        'error' => 'User database connection failed: ' . $user_db->connect_error
    ]));
}
$user_db->set_charset("utf8mb4");

/**
 * Get Authenticated User ID
 * Returns user_id from users table based on session username
 * Returns null if user not found or session invalid
 */
function getAuthenticatedUserId()
{
    global $user_db;

    // Check session
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        return null;
    }

    if (!isset($_SESSION['username'])) {
        return null;
    }

    // Query user database
    $stmt = $user_db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) {
        error_log("Database prepare error: " . $user_db->error);
        return null;
    }

    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }

    $row = $result->fetch_assoc();
    $user_id = intval($row['id']);
    $stmt->close();

    return $user_id;
}

/**
 * Validate User Exists in database
 */
function validateUserExists($user_id)
{
    global $user_db;

    $user_id = intval($user_id);

    $stmt = $user_db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

/**
 * Build a secure query with automatic user_id filtering
 */
function buildSecureQuery($table, $where = "", $columns = "*")
{
    $user_id = getAuthenticatedUserId();
    if ($user_id === null) {
        return null;
    }

    $user_id = intval($user_id);
    $where_clause = "user_id = {$user_id}";

    if (!empty($where)) {
        $where_clause .= " AND ({$where})";
    }

    return "SELECT {$columns} FROM {$table} WHERE {$where_clause}";
}

/**
 * Enforce User Context (used in middleware)
 * Terminates execution if user_id doesn't match authenticated user
 */
function enforceUserContext($requested_user_id)
{
    $authenticated_id = getAuthenticatedUserId();
    $requested_user_id = intval($requested_user_id);

    if ($authenticated_id === null || $authenticated_id !== $requested_user_id) {
        http_response_code(403);
        die(json_encode([
            'success' => false,
            'error' => 'Access Denied'
        ]));
    }
}
?>