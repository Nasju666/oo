<?php
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $middleware = new TenantMiddleware($conn, $user_db);
    $user_id = $middleware->getUserId();

    $stmt = $conn->prepare("INSERT INTO tblproduct (user_id, product_name, category, cost_price, selling_price, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issddi", $user_id, $product_name, $category, $cost_price, $selling_price, $stock);

    // Assign values
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $cost_price = floatval($_POST['cost_price']);
    $selling_price = floatval($_POST['selling_price']);
    $stock = intval($_POST['stock']);

    // Execute query
    if ($stmt->execute()) {
        header("Location: addproduct.php?success=1"); // Redirect after success
        echo "Product added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }


    $stmt->close();
    $conn->close();
}
?>