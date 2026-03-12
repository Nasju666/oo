<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Dots&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Zen+Dots&display=swap" rel="stylesheet">
    <script>
        function showAlert(event) {
            event.preventDefault(); // Prevent actual form submission
            alert("Registration successful!");
            document.getElementById("registerForm").submit(); // Submit after alert
        }
    </script>
</head>

<body>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Database connection (replace with your credentials)
        $conn = new mysqli(
            "localhost",
            "root",
            "",
            "user_db"
        );
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $username = $_POST["username"];
        $email = $_POST["email"];
        $password = password_hash(
            $_POST["password"],
            PASSWORD_DEFAULT
        ); // Secure password hashing
        $sql = "INSERT INTO users (username, email, password)
VALUES ('$username', '$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Registration successful!');</script>";
            header("location: login.php");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        $conn->close();
    }
    ?>

    <div class="box-container">

        <div class="container">

            <div class="right-panel">
                <img src="llogo.png" alt="Logo" class="logo">
                <h1>Sign up</h1>

                <form id="registerForm" method="post" onsubmit="showAlert(event)" action="<?php echo
                    htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="button">Register</button>
                </form>

                <div class="signup">
                    <p>Already have an account? <a href="login.php">Sign in</a></p>
                </div>
            </div>

            <div class="left-panel">
                <h1>Create an Account</h1>
                <img src="bentong.png" alt="Illustration">
            </div>
        </div>

    </div>

    <footer>
    <p>©Sari-Sari store performance tracking system, 2025. All rights reserved.</p>
    </footer>

</body>

</html>