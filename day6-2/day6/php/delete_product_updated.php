<?php
/**
 * EXAMPLE: Updated delete_product.php with Multi-Tenancy + Security
 * 
 * Demonstrates:
 * - Validating product ownership before deletion
 * - Using middleware to prevent ID swapping
 * - Double verification (middleware + query)
 * - Proper error handling
 */

session_start();

// Include dependencies
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';
require_once 'RequestValidator.php';

header('Content-Type: application/json');

try {
    // Initialize middleware - validates user session
    $middleware = new TenantMiddleware($conn, $user_db);
    $validator = new RequestValidator($middleware);

    // Get and validate product_id parameter
    $product_id = $validator->validateNumericId('product_id', 1);
    $user_id = $middleware->getUserId();

    // SECURITY LAYER 1: Middleware verification
    // This ensures the product_id exists and belongs to the user
    $validated_product_id = $validator->validateProductAccess($conn, $product_id);

    // Log the deletion attempt
    $middleware->logAccessAttempt("product_{$product_id}", 'delete', 'attempt');

    // SECURITY LAYER 2: Double-check ownership in DELETE query
    // Even though we already verified, this prevents accidental deletion
    // If someone somehow modifies the query, this WHERE clause saves us
    $stmt = $conn->prepare(
        "DELETE FROM tblproduct 
         WHERE product_id = ? AND user_id = ? 
         LIMIT 1"
    );

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("ii", $validated_product_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception("Delete failed: " . $stmt->error);
    }

    // Check if anything was actually deleted
    if ($conn->affected_rows === 0) {
        $stmt->close();

        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Product not found'
        ]);
        exit;
    }

    $stmt->close();

    // Log successful deletion
    $middleware->logAccessAttempt("product_{$product_id}", 'delete', 'success');

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in delete_product.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while deleting the product'
    ]);
}
?>