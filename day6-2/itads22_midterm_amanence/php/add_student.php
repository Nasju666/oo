<?php
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input values
    $f_name = isset($_POST['f_name']) ? trim($_POST['f_name']) : '';
    $m_name = isset($_POST['m_name']) ? trim($_POST['m_name']) : '';
    $l_name = isset($_POST['l_name']) ? trim($_POST['l_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $email_address = isset($_POST['email_address']) ? trim($_POST['email_address']) : '';
    $date_enrolled = isset($_POST['date_enrolled']) ? trim($_POST['date_enrolled']) : '';

    if (empty($f_name) || empty($m_name) || empty($l_name) || empty($address) || empty($email_address) || empty($date_enrolled)) {
        die("All fields are required.");
    }

    // Prepare and execute SQL query
    $stmt = $conn->prepare("INSERT INTO tblstudent (f_name, m_name, l_name, address, email_address, date_enrolled) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $f_name, $m_name, $l_name, $address, $email_address, $date_enrolled);

    if ($stmt->execute()) {
        header("Location: index.php?success=1"); // Redirect after success
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>
