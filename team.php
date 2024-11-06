<?php
// team.php
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
    <title>Team Members</title>
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
            position: relative; /* Position relative to place the X button absolutely */
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 25px;
        }
        h2 {
            color: #555;
        }
        p {
            color: #666;
            font-size: 18px;
        }
        .members {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px; /* Space between columns */
        }
        .member {
            flex: 1 1 calc(50% - 20px); /* Each column takes up 50% of the width minus the gap */
            box-sizing: border-box;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
        }
        .close-btn:hover {
            background-color: #e03e3e;
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
        <button class="close-btn" onclick="window.location.href='inbox.php';">X</button>
        <h1>Meet Our Team</h1>
        <p>We are Group 5 and we have 8 dedicated members:</p>
        
        <div class="members">
            <div class="member">
                <h2>Member 1: Edig, John Louise G.</h2>
                <p>Front End.</p>
            </div>
            <div class="member">
                <h2>Member 2: Entera, Michael Angelo E.</h2>
                <p>Front End/Back End.</p>
            </div>
            <div class="member">
                <h2>Member 3: Bayubay, Christian Jay</h2>
                <p>QA/Tester.</p>
            </div>
            <div class="member">
                <h2>Member 4: Acosta, Alije</h2>
                <p>Documentation.</p>
            </div>
            <div class="member">
                <h2>Member 5: Duran, Mitch</h2>
                <p>Documentation.</p>
            </div>
            <div class="member">
                <h2>Member 6: Maramara, Riza Mae</h2>
                <p>Documentation.</p>
            </div>
            <div class="member">
                <h2>Member 7: Veral, Kirk Arby</h2>
                <p>Documentation.</p>
            </div>
            <div class="member">
                <h2>Member 8: Sestual, Jefred</h2>
                <p>Documentation.</p>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Group 5. All rights reserved.</p>
    </footer>
</body>
</html>
