<?php
/**
 * EXAMPLE: Updated addproduct.php with Multi-Tenancy + Insert with user_id
 * 
 * Demonstrates:
 * - Inserting product with user_id automatically
 * - Input validation
 * - Proper error handling
 * - Transaction safety
 */

session_start();

// Include dependencies
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';
require_once 'RequestValidator.php';

header('Content-Type: application/json');

try {
    // Initialize middleware
    $middleware = new TenantMiddleware($conn, $user_db);
    $validator = new RequestValidator($middleware);

    $user_id = $middleware->getUserId();

    // Only POST requests allowed for adding products
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]));
    }

    // Validate inputs
    $product_name = $validator->validateString('product_name', 255);
    $category = $validator->validateString('category', 100);
    $cost_price = $validator->validateDecimal('cost_price', 0, 2);
    $selling_price = $validator->validateDecimal('selling_price', 0, 2);
    $stock = $validator->validateNumericId('stock', 0);

    // Business logic validation
    if ($selling_price < $cost_price) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'error' => 'Selling price cannot be less than cost price'
        ]));
    }

    // Start transaction for data integrity
    $conn->begin_transaction();

    try {
        // CRITICAL: Automatically inject user_id
        $stmt = $conn->prepare(
            "INSERT INTO tblproduct 
            (user_id, product_name, category, cost_price, selling_price, stock, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param(
            "issddi",
            $user_id,
            $product_name,
            $category,
            $cost_price,
            $selling_price,
            $stock
        );

        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to insert product");
        }

        $new_product_id = $conn->insert_id;
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Log successful creation
        $middleware->logAccessAttempt("product_{$new_product_id}", 'create', 'success');

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $new_product_id
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in addproduct.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while adding the product'
    ]);
}
?>