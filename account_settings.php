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
    $first_name = filter_var(trim($_POST['first_name']), FILTER_SANITIZE_STRING);
    $middle_name = filter_var(trim($_POST['middle_name']), FILTER_SANITIZE_STRING);
    $last_name = filter_var(trim($_POST['last_name']), FILTER_SANITIZE_STRING);
    $gender = filter_var(trim($_POST['gender']), FILTER_SANITIZE_STRING);
    $birthdate = filter_var(trim($_POST['birthdate']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

    // Calculate age
    $birthdate = new DateTime($birthdate);
    $today = new DateTime('today');
    $age = $birthdate->diff($today)->y;

    if (!$email) {
        $error_message = 'Invalid email format.';
    } elseif (empty($first_name) || empty($last_name) || empty($gender) || empty($birthdate)) {
        $error_message = 'Please fill out all required fields.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate->format('Y-m-d'))) { // Validate date format YYYY-MM-DD
        $error_message = 'Invalid birthdate format.';
    } elseif ($birthdate->format('Y-m-d') === $today->format('Y-m-d')) {
        $error_message = 'Birthdate cannot be today.';
    } elseif ($age < 13) {
        $error_message = 'You must be at least 13 years old to register.';
    } else {
        // Update user data
        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, gender = ?, birthdate = ?, email = ? WHERE id = ?');
        if ($stmt->execute([$first_name, $middle_name, $last_name, $gender, $birthdate->format('Y-m-d'), $email, $_SESSION['user_id']])) {
            $success_message = 'Account settings updated successfully!';
        } else {
            $error_message = 'Update failed. Please try again.';
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare('SELECT first_name, middle_name, last_name, gender, birthdate, email FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die('User not found.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
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
        input[type="date"],
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
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
        #caps-lock-warning {
            color: #ff4081;
            display: none; /* Hide by default */
        }
    </style>
    <script>
        // Function to detect CAPS LOCK
        function checkCapsLock(event) {
            const key = event.getModifierState('CapsLock');
            const warning = document.getElementById('caps-lock-warning');
            
            if (key) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }

        // Validation functions
        function validateFirstName() {
            const firstNameInput = document.getElementById('first_name');
            const firstNameError = document.getElementById('first-name-error');
            const firstNameValue = firstNameInput.value.trim();
            const firstNamePattern = /^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/;

            if (firstNameValue.length < 2 || !firstNamePattern.test(firstNameValue)) {
                firstNameError.style.display = 'block';
                firstNameInput.setCustomValidity('First name must be at least 2 characters long and only contain letters and single spaces between words.');
            } else {
                firstNameError.style.display = 'none';
                firstNameInput.setCustomValidity('');
            }
        }

        function validateMiddleName() {
            const middleNameInput = document.getElementById('middle_name');
            const middleNameError = document.getElementById('middle-name-error');
            const middleNameValue = middleNameInput.value.trim();
            const middleNamePattern = /^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/;

            if (middleNameValue.length < 2 || !middleNamePattern.test(middleNameValue)) {
                middleNameError.style.display = 'block';
                middleNameInput.setCustomValidity('Middle name must be at least 2 characters long and only contain letters and single spaces between words.');
            } else {
                middleNameError.style.display = 'none';
                middleNameInput.setCustomValidity('');
            }
        }

        function validateLastName() {
            const lastNameInput = document.getElementById('last_name');
            const lastNameError = document.getElementById('last-name-error');
            const lastNameValue = lastNameInput.value.trim();
            const lastNamePattern = /^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/;

            if (lastNameValue.length < 2 || !lastNamePattern.test(lastNameValue)) {
                lastNameError.style.display = 'block';
                lastNameInput.setCustomValidity('Last name must be at least 2 characters long and only contain letters and single spaces between words.');
            } else {
                lastNameError.style.display = 'none';
                lastNameInput.setCustomValidity('');
            }
        }

        function validateEmail() {
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email-error');
            const emailValue = emailInput.value.trim();
            const emailPattern = /^[a-zA-Z0-9._%+-]+@huemail\.com$/;

            if (!emailPattern.test(emailValue)) {
                emailError.style.display = 'block';
                emailInput.setCustomValidity('Email must be in the format of [your-email]@huemail.com');
            } else {
                emailError.style.display = 'none';
                emailInput.setCustomValidity('');
            }
        }
        
        function validateForm(event) {
            validateFirstName();
            validateMiddleName();
            validateLastName();
            validateEmail();
            if (document.querySelector('.error-message').style.display === 'block') {
                event.preventDefault();
            }
        }

        window.addEventListener('keydown', checkCapsLock);
    </script>
</head>
<body>
    <div class="container">
        <button class="close-btn" onclick="window.location.href='inbox.php';"><i class="fas fa-times"></i></button>
        <h1>Account Settings</h1>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <form action="account_settings.php" method="post" onsubmit="validateForm(event)">
            <div class="form-row">
                <div class="form-column">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" oninput="validateFirstName()" required>
                    <div id="first-name-error" class="error-message" style="display: none;"></div>
                </div>
                <div class="form-column">
                    <label for="middle_name">Middle Name:</label>
                    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>" oninput="validateMiddleName()">
                    <div id="middle-name-error" class="error-message" style="display: none;"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-column">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" oninput="validateLastName()" required>
                    <div id="last-name-error" class="error-message" style="display: none;"></div>
                </div>
                <div class="form-column">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled>Select gender</option>
                        <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-column">
                    <label for="birthdate">Birthdate:</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate']); ?>" required>
                </div>
                <div class="form-column email-wrapper">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" oninput="validateEmail()" required>
                    <div id="email-error" class="error-message" style="display: none;"></div>
                </div>
            </div>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>
