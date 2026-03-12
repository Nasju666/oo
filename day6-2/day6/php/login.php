<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Dots&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Zen+Dots&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php
    session_start(); // Start session for login
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Database connection
        $user_db = new mysqli("localhost", "root", "", "user_db");
        if ($user_db->connect_error) {
            die("Connection failed: " . $user_db->connect_error);
        }

        $username = $_POST["username"];
        $password = $_POST["password"];

        // Use prepared statement to prevent SQL injection
        $stmt = $user_db->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                // Store verified user_id from database
                $_SESSION["loggedin"] = true;
                $_SESSION["username"] = $username;
                $_SESSION["user_id"] = $row["id"];
                echo "<script>alert('Login successful! Redirecting...'); window.location.href = 'dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Incorrect password.');</script>";
            }
        } else {
            echo "<script>alert('Username not found!');</script>";
        }
        $stmt->close();
        $user_db->close();
    }
    ?>



    <div class="box-container">

        <div class="container">

            <div class="right-panel">
                <img src="../lllogo.png" alt="Logo" class="logo">
                <h1>Sign In</h1>

                <form method="post" action="<?php echo
                    htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="button">Login</button>
                </form>

                <div class="signup">
                    <p>Don't have an account? <a href="register.php">Sign up</a></p>
                </div>
            </div>

            <div class="left-panel">
                <h1>Welcome to</h1>
                <img src="../bentong.png" alt="">
                <h2>Sari Sari Store Performance Tracking System</h2>
            </div>
        </div>

    </div>

    <footer>
        <p>©Sari-Sari store performance tracking system, 2025. All rights reserved.</p>
    </footer>
</body>

</html>