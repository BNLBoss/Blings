<?php
session_start();
require 'db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Store user info in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        switch ($user['role']) {
            case 'citizen':
                header('Location: welcome.php'); // Citizens go to feedback and voting
                break;
            case 'official':
                header('Location: government_dashboard.php'); // Officials go to poll creation
                break;
            case 'moderator':
                header('Location: moderation_dashboard.php'); // Moderators go to a moderation dashboard
                break;
            default:
                header('Location: login.php'); // Fallback in case of an unknown role
        }
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Citizen Voice</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        /* Main container styles */
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --primary-light: #a2c4f4;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --border-color: #bdc3c7;
        }

        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: var(--light);
            color: var(--dark);
        }

        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-body {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            transform: scale(1);
            transition: transform 0.3s ease-in-out;
        }

        .form-body:hover {
            transform: scale(1.02);
        }

        h2 {
            color: var(--dark);
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .highlight {
            color: var(--primary);
        }

        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 12px 0;
            border-radius: 6px;
            border: 2px solid var(--border-color);
            font-size: 16px;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }

        .form-container input[type="email"]:focus,
        .form-container input[type="password"]:focus {
            border-color: var(--primary);
            background-color: rgba(0, 0, 0, 0.05);
        }

        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container input {
            padding-right: 40px;
        }

        .password-container i {
            position: absolute;
            right: 10px;
            cursor: pointer;
            color: var(--primary);
            font-size: 18px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: var(--primary);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-4px);
        }

        .form-error {
            color: red;
            margin-top: 10px;
        }

        .form-container p {
            margin-top: 15px;
            font-size: 14px;
        }

        .form-container a {
            color: var(--primary);
            text-decoration: none;
            transition: text-decoration 0.3s ease;
        }

        .form-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="form-body">
        <div class="form-container">
            <h2>Login to Your <br> <span class="highlight">Citizen Voice</span> Account</h2>
            <form action="login.php" method="post">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Email" required>

                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <i class="fa fa-eye-slash" id="togglePassword"></i>
                </div>

                <button type="submit" class="btn-submit">Login</button>
            </form>

            <div class="form-error">
                <!-- Error message will be displayed here -->
                <?php echo isset($error) ? $error : ''; ?>
            </div>

            <p>
                Don't have an account? <a href="register.php">Register</a>
            </p>
            <p><a href="../index.html">Home</a></p>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById("togglePassword").addEventListener("click", function () {
        const passwordField = document.getElementById("password");
        const icon = this;

        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
    });
</script>

</body>
</html>
