<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
    die("Database connection failed: " . $e->getMessage());
}

// Get email ID and folder from query string
$email_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$folder = isset($_GET['folder']) ? htmlspecialchars($_GET['folder']) : 'inbox';

// Ensure the email ID is valid
if ($email_id <= 0) {
    die("Invalid email ID.");
}

// Check if the email belongs to the logged-in user
$stmt = $pdo->prepare('SELECT user_id FROM emails WHERE id = ?');
$stmt->execute([$email_id]);
$email = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$email || $email['user_id'] != $_SESSION['user_id']) {
    die("Email not found or you do not have permission to delete it.");
}

// Delete the email
$stmt = $pdo->prepare('DELETE FROM emails WHERE id = ?');
$stmt->execute([$email_id]);

// Redirect to the appropriate folder after deletion
header('Location: inbox.php?folder=' . urlencode($folder));
exit;
?>
