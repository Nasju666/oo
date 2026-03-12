<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

try {
    $productName = $_GET['product_name'] ?? '';

    if (empty($productName)) {
        echo json_encode(['error' => 'Product name is required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT product_id, product_name, category, selling_price as price, stock FROM tblproduct WHERE product_name = ? AND user_id = ?");

    if (!$stmt) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("si", $productName, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Product not found']);
        $stmt->close();
        exit;
    }

    $product = $result->fetch_assoc();
    echo json_encode($product);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>