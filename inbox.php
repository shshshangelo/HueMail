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

// Define folder as 'inbox'
$folder = 'inbox';

// Get folder from query string or default to 'inbox'
$folder = isset($_GET['folder']) ? $_GET['folder'] : 'inbox';

// Define valid folders
$valid_folders = ['inbox', 'unread', 'draft', 'sent', 'archive', 'trash', 'spam', 'starred'];
if (!in_array($folder, $valid_folders)) {
    $folder = 'inbox'; // Default to 'inbox' if the folder is not valid
}

// Fetch emails for the logged-in user based on folder
$user_id = $_SESSION['user_id'];

// Handle "starred" folder differently
if ($folder === 'starred') {
    // Fetch starred emails
    $stmt = $pdo->prepare('
        SELECT e.id, e.sender, e.recipient, e.subject, e.body, e.status, e.created_at, u.email AS sender_email
        FROM emails e
        JOIN starred_emails s ON e.id = s.email_id
        JOIN users u ON e.user_id = u.id
        WHERE s.user_id = ?
        ORDER BY e.created_at DESC
    ');
    $stmt->execute([$user_id]);
} else {
    // Fetch emails for other folders
    $stmt = $pdo->prepare('
        SELECT e.id, e.sender, e.recipient, e.subject, e.body, e.status, e.created_at, u.email AS sender_email
        FROM emails e
        JOIN users u ON e.user_id = u.id
        WHERE e.user_id = ? AND e.status = ?
        ORDER BY e.created_at DESC
    ');
    $stmt->execute([$user_id, $folder]);
}
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Default profile picture path
$default_profile_pic = 'images/pp.png'; // Ensure this matches the default in add_profile.php
$profile_pic_path = $user['profile_pic'] ?: $default_profile_pic;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title><?php echo ucfirst($folder); ?> - HueMail</title>
    <style>
        /* Your existing CSS */
        body {
            font-family: 'Roboto', sans-serif;
            background: url('images/huemail.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
        }

        .side-panel {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: 215px;
            max-width: 100%;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }

        .side-panel .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .side-panel .header h1 {
            margin: 0;
            font-size: 1.5em;
        }

        .side-panel .navigation a {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            text-decoration: none;
            color: #1a73e8; /* Default link color */
            font-weight: 500;
            font-size: 25px; /* Increased font size */
        }

        .side-panel .navigation a i {
            margin-right: 8px; /* Space between icon and text */
            font-size: 1.2em;   /* Adjust icon size */
        }

        .side-panel .navigation a.active {
            font-weight: bold;
            border-bottom: 2px solid black;
        }

        .side-panel .logout {
            background-color: #e53935; /* Red background color */
            color: #fff !important;    /* White text color with !important */
            border: none;
            border-radius: 10px;
            padding: 2px 2px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1em;
            margin-top: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .side-panel .logout:hover {
            background-color: #b71c1c; /* Darker red background color on hover */
        }

        .side-panel .logout i {
            margin-right: 1px; /* Space between icon and text */
        }

        .main-content {
            margin-left: 250px; /* Adjust based on the width of the side panel */
            padding: 5px;
            flex: 1;
            max-width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 0;
            position: relative; /* Added for dropdown positioning */
        }

        .navbar .search-bar {
            flex: 1;
            margin: 0 20px;
        }

        .navbar .search-bar input {
            width: 98%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .navbar .profile {
            display: flex;
            align-items: center;
            position: relative; /* Added for dropdown positioning */
        }

        .navbar .profile img { /* Profile picture styling */
            border-radius: 50%;
            width: 70px;
            height: 70px;
            margin-right: 20px;
            cursor: pointer;
            border: 4px solid #1a73e8; /* Border color and width */
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            width: 200px;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }

        .dropdown-menu a:hover {
            background-color: #f5f5f5;
        }

        .show {
            display: block;
        }

/* Style the email table */
table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.9); /* Matches the modal background */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    overflow: hidden; /* Ensures rounded corners are visible */
}

th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

thead {
    background-color: #007bff;
    color: white;
}

th {
    font-weight: bold;
}

tbody tr:hover {
    background-color: #f5f5f5;
}

.email-subject {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.fas {
    margin-right: 5px;
}


        .email-body {
            color: #5f6368;
            font-size: 14px;
        }

        .no-emails-message {
            text-align: center;
            font-size: 1.2em;
            color: #555;
            margin-top: 20px;
        }

        .search-container {
            position: relative;
            width: 100%; /* Adjusted width to make it slightly larger */
            max-width: 500px; /* Optional: Set a max-width for better control on larger screens */
            margin: 0 auto; /* Center the search bar */
        }

        .search-container i {
            position: absolute;
            top: 50%;
            left: 15px; /* Adjusted for better spacing from the left edge */
            transform: translateY(-50%);
            font-size: 18px; /* Slightly larger icon */
            color: black; /* Icon color */
        }

        .search-container input {
            width: 100%;
            padding: 10px 20px 10px 40px; /* Adjusted padding for better balance */
            border: 2px solid black; /* Thinner border for a more modern look */
            border-radius: 8px; /* Increased border-radius for rounded corners */
            font-size: 16px; /* Larger font size for better readability */
            box-sizing: border-box; /* Ensure padding and border are included in the total width */
        }

        
/* Basic Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial', sans-serif;
    background: url('images/huemail.jpg') no-repeat center center fixed;
    background-size: cover;
    color: #333;
    line-height: 1.6;
    margin: 0;
}

/* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Dark overlay background */
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    position: relative;
    background: rgba(255, 255, 255, 0.9); /* Slightly transparent white background for the modal */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    padding: 20px;
    width: 100%;
    max-width: 800px;
    box-sizing: border-box;
}

.modal-header {
    margin-bottom: 20px;
}

.modal-header h2 {
    color: #444;
    font-size: 24px;
    font-weight: bold;
}

.close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ff4d4d;
    border: none;
    border-radius: 50%;
    width: 35px; /* Increased size for better visibility */
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #fff;
    font-size: 20px; /* Increased font size for better visibility */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Added shadow for a more 3D effect */
    transition: background 0.3s, transform 0.3s;
}

.close:hover {
    background: #ff2d2d;
    transform: scale(1.1); /* Slightly enlarges the button on hover */
}

.close:active {
    transform: scale(0.95); /* Slightly shrinks the button when clicked */
}

.close i {
    font-size: 20px; /* Adjusted icon size */
}

.modal-body form {
    display: flex;
    flex-direction: column;
}

.modal-body label {
    font-weight: bold;
    margin-bottom: 5px;
}

.modal-body input[type="email"],
.modal-body input[type="text"],
.modal-body textarea,
.modal-body input[type="file"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.modal-body input[type="email"]:focus,
.modal-body input[type="text"]:focus,
.modal-body textarea:focus,
.modal-body input[type="file"]:focus {
    border-color: #007bff;
    outline: none;
}

.modal-body textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    resize: vertical;
}

.modal-body button,
.modal-body .btn {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    margin-right: 10px;
    transition: background-color 0.3s;
}

.modal-body button:hover,
.modal-body .btn:hover {
    background-color: #0056b3;
}

.modal-body .btn-secondary {
    background-color: #6c757d;
}

.modal-body .btn-secondary:hover {
    background-color: #5a6268;
}

.modal-body .error-message {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
}

.modal-body .cc-bcc-toggle {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.modal-body .cc-bcc-toggle label {
    cursor: pointer;
    margin-right: 10px;
    color: #007bff;
    text-decoration: underline;
}

.modal-body .cc-bcc-fields {
    display: none;
    margin-bottom: 15px;
}

.modal-body .cc-bcc-fields input {
    margin-bottom: 10px;
}

.modal-body .toolbar {
    display: flex;
    margin-bottom: 10px; /* Adds space between toolbar and editor */
    align-items: center;
}

.modal-body .toolbar button {
    margin-right: 5px;
    padding: 5px 10px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    background-color: #007bff;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.3s;
}

.modal-body .toolbar button:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

.modal-body .toolbar .btn-trash {
    background-color: #ff4d4d;
}

.modal-body .toolbar .btn-trash:hover {
    background-color: #ff2d2d;
}
    </style>
</head>
<body>
<div class="side-panel">
    <div class="header">
        <h1>Inbox</h1>
    </div>
    <div class="navigation">
        <a href="#" id="compose-button"><i class="fas fa-pencil-alt"></i> Compose</a>

        <a href="inbox.php" class="active">
            <i class="fas fa-inbox"></i> Inbox
        </a>
                
        <a href="starred.php" class="<?php echo $folder === 'starred' ? 'active' : ''; ?>">
            <i class="fas fa-star""></i> Starred
        </a>

        <a href="unread.php" class="<?php echo $folder === 'unread' ? 'active' : ''; ?>">
            <i class="fas fa-envelope-open-text"></i> Unread
        </a>
        
        <a href="sent.php" class="<?php echo $folder === 'sent' ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i> Sent
        </a>

        <a href="draft.php" class="<?php echo $folder === 'draft' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Drafts
        </a>


        <a href="archive.php" class="<?php echo $folder === 'archive' ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i> Archive
        </a>

        <a href="spam.php" class="<?php echo $folder === 'spam' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle""></i> Spam
        </a>

        <a href="trash.php" class="<?php echo $folder === 'trash' ? 'active' : ''; ?>">
            <i class="fas fa-trash"></i> Trash
        </a>
        

        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="main-content">
    <div class="navbar">
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="search" placeholder="Search emails...">
        </div>

        <div class="profile">
            <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="Profile Picture">
            <div class="dropdown-menu" id="dropdown-menu">
                <a href="add_profile.php">Profile Settings</a>
                <a href="account_settings.php">Account Settings</a>
                <a href="change_password.php">Change Password</a>
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="team.php">Meet The Team!</a>
            </div>
        </div>
    </div>
    <div class="email-list">
        <?php if (empty($emails)): ?>
            <div class="no-emails-message">No emails found.</div>
        <?php else: ?>
            <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Sender</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
                <tbody>
            <?php foreach ($emails as $email): ?>
                <tr>
                    <td><?php echo htmlspecialchars($email['sender_email']); ?></td>
                    <td class="email-subject"><?php echo htmlspecialchars($email['subject']); ?></td>
                    <td><?php echo htmlspecialchars($email['created_at']); ?></td>
                    <td>
                        <?php if ($folder === 'starred'): ?>
                            <!-- Unstar button for starred emails -->
                            <a href="starred_actions.php?action=unstar&email_id=<?php echo $email['id']; ?>"><i class="fas fa-star"></i> Unstar</a>
                        <?php else: ?>
                            <!-- Star button for non-starred emails -->
                            <a href="starred_actions.php?action=star&email_id=<?php echo $email['id']; ?>"><i class="fas fa-star"></i> Star</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<!-- The Modal -->
<div id="compose-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <h2>Compose Email</h2>
        </div>
        <div class="modal-body">
            <form action="compose.php" method="POST" enctype="multipart/form-data" onsubmit="submitForm()">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" id="bodyContent" name="body">

                <label for="recipient">To:</label>
                <input type="email" id="recipient" name="recipient" required>

                <div class="cc-bcc-toggle">
                    <label onclick="toggleFields('cc')">CC</label>
                    <label onclick="toggleFields('bcc')">BCC</label>
                </div>

                <div id="cc-fields" class="cc-bcc-fields">
                    <label for="cc">CC:</label>
                    <input type="text" id="cc" name="cc" onkeydown="handleComma(event, this)">
                </div>

                <div id="bcc-fields" class="cc-bcc-fields">
                    <label for="bcc">BCC:</label>
                    <input type="text" id="bcc" name="bcc" onkeydown="handleComma(event, this)">
                </div>

                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" required>

                <label for="body">Message:</label>
                <div class="toolbar">
                    <button type="button" id="boldBtn" onclick="formatText('bold')">B</button>
                    <button type="button" id="italicBtn" onclick="formatText('italic')">I</button>
                    <button type="button" id="underlineBtn" onclick="formatText('underline')">U</button>
                    <input type="file" id="attachment" name="attachment" style="display:none;" onchange="handleFileChange(event)">
                    <button type="button" onclick="document.getElementById('attachment').click()">Attach File</button>
                    <button type="button" onclick="insertEmoji()">😊</button>
                    <button type="button" onclick="insertImage()">📷</button>
                    <button type="button" class="btn-trash" onclick="clearForm()">🗑️</button>
                </div>
                <textarea id="editor" name="body" style="width: 100%; min-height: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                <br>
                <div>
                    <button type="submit" name="send">Send</button>
                    <button type="submit" name="save_draft" class="btn-secondary">Save as Draft</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show the modal when the compose button is clicked
    document.querySelector('#compose-button').addEventListener('click', function() {
        document.getElementById('compose-modal').style.display = 'flex'; // Use flex to center the modal
    });

    // Close the modal when the close button is clicked
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('compose-modal').style.display = 'none';
    });

    // Close the modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target === document.getElementById('compose-modal')) {
            document.getElementById('compose-modal').style.display = 'none';
        }
    };

    // Toggle dropdown menu visibility
    document.querySelector('.profile img').addEventListener('click', function() {
        const dropdown = document.getElementById('dropdown-menu');
        dropdown.classList.toggle('show');
    });

    // Close the dropdown menu if clicked outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('dropdown-menu');
        if (!event.target.closest('.profile')) {
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    });

    // Toggle CC/BCC fields
    function toggleFields(type) {
        document.getElementById('cc-fields').style.display = type === 'cc' ? 'block' : 'none';
        document.getElementById('bcc-fields').style.display = type === 'bcc' ? 'block' : 'none';
    }

    // Handle comma separation in CC/BCC fields
    function handleComma(event, input) {
        if (event.key === ',') {
            let value = input.value.trim();
            if (value) {
                input.value = value + ',';
            }
            event.preventDefault();
        }
    }

    // Text formatting functions for the rich text editor
    function formatText(command) {
        document.execCommand(command, false, null);
    }

    function insertEmoji() {
        document.execCommand('insertText', false, '😊');
    }

    function insertImage() {
        const url = prompt('Enter the image URL');
        if (url) {
            document.execCommand('insertImage', false, url);
        }
    }

    function handleFileChange(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '100%';
                document.getElementById('editor').appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    }

    // Clear form and editor content
    function clearForm() {
        document.querySelector('form').reset();
        document.getElementById('editor').innerHTML = '';
    }

    // Ensure form submission handles textarea content correctly
    function submitForm() {
        // No need to copy content from rich text editor
        // The content will be directly from the textarea
    }
    
    // Attach functions to global scope if needed
    window.toggleFields = toggleFields;
    window.handleComma = handleComma;
    window.formatText = formatText;
    window.insertEmoji = insertEmoji;
    window.insertImage = insertImage;
    window.handleFileChange = handleFileChange;
    window.clearForm = clearForm;
    window.submitForm = submitForm;
});

</script>
</body>
</html>
