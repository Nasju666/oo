<?php
session_start();
include 'db_connect.php';

// Check if student_id is provided
if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);

    // Prepare delete query
    $sql = "DELETE FROM tblstudent WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Student record deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting record: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}

// Redirect back to the student list page
header("Location: view_student.php");
exit();
?>