<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
$host = 'localhost';
$db   = 'HueMail';
$user = 'root';  // Change to your MySQL username
$pass = '';      // Change to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch user's profile details
$stmt = $pdo->prepare("SELECT first_name, profile_pic FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Default profile picture if not set
$profile_pic = $user['profile_pic'] ?: 'images/pp.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - HueMail</title>
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
            background: rgba(0, 0, 0, 0.7);
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
            background-color: #555;
            color: #fff;
        }
        .container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 25px;
            max-width: 650px;
            width: 100%;
            text-align: center;
            margin: 100px auto;
        }
        h1 {
            margin-top: 20px;
            color: #333;
            font-size: 24px;
        }
        .profile-pic {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            margin-top: 20px;
            border: 5px solid #00a400; /* Optional: Add a green border for emphasis */
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
            background-color: green;
        }
        footer {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    text-align: center;
    position: fixed;
    bottom: 0;
    width: 100%;
}

        p {
            color: #666;
            font-size: 18px;
        }

        button.skip {
    background-color: #ff4d4d; /* Red color */
    color: #fff;
}

button.skip:hover {
    background-color: #cc0000; /* Darker red for hover effect */
}

    </style>
</head>
<body>
    <div class="navbar">
        <a href="welcome.php" class="current">Home</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="container">
        <img src="<?= $profile_pic ?>" alt="Profile Picture" class="profile-pic">
        <h1>You have successfully registered!</h1>
        <p>Next step, add a profile picture so that the other users can recognize you and personalize your experience on HueMail.</p>
        <button onclick="window.location.href='add_profile.php';">Add Profile</button>
        <button class="skip" onclick="window.location.href='inbox.php';">Skip</button>
        </div>
</body>
</html>
