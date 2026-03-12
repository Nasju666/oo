<?php
session_start();
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"]
    !== true
) {
    header("location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        function showAlert(event) {
            event.preventDefault();
            alert("Student added successfully!");
            document.getElementById("registerForm").submit();
        }
    </script>
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

    <div class="box-container">

        <div class="container">

            <div class="left-panel">
                <img src="cec.jpg" alt="Illustration">
                <h2>Connect with the Dragons.</h2>
                <p>Student dashboard.</p>
            </div>


            <div class="right-panel">
                <img src="ceclogs.png" alt="Logo" class="logo">
                <h1>Student Portal</h1>
                <h3>Add a Student</h3>

                <form action="add_student.php" method="POST" id="registerForm" onsubmit="showAlert(event)">
                    <input type="text" id="f_name" name="f_name" placeholder="First Name" required>
                    <input type="text" id="m_name" name="m_name" placeholder="Middle Name" required>
                    <input type="text" id="l_name" name="l_name" placeholder="Last Name" required>
                    <input type="text" id="address" name="address" placeholder="Address" required>
                    <input type="email" id="email_address" name="email_address" placeholder="Email" required>
                    <input type="date" id="date_enrolled" name="date_enrolled" required>
                    <button type="submit">Add Student</button>
                </form>

            </div>
        </div>

    </div>

    <footer>
        <p>&copy; Developed by Amanence</p>
    </footer>

</body>

</html>