<?php
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $middleware = new TenantMiddleware($conn, $user_db);
    $user_id = $middleware->getUserId();

    $stmt = $conn->prepare("INSERT INTO tblexpense (user_id, expense_category, expense_description, amount, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $user_id, $expense_category, $expense_description, $amount, $notes);

    // Assign values
    $expense_category = trim($_POST['expense_category']);
    $expense_description = trim($_POST['expense_description']);
    $amount = floatval($_POST['amount']);
    $notes = trim($_POST['notes']);

    // Execute query
    if ($stmt->execute()) {
        header("Location: expensetracker.php?success=1"); // Redirect after success
        echo "Expense added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }


    $stmt->close();
    $conn->close();
}
?>