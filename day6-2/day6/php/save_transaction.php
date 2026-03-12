<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

try {
    $middleware = new TenantMiddleware($conn, $user_db);
    $user_id = $middleware->getUserId();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $totalQuantity = $_POST['totalQuantity'];
        $totalPrice = $_POST['totalPrice'];
        $received = $_POST['received'];
        $status = $totalPrice > $received ? 'insufficient' : 'completed';

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into transactions table with user_id
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, total_quantity, total_price, received, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iidds", $user_id, $totalQuantity, $totalPrice, $received, $status);
            $stmt->execute();
            $transactionId = $conn->insert_id;

            $products = json_decode($_POST['products'], true);

            foreach ($products as $product) {
                // Insert transaction details with user_id
                $stmt = $conn->prepare("INSERT INTO transaction_details (user_id, transaction_id, product_id, product_name, category, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisssid", $user_id, $transactionId, $product['product_id'], $product['product_name'], $product['category'], $product['price'], $product['quantity'], $product['subtotal']);
                $stmt->execute();

                // Update product stock - verify ownership
                $stmt = $conn->prepare("UPDATE tblproduct SET stock = stock - ? WHERE product_id = ? AND user_id = ?");
                $stmt->bind_param("iii", $product['quantity'], $product['product_id'], $user_id);
                $stmt->execute();
            }

            // Commit transaction
            $conn->commit();
            header("Location: transaction.php?success=1");
            exit;
        } catch (Exception $e) {
            // Rollback transaction if something failed
            $conn->rollback();
            header("Location: transaction.php?error=1&message=" . urlencode($e->getMessage()));
            exit;
        }
    }
} catch (Exception $e) {
    header("Location: transaction.php?error=1&message=" . urlencode($e->getMessage()));
    exit;
}
?>