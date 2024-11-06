<?php
// privacy.php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy</title>
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
            position: relative;
            background: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
            margin: 39px;
            width: auto; /* Allows the width to adjust to the content */
            text-align: center;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background-color: #ff4d4d; /* Red background color */
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-btn:hover {
            background-color: #e60000; /* Darker red on hover */
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }
        h2 {
            color: #555;
        }
        p {
            color: #666;
            font-size: 18px;
        }
        footer {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="close-btn" onclick="window.location.href='inbox.php'">X</button>
        <h1>Privacy Policy</h1>
        <p>Last updated: <?php echo date('F j, Y'); ?></p>
        <p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
        
        <h2>1. Information We Collect</h2>
        <p>We collect information you provide directly, such as through forms. We also collect information about your usage of the project.</p>

        <h2>2. How We Use Your Information</h2>
        <p>We use your information to operate and improve our project, communicate with you, and for other purposes with your consent.</p>

        <h2>3. Data Protection</h2>
        <p>We take reasonable measures to protect your information from unauthorized access or disclosure.</p>

        <h2>4. Changes to Privacy Policy</h2>
        <p>We may update this policy. Changes will be posted here with a new date.</p>

        <h2>5. Contact Us</h2>
        <p>If you have questions about our privacy practices, please contact us at <a href="mailto:admin@huemail.com" style="color: #007bff;">admin@huemail.com</a>.</p>
    </div>
        
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Group 5. All rights reserved.</p>
    </footer>
</body>
</html>
