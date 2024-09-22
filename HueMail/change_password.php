<?php
session_start();

// Database connection
$host = 'localhost';
$db   = 'HueMail';
$user = 'root';  // Change to your MySQL username
$pass = '';      // Change to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim inputs
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Fetch current password hash from the database
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('User not found.');
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error_message = 'Current password is incorrect.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'New password must be at least 8 characters long.';
    } else {
        // Hash new password
        $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password in the database
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        if ($stmt->execute([$new_password_hash, $_SESSION['user_id']])) {
            $success_message = 'Password changed successfully!';
        } else {
            $error_message = 'Failed to change password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/huemail.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            position: relative; /* Position relative to place the close button */
            background: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 25px;
            max-width: 100rem; /* Adjust width as needed */
            width: 80%;
            text-align: center;
            margin: auto; /* Center the container */
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff4d4d;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #fff;
            font-size: 18px;
            transition: background 0.3s;
        }
        .close-btn:hover {
            background: #ff2d2d;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-column {
            flex: 1;
            min-width: calc(50% - 10px); /* Two columns with space in between */
            margin-right: 10px;
        }
        .form-column:last-child {
            margin-right: 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            text-align: left;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input[type="password"] {
            padding-right: 40px; /* Space for the eye icon */
        }
        .password-wrapper .eye-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            display: none; /* Hide by default */
        }
        .password-wrapper input[type="password"].has-text ~ .eye-icon {
            display: block; /* Show when input has text */
        }
        .email-wrapper {
            position: relative;
        }
        .email-wrapper input {
            padding-right: 120px; /* Space for the suffix */
        }
        .email-wrapper .suffix {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
        }
        button {
            width: 20%;
            padding: 12px;
            background-color: #00a400;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #3b6e22;
        }
        .error-message {
            color: #ff4081;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-message {
            color: #28a745;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .login-link-container {
            margin-top: 20px; /* Increased margin to provide more space above */
            font-size: 18px; /* Increased font size for better visibility */
            color: #666;
        }
        .login-link {
            color: #007bff;
            font-size: 20px; /* Larger font size for the link */
            font-weight: bold; /* Bold font for emphasis */
            text-decoration: none;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        p {
            color: #666;
            font-size: 18px;
        }
        .auth-links .login-button {
            display: inline-block;
            background-color: #1877f2;
            color: white;
            text-decoration: none;
            padding: 5px 15px; /* Padding for button size */
            border-radius: 8px; /* Rounded corners */
            font-size: 18px;
            text-align: center;
            border: none;
            transition: background-color 0.3s ease; /* Smooth background color transition */
        }

        .auth-links .login-button:hover {
            background-color: #45a049; /* Darker green on hover */
        }
        .caps-lock-warning {
            color: red; 
            display: none;
        }
    </style>
    <script>
        // Function to detect CAPS LOCK
        function checkCapsLock(event) {
            const key = event.getModifierState('CapsLock');
            const warning = event.target.parentElement.querySelector('.caps-lock-warning');
            if (key) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }

        // Function to toggle password visibility
        function togglePasswordVisibility(event) {
            const passwordField = event.target.previousElementSibling;
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                event.target.classList.remove('fa-eye-slash');
                event.target.classList.add('fa-eye');
            } else {
                passwordField.type = 'password';
                event.target.classList.remove('fa-eye');
                event.target.classList.add('fa-eye-slash');
            }
        }

        // Function to show/hide eye icon based on input
        function handlePasswordInput(event) {
            const eyeIcon = event.target.nextElementSibling;
            if (event.target.value.length > 0) {
                eyeIcon.style.display = 'block';
            } else {
                eyeIcon.style.display = 'none';
            }
        }

        // Attach event listeners when DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function () {
            const passwordFields = document.querySelectorAll('.password-wrapper input[type="password"]');
            const eyeIcons = document.querySelectorAll('.eye-icon');

            passwordFields.forEach(passwordField => {
                passwordField.addEventListener('input', handlePasswordInput);
                passwordField.addEventListener('keydown', checkCapsLock);
                passwordField.addEventListener('keyup', checkCapsLock);
            });

            eyeIcons.forEach(eyeIcon => {
                eyeIcon.addEventListener('click', togglePasswordVisibility);
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>
        <button class="close-btn" onclick="window.location.href='inbox.php';"><i class="fas fa-times"></i></button>

        <?php if ($error_message): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <form method="POST" action="update_password.php">
            <div class="password-wrapper">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password" required>
                <i class="fa fa-eye-slash eye-icon"></i>
                <div class="caps-lock-warning">Caps Lock is ON</div>
            </div>
            <div class="password-wrapper">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required>
                <i class="fa fa-eye-slash eye-icon"></i>
                <div class="caps-lock-warning">Caps Lock is ON.</div>
            </div>
            <div class="password-wrapper">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <i class="fa fa-eye-slash eye-icon"></i>
                <div class="caps-lock-warning">Caps Lock is ON.</div>
            </div>
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>
</html>
