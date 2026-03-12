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
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>


<body>
    <div class="nav-container">
        <nav>
            <div class="nav-header">
                <div class="nav-logo">
                    <a href="#" class="logow"><img src="ceclogs.png" alt="Logo" class="nav-logo"></a>
                    <a href="#" class="logow" id="logo-text">Student Portal</a>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="#">Home</a></li>
                <li><a href="index.php">Add Student</a></li>
                <li><a href="view_student.php">View Students</a></li>
                <li><a id="out" href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>

    <div class="home--container">
        <div class="home-container">
            <div class="left">

                <h1>Welcome, <?php echo $_SESSION["username"]; ?>!</h1>
                <p>Dive in, explore, and if you need help, we’ve got your back!<br> Let's make managing students
                    effortless
                    and even a little fun. 😎</p>

            </div>

            <div class="right">
                <img src="ge.png" alt="">
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; Developed by Amanence</p>
    </footer>

</body>

</html>