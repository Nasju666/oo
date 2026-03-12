<?php
/**
 * EXAMPLE: Updated fetch_products.php with Multi-Tenancy Isolation
 * 
 * Demonstrates:
 * - Using TenantMiddleware for authentication
 * - RequestValidator for input validation
 * - Scoped queries (WHERE user_id = ?)
 * - JSON response format
 */

session_start();

// Include dependencies
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';
require_once 'RequestValidator.php';

// Set response header
header('Content-Type: application/json');

try {
    // Initialize middleware - validates user session and gets user_id
    $middleware = new TenantMiddleware($conn, $user_db);
    $validator = new RequestValidator($middleware);

    $user_id = $middleware->getUserId();

    // Get optional search/filter parameters
    $category = isset($_REQUEST['category']) ? $validator->validateString('category', 100, false) : null;
    $search = isset($_REQUEST['search']) ? $validator->validateString('search', 255, false) : null;

    // Build query with automatic user_id filtering
    $query = "SELECT 
                product_id,
                product_name,
                category,
                cost_price,
                selling_price,
                stock,
                created_at
              FROM tblproduct 
              WHERE user_id = ?";

    $params = [(int) $user_id];
    $param_types = "i";

    // Add optional filters
    if (!is_null($category)) {
        $query .= " AND category = ?";
        $params[] = $category;
        $param_types .= "s";
    }

    if (!is_null($search)) {
        $query .= " AND product_name LIKE ?";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $param_types .= "s";
    }

    $query .= " ORDER BY product_name ASC";

    // Prepare and execute with proper binding
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    // Bind parameters dynamically
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'product_id' => $row['product_id'],
            'product_name' => htmlspecialchars($row['product_name']),
            'category' => htmlspecialchars($row['category']),
            'cost_price' => (float) $row['cost_price'],
            'selling_price' => (float) $row['selling_price'],
            'stock' => (int) $row['stock'],
            'created_at' => $row['created_at']
        ];
    }

    $stmt->close();

    // Log successful access
    $middleware->logAccessAttempt('products', 'fetch', 'success');

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_products.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching products'
    ]);
}
?>