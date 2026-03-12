<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

// Check if product_id is provided
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // Delete query - verify ownership
    $sql = "DELETE FROM tblproduct WHERE product_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $product_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Product record deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting record: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}

// Redirect back to the student list page
header("Location: viewproduct.php");
exit();
?>