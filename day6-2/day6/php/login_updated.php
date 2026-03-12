<?php
/**
 * EXAMPLE: Updated login.php with Multi-Tenancy Support
 * 
 * Key improvements:
 * - Stores user_id in session
 * - Fixes SQL injection vulnerability
 * - Proper prepared statements
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

// Include database connection
require_once 'db_connect_updated.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    if (empty($_POST["username"]) || empty($_POST["password"])) {
        $login_error = "Username and password are required";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        // Validate input lengths
        if (strlen($username) > 255 || strlen($password) > 255) {
            $login_error = "Invalid username or password";
        } else {
            // Using prepared statement to prevent SQL injection
            $stmt = $user_db->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");

            if (!$stmt) {
                error_log("Database error: " . $user_db->error);
                $login_error = "Server error - please try again";
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $user_id = intval($row['id']);
                    $stored_password = $row['password'];

                    // Verify password
                    if (password_verify($password, $stored_password)) {
                        // CRITICAL: Store both username AND user_id in session
                        $_SESSION["loggedin"] = true;
                        $_SESSION["username"] = $username;
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["login_time"] = time();

                        // Log successful login
                        error_log("[Auth] User logged in: {$username} (ID: {$user_id}) from IP: " . $_SERVER['REMOTE_ADDR']);

                        // Redirect to dashboard
                        header("location: dashboard.php");
                        exit();
                    } else {
                        $login_error = "Incorrect password";
                        // Log failed attempt
                        error_log("[Auth] Failed login attempt for user: {$username} from IP: " . $_SERVER['REMOTE_ADDR']);
                    }
                } else {
                    $login_error = "Username not found";
                    // Log failed attempt
                    error_log("[Auth] Login attempt with non-existent user: {$username} from IP: " . $_SERVER['REMOTE_ADDR']);
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sari-Sari Store</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Dots&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Zen+Dots&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <h1>Login</h1>

        <?php if (isset($login_error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required maxlength="255">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required maxlength="255">
            </div>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>

</html>