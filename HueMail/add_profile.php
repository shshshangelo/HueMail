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

// Fetch existing profile data
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Default profile picture path
$default_profile_pic = 'images/pp.png'; // Ensure this matches the default in welcome.php
$profile_pic_path = $user['profile_pic'] ?: $default_profile_pic;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['tmp_name']) {
        // Handle file upload and cropping
        $profile_pic_path = 'uploads/' . uniqid() . '-' . 'profile_pic.jpg';
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profile_pic_path);

        // Save profile details to the database
        $stmt = $pdo->prepare("UPDATE users SET profile_pic = :profile_pic WHERE id = :id");
        $stmt->execute([
            ':profile_pic' => $profile_pic_path,
            ':id' => $_SESSION['user_id']
        ]);

        // Redirect to inbox after saving
        header('Location: inbox.php');
        exit;
    } else if (isset($_POST['keep_current']) && $_POST['keep_current'] === 'on') {
        // If "Keep current profile picture" is checked, redirect to inbox.php
        header('Location: inbox.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Profile - HueMail</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
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
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 25px;
            max-width: 650px;
            width: 100%;
            margin: 100px auto;
            text-align: center;
            position: relative;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="file"] {
            padding: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            width: auto;
        }
        button {
            width: 20%;
            padding: 12px;
            background-color: #00a400;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: green;
        }
        .error {
            color: red;
            font-size: 16px;
        }
        .profile-pic {
            margin-bottom: 15px;
        }
        .profile-pic img {
            border-radius: 50%;
            width: 200px; /* Increased size */
            height: 200px; /* Increased size */
            object-fit: cover;
            border: 5px solid #00a400; /* Green border */
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
        .notification {
            color: red; /* Changed to red text */
            font-size: 16px;
            margin-bottom: 20px;
            display: none; /* Hidden by default */
        }
        .notification.show {
            display: block;
        }
        #crop-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            z-index: 1000;
            color: #fff;
            display: none;
        }
        .crop-container {
            position: relative;
        }
        #crop-image {
            max-width: 100%;
        }
        #crop-save, #crop-cancel {
            margin: 10px;
            padding: 10px;
            border: none;
            color: #fff;
            cursor: pointer;
        }
        #crop-save {
            background-color: #00a400;
        }
        #crop-cancel {
            background-color: #ff4d4d;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="close-btn" onclick="window.location.href='inbox.php';">&times;</button>
        <h1>Add Your Profile</h1>
        <div class="profile-pic">
            <img src="<?= htmlspecialchars($profile_pic_path) ?>" alt="Profile Picture">
        </div>
        <div id="notification" class="notification"></div>
        <form id="profile-form" method="post" enctype="multipart/form-data">
            <label>
                <input type="checkbox" name="keep_current" id="keep-current-checkbox" <?= isset($_POST['keep_current']) ? 'checked' : '' ?>>
                Keep current profile picture
            </label>
            <input type="file" name="profile_pic" id="profile-pic-input" accept=".jpg, .jpeg, .png">
            <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <center><button type="submit">Save Profile</button></center>
        </form>
    </div>

    <!-- Hidden modal for cropping -->
    <div id="crop-modal">
        <div class="crop-container">
            <img id="crop-image" src="" alt="Profile Picture">
            <button id="crop-save">Save Profile</button>
            <button id="crop-cancel">Cancel</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        let cropper;
        const input = document.getElementById('profile-pic-input');
        const cropImage = document.getElementById('crop-image');
        const cropModal = document.getElementById('crop-modal');
        const cropSave = document.getElementById('crop-save');
        const cropCancel = document.getElementById('crop-cancel');

        input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    cropImage.src = event.target.result;
                    cropModal.style.display = 'block';
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        preview: '.img-preview',
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        cropSave.addEventListener('click', function() {
            const canvas = cropper.getCroppedCanvas();
            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('profile_pic', blob, 'profile_pic.jpg');
                formData.append('keep_current', document.getElementById('keep-current-checkbox').checked);

                fetch('add_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    window.location.href = 'add_profile.php';
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        cropCancel.addEventListener('click', function() {
            cropModal.style.display = 'none';
            cropper.destroy();
        });
    </script>
</body>
</html>
