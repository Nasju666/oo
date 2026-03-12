<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

try {
    $middleware = new TenantMiddleware($conn, $user_db);
    $user_id = $middleware->getUserId();

    $transactionId = $_GET['transaction_id'] ?? 0;

    $stmt = $conn->prepare("
        SELECT product_id, product_name, category, price, quantity, subtotal 
        FROM transaction_details 
        WHERE transaction_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $transactionId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($products);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>