<?php
$servername = "localhost"; // Change if needed
$username = "root"; // Your database username
$password = ""; // Your database password (default is empty for XAMPP)
$dbname = "itads22_midterm_amanence"; // Change to your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// } else {
//     echo "Database connected successfully. <br>";
// }

// Test query
$sql = "SELECT * FROM tblstudent";
$result = $conn->query($sql);

// if ($result) {
//     echo "Query executed successfully. Found " . $result->num_rows . " records.<br>";
// } else {
//     die("Query failed: " . $conn->error);
// }
?>