<?php
session_start();

// Generate a CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Database connection setup
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    // Process the form
    $recipient = filter_var($_POST['recipient'], FILTER_SANITIZE_EMAIL);
    $cc = isset($_POST['cc']) ? filter_var($_POST['cc'], FILTER_SANITIZE_STRING) : '';
    $bcc = isset($_POST['bcc']) ? filter_var($_POST['bcc'], FILTER_SANITIZE_STRING) : '';
    $subject = htmlspecialchars($_POST['subject']);
    $body = htmlspecialchars($_POST['body']);
    $status = isset($_POST['save_draft']) ? 'draft' : 'sent'; // Determine status based on button clicked

    // Basic validation
    $ccArray = array_map('trim', explode(',', $cc));
    $bccArray = array_map('trim', explode(',', $bcc));

    $validCC = array_filter($ccArray, function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    });
    $validBCC = array_filter($bccArray, function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    });

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid recipient email address.';
    } elseif (empty($validCC) && empty($recipient)) {
        $error_message = 'You must provide at least one recipient or CC.';
    } elseif (empty($validBCC) && empty($recipient)) {
        $error_message = 'You must provide at least one recipient or BCC.';
    } else {
        // Convert arrays back to comma-separated strings
        $ccString = implode(',', $validCC);
        $bccString = implode(',', $validBCC);

        // Insert email into the database
        $stmt = $pdo->prepare('INSERT INTO emails (sender, recipient, cc, bcc, subject, body, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if ($stmt->execute([$_SESSION['email'], $recipient, $ccString, $bccString, $subject, $body, $_SESSION['user_id'], $status])) {
            header('Location: inbox.php?folder=' . ($status === 'sent' ? 'sent' : 'draft'));
            exit;
        } else {
            $error_message = 'Failed to save email. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose - HueMail</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            position: relative;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: 100%;
            max-width: 800px;
            box-sizing: border-box;
        }

        .close-btn {
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

    .close-btn:hover {
        background: #ff2d2d;
        transform: scale(1.1); /* Slightly enlarges the button on hover */
    }

    .close-btn:active {
        transform: scale(0.95); /* Slightly shrinks the button when clicked */
    }

    .close-btn i {
        font-size: 20px; /* Adjusted icon size */
    }
        h1 {
            margin-bottom: 20px;
            color: #444;
            font-size: 24px;
            font-weight: bold;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="email"],
        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        input[type="email"]:focus,
        input[type="text"]:focus,
        textarea:focus,
        input[type="file"]:focus {
            border-color: #007bff;
            outline: none;
        }

        textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    resize: vertical;
}


        button,
        .btn {
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

        button:hover,
        .btn:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .cc-bcc-toggle {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .cc-bcc-toggle label {
            cursor: pointer;
            margin-right: 10px;
            color: #007bff;
            text-decoration: underline;
        }

        .cc-bcc-fields {
            display: none;
            margin-bottom: 15px;
        }

        .cc-bcc-fields input {
            margin-bottom: 10px;
        }

        .toolbar {
    display: flex;
    margin-bottom: 10px; /* Adds space between toolbar and editor */
    align-items: center;
}

.toolbar button {
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

.toolbar button:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

.toolbar .btn-trash {
    background-color: #ff4d4d;
}

.toolbar .btn-trash:hover {
    background-color: #ff2d2d;
}

        .btn-trash {
            background: #ff4d4d;
            color: white;
        }

        .btn-trash:hover {
            background: #ff2d2d;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="close-btn" onclick="window.location.href='inbox.php';"><i class="fas fa-times"></i></button>
        <h1>Compose Email</h1>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
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

    <script>
        function toggleFields(type) {
            document.getElementById('cc-fields').style.display = type === 'cc' ? 'block' : 'none';
            document.getElementById('bcc-fields').style.display = type === 'bcc' ? 'block' : 'none';
        }

        function handleComma(event, input) {
            if (event.key === ',') {
                let value = input.value.trim();
                if (value) {
                    input.value = value + ',';
                }
                event.preventDefault();
            }
        }

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

        function clearForm() {
            document.querySelector('form').reset();
            document.getElementById('editor').innerHTML = '';
        }

        function submitForm() {
    // No need to copy content from rich text editor
    // The content will be directly from the textarea
}

    </script>
</body>
</html>
