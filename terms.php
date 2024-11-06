<?php
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
    <title>Terms of Service</title>
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
            background: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
            margin: 39px;
            width: auto; /* Allows the width to adjust to the content */
            text-align: center;
            position: relative; /* Position relative for absolute positioning of the close button */
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border: none;
            background: #ff4d4d;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .close-btn:hover {
            background: #e03e3e;
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
        .member {
            margin-bottom: 10px;
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
        <button class="close-btn" onclick="window.location.href='inbox.php'">&times;</button>
        <h1>Terms of Service</h1>
        <p>Last updated: <?php echo date('F j, Y'); ?></p>
        <p>Welcome to HueMail!</p>
        <p>By using this project, you agree to these terms. If you don't agree, please don't use the project.</p>
        
        <h2>1. Use of Service</h2>
        <p>You agree to use this project for educational purposes only. Don't misuse the project in ways that could harm it or disrupt others.</p>

        <h2>2. Intellectual Property</h2>
        <p>All content is owned by Group 5, as part of the IT Major Elective 3 subject, and our instructor is Mr. Ryan Prudenciado. Please do not copy, modify, or distribute any content without permission.</p>

        <h2>3. Limitation of Liability</h2>
        <p>The project is provided "as is" without warranties. We don't guarantee it will be error-free or available at all times.</p>

        <h2>4. Changes to Terms</h2>
        <p>We may update these terms. Changes will be posted here with a new date. Continued use means you accept the updated terms.</p>

        <h2>5. Contact Us</h2>
        <p>For questions about these terms, please contact us at <a href="mailto:admin@huemail.com" style="color: #007bff;">admin@huemail.com</a>.</p>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Group 5. All rights reserved.</p>
    </footer>
</body>
</html>
