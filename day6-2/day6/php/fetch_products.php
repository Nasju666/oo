<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

// Query to fetch products for authenticated user only
$query = "SELECT product_name, category, cost_price, selling_price, stock, created_at FROM tblproduct WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die(json_encode(["error" => "Query failed: " . $conn->error]));
}

// Fetch data as an associative array
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($products);

$conn->close();
?>