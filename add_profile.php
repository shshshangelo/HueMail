<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
$host = 'localhost';
$db = 'HueMail';
$user = 'root';
$pass = '';

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
$default_profile_pic = 'images/pp.png';
$profile_pic_path = $user['profile_pic'] ?: $default_profile_pic;

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['tmp_name']) {
        $file = $_FILES['profile_pic'];
        $file_name = trim($file['name']);

        // Check for valid image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $error = "File is not a valid image.";
        } else {
            // Validate the MIME type and extension
            $allowed_types = ['image/jpeg', 'image/png'];
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            if (!in_array($image_info['mime'], $allowed_types) || !in_array(strtolower($extension), $allowed_extensions)) {
                $error = "Only JPG and PNG files are allowed.";
            } else if ($file['size'] > 2 * 1024 * 1024) { // Limit to 2MB
                $error = "File size must be less than 2MB.";
            } else {
                // Handle file upload
                if ($user['profile_pic'] && $user['profile_pic'] !== $default_profile_pic) {
                    // Delete old profile picture only if it's not the default
                    if (file_exists($user['profile_pic'])) {
                        unlink($user['profile_pic']);
                    }
                }

                // Prepare new file path
                $profile_pic_path = 'uploads/' . uniqid() . '-profile_pic.' . $extension;
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                if (move_uploaded_file($file['tmp_name'], $profile_pic_path)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_pic = :profile_pic WHERE id = :id");
                    $stmt->execute([
                        ':profile_pic' => $profile_pic_path,
                        ':id' => $_SESSION['user_id']
                    ]);

                    $_SESSION['success_message'] = 'Profile picture successfully updated!';
                    header('Location: inbox.php');
                    exit;
                } else {
                    $error = "Failed to move uploaded file.";
                }
            }
        }
    } else if (isset($_POST['keep_current']) && $_POST['keep_current'] === 'on') {
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
            width: 200px;
            height: 200px;
            object-fit: cover;
            border: 5px solid #00a400;
            display: block;
            margin: 0 auto;
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

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
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
            width: 500px;
            height: 500px;
            overflow: hidden;
            border-radius: 1000%;
        }
        #crop-image {
            max-width: none;
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
        #zoom-in, #zoom-out {
            margin: 10px;
            padding: 10px;
            border: none;
            color: #fff;
            background-color: #007bff; /* Blue color */
            cursor: pointer;
        }
        #zoom-in:hover, #zoom-out:hover {
            background-color: #0056b3; /* Darker blue */
        }
        .submit-button {
            display: block;
            margin: 0 auto; /* Centers the button */
            width: 20%; /* Adjust width as needed */
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
        <div id="notification" class="notification"><?= htmlspecialchars($error) ?></div>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <form id="profile-form" method="post" enctype="multipart/form-data">
            <label>
                <input type="checkbox" name="keep_current" id="keep-current-checkbox" <?= isset($_POST['keep_current']) ? 'checked' : '' ?>>
                Keep current profile picture
            </label>
            <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*">
            <button class="submit-button"
                type="submit">Save</button>
        </form>
    </div>
    <div id="crop-modal">
        <h2>Adjust Your Profile Picture</h2>
        <div class="crop-container">
            <img id="crop-image" src="" alt="Crop Image">
        </div>
        <button id="zoom-in">Zoom In</button>
        <button id="zoom-out">Zoom Out</button>
        <button id="crop-save">Save</button>
        <button id="crop-cancel">Cancel</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        const profileForm = document.getElementById('profile-form');
        const profilePicInput = document.getElementById('profile-pic-input');
        const keepCurrentCheckbox = document.getElementById('keep-current-checkbox');
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const zoomInButton = document.getElementById('zoom-in');
        const zoomOutButton = document.getElementById('zoom-out');
        const cropSaveButton = document.getElementById('crop-save');
        const cropCancelButton = document.getElementById('crop-cancel');
        let cropper;

        profileForm.addEventListener('submit', function (event) {
            if (!profilePicInput.value && keepCurrentCheckbox.checked) {
                event.preventDefault();
            }
        });

        profilePicInput.addEventListener('change', function (event) {
            const files = event.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    cropImage.src = e.target.result;
                    cropModal.style.display = 'block';
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 2,
                        minContainerWidth: 500,
                        minContainerHeight: 500
                    });
                };
                reader.readAsDataURL(files[0]);
            }
        });

        cropSaveButton.addEventListener('click', function () {
            const canvas = cropper.getCroppedCanvas({
                width: 500,
                height: 500
            });

            canvas.toBlob(function (blob) {
                const formData = new FormData(profileForm);
                formData.set('profile_pic', blob, 'profile_pic.png');
                
                fetch('add_profile.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        window.location.href = 'inbox.php';
                    } else {
                        const notification = document.getElementById('notification');
                        notification.textContent = data.message;
                    }
                }).catch(error => console.error('Error:', error));
            });
        });

        cropCancelButton.addEventListener('click', function () {
            cropModal.style.display = 'none';
            cropper.destroy();
            cropper = null;
            profilePicInput.value = '';
        });

        zoomInButton.addEventListener('click', function () {
            cropper.zoom(0.1);
        });

        zoomOutButton.addEventListener('click', function () {
            cropper.zoom(-0.1);
        });
    </script>
</body>
</html>
