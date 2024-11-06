<?php
session_start();

// Redirect to inbox if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: inbox.php');
    exit;
}

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

// Default values for form inputs
$first_name = $middle_name = $last_name = $gender = $birthdate = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim inputs
    $first_name = filter_var(trim($_POST['first_name']), FILTER_SANITIZE_STRING);
    $middle_name = filter_var(trim($_POST['middle_name']), FILTER_SANITIZE_STRING);
    $last_name = filter_var(trim($_POST['last_name']), FILTER_SANITIZE_STRING);
    $gender = filter_var(trim($_POST['gender']), FILTER_SANITIZE_STRING);
    $birthdate = filter_var(trim($_POST['birthdate']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Calculate age
    $birthdate = new DateTime($birthdate);
    $today = new DateTime('today');
    $age = $birthdate->diff($today)->y;

    // Default profile picture based on gender
    $profile_pic = $gender === 'male' ? 'images/male.png' : ($gender === 'female' ? 'images/female.png' : 'images/pp.png');

    if (!$email) {
        $error_message = 'Invalid email format.';
    } elseif (empty($first_name) || empty($last_name) || empty($gender) || empty($birthdate) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill out all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate->format('Y-m-d'))) { // Validate date format YYYY-MM-DD
        $error_message = 'Invalid birthdate format.';
    } elseif ($birthdate->format('Y-m-d') === $today->format('Y-m-d')) {
        $error_message = 'Birthdate cannot be today.';
    } elseif ($age < 13) {
        $error_message = 'Must be at least 13 years old to register.';
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{7,}$/', $password)) { // Validate password strength
        $error_message = 'Password must be at least 7 characters long, start with an uppercase letter, and include lowercase letters, numbers, and special characters.';
    } else {
        // Check if the email is already registered
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_message = 'Email is already registered.';
        } else {
            // Insert new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (first_name, middle_name, last_name, gender, birthdate, email, password_hash, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$first_name, $middle_name, $last_name, $gender, $birthdate->format('Y-m-d'), $email, $password_hash, $profile_pic])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['email'] = $email; // Set session email
                $success_message = 'Registration successful! Redirecting to login...';
                header('refresh:2;url=welcome.php');
                exit;
            } else {
                $error_message = 'Registration failed. Please try again.';
            }
        }
    }
}
$page = 'signup'; // Set the current page identifier
?>



    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sign up for HueMail</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: url('images/pp.jpg') no-repeat center center fixed;
                background-size: cover;
                margin: 0;
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }
            .navbar {
                background: rgba(0, 0, 0, 0.7); /* Dark background for navbar */
                color: white;
                padding: 10px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: sticky;
                top: 0;
                z-index: 1000;
            }
            .navbar a {
                color: white;
                text-decoration: none;
                margin: 0 15px;
                font-size: 18px;
                padding: 10px;
                border-radius: 5px;
            }
            .navbar a:hover, .navbar a.current {
                background-color: #555; /* Darker background on hover and for the current page */
                color: #fff; /* Ensure text color is white for better readability */
            }
            .container {
                background: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
                border: 5px solid white; /* Light gray border */
                border-radius: 30px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                padding: 25px;
                max-width: 40rem; /* Adjust width as needed */
                width: 80%;
                text-align: center;
                margin: auto; /* Center the container */
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
    margin-bottom: 20px; /* Adjust as needed */
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
    transform: translateY(-20%);
    cursor: pointer;
    color: #666;
    display: none; /* Hide by default */
}
.password-wrapper input[type="password"].has-text ~ .eye-icon {
    display: block; /* Show when input has text */
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
            width: 15%;
            padding: 10px;
            background-color: #00a400;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 20px;
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
            footer {
                background: rgba(0, 0, 0, 0.7);
                color: white;
                text-align: center;
                position: sticky;
                bottom: 0;
                width: 100%;
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
    position: absolute;
    left: 0; /* Align to the left of the parent container */
    top: 75px; /* Adjust vertical positioning as needed */
    color: red;
    font-size: 15px;
    display: none; /* Hide by default */
}
            
        </style>
        <script>
        function handlePasswordInput(event) {
    const fieldId = event.target.id;
    const eyeIcon = event.target.nextElementSibling;
    const capsLockWarning = document.getElementById(fieldId + '-caps-lock-warning');

    if (event.target.value.length > 0) {
        eyeIcon.style.display = 'block';
    } else {
        eyeIcon.style.display = 'none';
    }

    checkCapsLock(event, fieldId);
}

function checkCapsLock(event, fieldId) {
    const key = event.getModifierState('CapsLock');
    const warning = document.getElementById(fieldId + '-caps-lock-warning');

    if (key) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

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

        </script>
    </head>
    <body>
        <!-- Navbar -->
        <div class="navbar">
            <div class="nav-links">
                <a href="home.php" class="<?php echo $page == 'home' ? 'current' : ''; ?>">Home</a>
             </div>
            <div class="auth-links">
            <a href="login.php" class="login-button <?php echo $page == 'login' ? 'current' : ''; ?>">Log In</a>
            <a href="register.php" class="<?php echo $page == 'signup' ? 'current' : ''; ?>">Sign Up</a>

            </div>
        </div>

        <div class="container">
            <h1>Create a new account</h1>
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <form action="register.php" method="POST">
            <div class="form-row">
        <div class="form-column">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required pattern="^[a-zA-Z]+(?:\s[a-zA-Z]+)*$" title="Must be at least 2 characters long and only contain letters." oninput="validateFirstName()">
    <div id="first-name-error" style="color: red; display: none;">Must be at least 2 characters long and only contain letters.</div>
    </div>
    <div class="form-column">
                        <label for="middle_name">Middle Name: (Optional)</label>
                        <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>" pattern="^[a-zA-Z]+(?:\s[a-zA-Z]+)*$" title="Must be at least 2 characters long and only contain letters." oninput="validateMiddleName()">
                        <div id="middle-name-error" style="color: red; display: none;">Must be at least 2 characters long and only contain letters.</div>
                    </div>
                </div>
    <div class="form-row">
        <div class="form-column">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($middle_name); ?>" required pattern="^[a-zA-Z]+(?:\s[a-zA-Z]+)*$" title="Must be at least 2 characters long and only contain letters." oninput="validateLastName()">
    <div id="last-name-error" style="color: red; display: none;">Must be at least 2 characters long and only contain letters.</div>
    </div>
        <div class="form-column">
            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
                <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo $gender === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column">
            <label for="birthdate">Birthdate:</label>
            <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" required oninput="validateAge()">
            <div id="age-error" style="color: red; display: none;">Must be at least 13 years old to register.</div>
        </div>
        <div class="form-column email-wrapper">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required oninput="validateEmail()">
            <div id="email-error" style="color: red; display: none;">Email must end with @huemail.com.</div>
        </div>
    </div>

    <script>
function validateFirstName() {
    const firstNameInput = document.getElementById('first_name');
    const firstNameError = document.getElementById('first-name-error');
    const firstNameValue = firstNameInput.value.trim();

    // Regular expression to ensure the first name doesn't start or end with a space
    // and allows only single spaces between words
    const firstNamePattern = /^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/;

    if (firstNameValue.length < 2 || !firstNamePattern.test(firstNameValue)) {
        firstNameError.style.display = 'block';
        firstNameInput.setCustomValidity('Must be at least 2 characters long and only contain letters.');
    } else {
        firstNameError.style.display = 'none';
        firstNameInput.setCustomValidity('');
    }
}

function validateMiddleName() {
    const middleNameInput = document.getElementById('middle_name');
    const middleNameError = document.getElementById('middle-name-error');
    const middleNameValue = middleNameInput.value.trim();

    // Regular expression to ensure the middle name doesn't start or end with a space
    // and allows only single spaces between words
    const middleNamePattern = /^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/;

    if (middleNameValue.length < 2 || !middleNamePattern.test(middleNameValue)) {
        middleNameError.style.display = 'block';
        middleNameInput.setCustomValidity('Must be at least 2 characters long and only contain letters.');
    } else {
        middleNameError.style.display = 'none';
        middleNameInput.setCustomValidity('');
    }
}

function validateLastName() {
    const lastNameInput = document.getElementById('last_name');
    const lastNameError = document.getElementById('last-name-error');
    const lastNameValue = lastNameInput.value.trim();

    // Regular expression to ensure the last name doesn't start or end with a space
    // and allows only single spaces between words
    const lastNamePattern = /^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/;

    if (lastNameValue.length < 2 || !lastNamePattern.test(lastNameValue)) {
        lastNameError.style.display = 'block';
        lastNameInput.setCustomValidity('Must be at least 2 characters long and only contain letters.');
    } else {
        lastNameError.style.display = 'none';
        lastNameInput.setCustomValidity('');
    }
}

        function validateEmail() {
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email-error');
            const emailPattern = /^[a-zA-Z0-9._%+-]+@huemail\.com$/;

            if (!emailPattern.test(emailInput.value)) {
                emailError.style.display = 'block';
                emailInput.setCustomValidity('Email must end with @huemail.com.');
            } else {
                emailError.style.display = 'none';
                emailInput.setCustomValidity('');
            }
        }


        function validateAge() {
            const birthdateInput = document.getElementById('birthdate');
            const ageError = document.getElementById('age-error');
            const birthdate = new Date(birthdateInput.value);
            const today = new Date();
            const age = today.getFullYear() - birthdate.getFullYear();
            const monthDifference = today.getMonth() - birthdate.getMonth();

            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }

            if (age < 13) {
                ageError.style.display = 'block';
                birthdateInput.setCustomValidity('Must be at least 13 years old.');
            } else {
                ageError.style.display = 'none';
                birthdateInput.setCustomValidity('');
            }
        }
    </script>
    <script>
function checkCapsLock(event, fieldId) {
    const key = event.getModifierState('CapsLock');
    const warning = document.getElementById(fieldId + '-caps-lock-warning');

    if (key) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}


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

        function handlePasswordInput(event) {
            const eyeIcon = event.target.nextElementSibling;
            if (event.target.value.length > 0) {
                eyeIcon.style.display = 'block';
            } else {
                eyeIcon.style.display = 'none';
            }
        }
        
    </script>

    <div class="form-row">
    <div class="form-column password-wrapper">
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{7,}" title="Password must be at least 7 characters long, start with an uppercase letter, and include lowercase letters, numbers, and special characters." oninput="handlePasswordInput(event)" onkeydown="checkCapsLock(event, 'password')">
    <i class="fa fa-eye-slash eye-icon" onclick="togglePasswordVisibility(event)"></i>
    <div id="password-caps-lock-warning" class="caps-lock-warning">Caps Lock is ON</div>
</div>

<div class="form-column password-wrapper">
    <label for="confirm_password">Confirm Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{7,}" title="Passwords must match." oninput="handlePasswordInput(event)" onkeydown="checkCapsLock(event, 'confirm_password')">
    <i class="fa fa-eye-slash eye-icon" onclick="togglePasswordVisibility(event)"></i>
    <div id="confirm_password-caps-lock-warning" class="caps-lock-warning">Caps Lock is ON</div>
</div>
    </div>
    <br>
                <button type="submit">Sign Up</button>
                <div class="login-link-container">
                    <a href="login.php" class="login-link">Already have an account?</a>
                </div>
            </form>
        </div>
                
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Group 5. All rights reserved.</p>
        </footer>

    </body>
    </html>
