<?php
session_start();
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"]
    !== true
) {
    header("location: login.php");
    exit();
}
include 'db_connect.php';

// Fetch student records
$sql = "SELECT student_id, f_name, m_name, l_name, address, email_address, date_enrolled FROM tblstudent";
$result = $conn->query($sql);

// Debug: Check if the query executes properly
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* View Students CSS */

        .nav-container {
            border-bottom: 2px solid #2c67f233;
            box-shadow: 0px 10px 10px 0px #2c67f233;
        }

        nav {
            display: flex;
            justify-content: space-between;
            margin: 10px;
            margin-left: 200px;
            margin-right: 200px;
            text-decoration: none;
        }

        .nav-header {
            display: flex;
            justify-content: center;
        }

        .nav-header a {
            text-decoration: none;
            color: #010490;
        }

        #logo-text {
            font-size: 28px;
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            font-weight: 600;
        }

        .nav-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;

        }


        .nav-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .nav-logo {
            height: 75px;
        }

        nav ul li {
            padding-left: 50px;
            text-decoration: none;
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            font-size: 22px;
            font-weight: 500;
        }

        nav ul {
            list-style-type: none;
        }

        nav ul li a {
            text-decoration: none;
        }

        body {
            background: linear-gradient(60deg, #ebf6fa, #d1f0f5);
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
        }

        table {
            width: 90%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            margin-left: auto;
            margin-right: auto;
        }

        th,
        td {
            border: 1px solid #62cff4;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #2c67f2;
            color: white;
        }

        tr:nth-child(odd) {
            background-color: rgba(61, 121, 250, 0.16);
        }

        tr td a {
            text-decoration: none;
            color: white;
            padding: 5px 10px;
            background: #007bff;
            border-radius: 5px;
        }

        .delete {
            background-color: rgb(245, 73, 73);
        }

        h1 {
            margin-top: 50px;
            margin-bottom: 10px;
            font-size: 45px;
        }

        #out {
            background-color: rgb(233, 30, 30);
            padding: 5px 10px;
            border-radius: 10px;
            color: white;
            transition: all ease 0.3s;
        }

        #out:hover {
            text-decoration: none;
            background-color: rgb(235, 93, 93);
            padding: 10px 10px;
        }
    </style>
</head>

<body>
    <div class="nav-container">
        <nav>
            <div class="nav-header">
                <div class="nav-logo">
                    <a href="home.php" class="logow"><img src="ceclogs.png" alt="Logo" class="nav-logo"></a>
                    <a href="home.php" class="logow" id="logo-text">Student Portal</a>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="home.php">Home</a></li>
                <li><a href="index.php">Add Student</a></li>
                <li><a href="view_student.php">View Students</a></li>
                <li><a id="out" href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>

    <h1>Student List</h1>
    <br><br>

    <table>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Address</th>
            <th>Email</th>
            <th>Date Enrolled</th>
            <th>Actions</th>
        </tr>

        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['student_id']}</td>
                    <td>{$row['f_name']}</td>
                    <td>{$row['m_name']}</td>
                    <td>{$row['l_name']}</td>
                    <td>{$row['address']}</td>
                    <td>{$row['email_address']}</td>
                    <td>{$row['date_enrolled']}</td>
                    <td>
                        <a href='edit_student.php?id={$row['student_id']}'>Edit</a> |
                        <a class='delete' href='delete_student.php?id={$row['student_id']}' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No students found.</td></tr>";
        }

        // Close connection properly
        if ($conn) {
            $conn->close();
        }
        ?>
    </table>

</body>

</html>