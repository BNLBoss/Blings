<?php
require 'db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "User with this email already exists!";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $password_hash]);

        echo "Registration successful!";
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .main-container {
            padding: 0px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-body {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 0px;
            width: 400px;
            transition: transform 0.3s ease;
        }
        .form-container:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }
        .input-group {
            position: relative;
            margin-bottom: 15px;
        }
        .input-group input {
            width: 100%;
            padding: 0px 50px 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            transition: border-color 0.3s ease;
        }
        .input-group input:focus {
            border-color: #007bff;
            outline: none;
        }
        .icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
        }
        .password-requirements {
            font-size: 0.9em;
            color: #888;
        }
        .error-body {
            color: red;
            margin-top: 5px;
        }
        .requirement-char {
            display: inline-block;
            width: 15px;
            text-align: center;
        }
        .valid {
            color: green;
        }
        .invalid {
            color: red;
        }
        .btn-submit {
            width: 100%;
            padding: 4px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 2px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        p a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        p a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main-container bg-light center-items">
        <div class="form-body br-medium">
            <div class="form-container">
                <h2>Create A<span class="highlight"> Citizen Voice </span> <br> Account </h2>
                <form action="register.php" method="post">
                    <div class="input-group">
                        <input type="text" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                        <span class="icon">👤</span>
                    </div>
                    <div class="input-group">
                        <input type="text" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                        <span class="icon">👤</span>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        <span class="icon">📧</span>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" id="password" placeholder="Password" required oninput="checkPassword(this.value)">
                        <span class="icon">🔒</span>
                        <div class="password-requirements">
                            <p>Requirements:</p>
                            <div>
                                <span id="length" class="requirement-char">✖</span> 8 chars
                                <span id="uppercase" class="requirement-char">✖</span> 1 upper
                                <span id="number" class="requirement-char">✖</span> 1 number
                                <span id="special-char" class="requirement-char">✖</span> 1 special
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Register</button>
                </form>

                <!-- Error Display -->
                <?php if (!empty($errors)): ?>
                    <div class="error-body">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Links -->
                <p>Already have an account? <a href="login.php">Login</a></p>
                <p><a href="../index.html">Home</a></p>
            </div>
        </div>
    </div>

    <script>
        function checkPassword(password) {
            document.getElementById("length").textContent = password.length >= 8 ? '✔' : '✖';
            document.getElementById("uppercase").textContent = /[A-Z]/.test(password) ? '✔' : '✖';
            document.getElementById("number").textContent = /[0-9]/.test(password) ? '✔' : '✖';
            document.getElementById("special-char").textContent = /[!@#$%^&*(),.?":{}|<>]/.test(password) ? '✔' : '✖';
        }
    </script>
</body>
</html>
