<?php
session_start();
require_once '../src/db.php';

$authenticated = $_SESSION['authenticated'] ?? false;
$current_user = $_SESSION['user'] ?? null;

if (!$authenticated) {
    header('Location: login.php');
    exit;
}

// Store the selection preference
$selected_mode = $_SESSION['rental_mode'] ?? null;
$license_uploaded = $_SESSION['license_uploaded'] ?? false;
$license_filename = $_SESSION['license_filename'] ?? null;
$upload_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle license upload
    if (isset($_FILES['license_photo']) && $_FILES['license_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['license_photo'];
        $filename = $file['name'];
        $tmp_name = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];

        // Validate file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $upload_errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
        }
        if ($file_size > $max_size) {
            $upload_errors[] = 'File size exceeds 5MB limit.';
        }

        if (empty($upload_errors)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/licenses/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $new_filename = 'license_' . time() . '_' . md5($current_user['username'] ?? $current_user['name']) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($tmp_name, $upload_path)) {
                $_SESSION['license_uploaded'] = true;
                $_SESSION['license_filename'] = $new_filename;
                $_SESSION['license_upload_path'] = $upload_path;
                $license_uploaded = true;
                $license_filename = $new_filename;

                if (!empty($current_user['id'])) {
                    update_user_license((int)$current_user['id'], $new_filename);
                }
            } else {
                $upload_errors[] = 'Failed to upload file. Please try again.';
            }
        }
    } elseif (isset($_FILES['license_photo']) && $_FILES['license_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors[] = 'An error occurred during file upload.';
    }

    // Handle mode selection
    $mode = $_POST['mode'] ?? null;

    if ($mode === 'with_driver') {
        $_SESSION['rental_mode'] = $mode;
        if (!empty($current_user['id'])) {
            update_user_rental_mode((int)$current_user['id'], $mode);
        }
        header('Location: vehicle_rental.php');
        exit;
    } elseif ($mode === 'without_driver') {
        if (!$license_uploaded) {
            $upload_errors[] = 'Please upload your license photo before proceeding with self-drive rental.';
        } else {
            $_SESSION['rental_mode'] = $mode;
            if (!empty($current_user['id'])) {
                update_user_rental_mode((int)$current_user['id'], $mode);
            }
            header('Location: vehicle_rental_no_driver.php');
            exit;
        }
    }
}

function safe($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Rental Mode | Vehicle Rental</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 25%),
                        radial-gradient(circle at bottom right, rgba(255,255,255,0.12), transparent 22%),
                        linear-gradient(135deg, #667eea 0%, #764ba2 48%, #3b4cc0 100%);
            background-size: 220% 220%;
            animation: gradientBG 16s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .container {
            max-width: 1080px;
            width: 100%;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.18);
            border-radius: 32px;
            padding: 32px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .container::before,
        .container::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.55;
            z-index: 0;
        }

        .container::before {
            width: 240px;
            height: 240px;
            background: rgba(255, 255, 255, 0.16);
            top: -60px;
            left: -60px;
        }

        .container::after {
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.12);
            bottom: -40px;
            right: -40px;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease both;
            animation-delay: 0.1s;
        }

        .header h1 {
            font-size: 2.7em;
            margin-bottom: 10px;
            text-shadow: 0 4px 18px rgba(0, 0, 0, 0.28);
            letter-spacing: 0.06em;
        }

        .header p {
            font-size: 1.15em;
            opacity: 0.95;
            line-height: 1.5;
        }

        .user-greeting {
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(12px);
            padding: 18px 24px;
            border-radius: 16px;
            margin-bottom: 28px;
            color: white;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.25);
            animation: fadeInUp 0.8s ease both;
            animation-delay: 0.18s;
        }

        .user-greeting p {
            font-size: 1rem;
            line-height: 1.6;
        }

        .selection-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .selection-container form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            width: 100%;
        }

        .option-card {
            background: rgba(255,255,255,0.96);
            border-radius: 24px;
            padding: 38px 30px;
            box-shadow: 0 28px 80px rgba(12, 29, 81, 0.12);
            transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
            cursor: pointer;
            text-align: center;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            animation: popIn 0.8s ease both;
        }

        .option-card:nth-of-type(1) {
            animation-delay: 0.28s;
        }

        .option-card:nth-of-type(2) {
            animation-delay: 0.34s;
        }

        .option-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 80px rgba(10, 29, 80, 0.18);
        }

        .option-card.with-driver {
            border-color: #667eea;
        }

        .option-card.with-driver:hover {
            border-color: #764ba2;
        }

        .option-card.without-driver {
            border-color: #28a745;
        }

        .option-card.without-driver:hover {
            border-color: #218838;
        }

        .option-icon {
            font-size: 4em;
            margin-bottom: 20px;
            display: block;
        }

        .option-title {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .option-description {
            color: #666;
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .option-features {
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            list-style: none;
        }

        .option-features li {
            padding: 8px 0;
            color: #555;
            border-bottom: 1px solid #e9ecef;
        }

        .option-features li:last-child {
            border-bottom: none;
        }

        .option-features li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }

        .option-card.without-driver .option-features li:before {
            color: #28a745;
        }

        .option-card.with-driver .option-features li:before {
            color: #667eea;
        }

        .select-button {
            width: 100%;
            padding: 14px 20px;
            font-size: 1.1em;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }

        .option-card.with-driver .select-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .option-card.with-driver .select-button:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .option-card.with-driver .select-button:disabled {
            background: linear-gradient(135deg, #ccc 0%, #999 100%);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
        }

        .option-card.with-driver .select-button:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .option-card.without-driver .select-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .option-card.without-driver .select-button:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
        }

        .option-card.without-driver .select-button:disabled {
            background: linear-gradient(135deg, #ccc 0%, #999 100%);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
        }

        .option-card.without-driver .select-button:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .select-button {
            width: 100%;
            padding: 16px 22px;
            font-size: 1.1em;
            font-weight: 700;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.35s ease;
            color: white;
            box-shadow: 0 14px 30px rgba(102, 126, 234, 0.18);
        }

        .select-button:active {
            transform: translateY(2px);
        }

        .file-input-label {
            display: block;
            padding: 34px;
            border: 2px dashed #667eea;
            border-radius: 18px;
            text-align: center;
            cursor: pointer;
            transition: all 0.35s ease;
            background: #f8f9fa;
            position: relative;
        }

        .file-input-label:hover {
            background: #eef2ff;
            border-color: #764ba2;
            transform: translateY(-2px);
        }

        .file-input-label.has-file {
            background: #d4edda;
            border-color: #28a745;
        }

        .file-input-label .upload-icon {
            font-size: 3.4em;
            margin-bottom: 10px;
            display: block;
            animation: float 4s ease-in-out infinite;
        }

        .license-preview {
            margin-top: 20px;
            display: none;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .license-preview.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .license-preview img {
            max-width: 220px;
            max-height: 220px;
            border-radius: 18px;
            border: 2px solid #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.18);
            margin: 0 auto;
            display: block;
        }

        .file-upload-requirements {
            background: rgba(239, 244, 255, 0.9);
            border-left: 4px solid #667eea;
            padding: 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 0.95em;
            color: #2b3a74;
        }

        .file-upload-requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.8s ease both;
            animation-delay: 0.42s;
        }


        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }

        .footer a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .footer a:hover {
            opacity: 0.8;
        }

        .logout-link {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 6px;
            border: 2px solid white;
            transition: all 0.3s;
        }

        .logout-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .license-upload-section {
            background: white;
            border-radius: 18px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 16px 60px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(102, 126, 234, 0.24);
            animation: fadeInUp 0.8s ease both;
            animation-delay: 0.22s;
        }

        .license-upload-section h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .license-upload-section p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .file-upload-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-input-label {
            display: block;
            padding: 30px;
            border: 2px dashed #667eea;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .file-input-label:hover {
            background: #eef2ff;
            border-color: #764ba2;
        }

        .file-input-label.has-file {
            background: #d4edda;
            border-color: #28a745;
        }

        .file-input-label.has-file:hover {
            background: #c3e6cb;
        }

        .file-input-label.error {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .file-input-label .upload-icon {
            font-size: 3em;
            margin-bottom: 10px;
            display: block;
        }

        .file-input-label .upload-text {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .file-input-label .upload-hint {
            color: #999;
            font-size: 0.9em;
        }

        .file-input-label.has-file .upload-text {
            color: #28a745;
        }

        .file-input-label.has-file .upload-hint {
            color: #155724;
        }

        #licensePhoto {
            display: none;
        }

        .license-preview {
            margin-top: 20px;
            display: none;
        }

        .license-preview.show {
            display: block;
        }

        .license-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
            margin: 0 auto;
            display: block;
        }

        .license-preview-info {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
            color: #4338ca;
            font-size: 0.9em;
            text-align: center;
        }

        .file-upload-requirements {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #4338ca;
        }

        .file-upload-requirements ul {
            margin: 10px 0 0 20px;
            list-style: none;
        }

        .file-upload-requirements li {
            margin: 5px 0;
            padding-left: 20px;
            position: relative;
        }

        .file-upload-requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }

        .upload-error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            color: #842029;
            padding: 12px;
            margin-bottom: 20px;
        }

        .upload-error-message ul {
            margin: 10px 0 0 20px;
            list-style: none;
        }

        .upload-error-message li {
            margin: 5px 0;
            padding-left: 20px;
            position: relative;
        }

        .upload-error-message li:before {
            content: "✗";
            position: absolute;
            left: 0;
            font-weight: bold;
            color: #dc3545;
        }

        .upload-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            color: #155724;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: translateY(28px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        @media (max-width: 768px) {
            .license-upload-section {
                padding: 25px;
            }

            .file-input-label {
                padding: 20px;
            }

            .file-input-label .upload-icon {
                font-size: 2.5em;
            }
        }
            .header h1 {
                font-size: 2em;
            }

            .selection-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .option-card {
                padding: 30px 20px;
            }

            .option-icon {
                font-size: 3em;
            }

            .option-title {
                font-size: 1.5em;
            }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Vehicle Rental System</h1>
            <p>Choose Your Rental Experience</p>
        </div>

        <div class="user-greeting">
            <p>Welcome, <strong><?php echo safe($current_user['name'] ?? $current_user['username'] ?? 'Guest'); ?></strong>!</p>
            <p>How would you like to rent a vehicle today?</p>
        </div>

        <!-- License Upload Section -->
        <div class="license-upload-section">
            <h2>📋 Upload Your Driver's License (Required for Self-Drive)</h2>
            <p>For self-drive rentals, we need a copy of your valid driver's license. If you choose to rent with one of our professional drivers, this is optional.</p>

            <?php if (!empty($upload_errors)): ?>
                <div class="upload-error-message">
                    <strong>Upload Error:</strong>
                    <ul>
                        <?php foreach ($upload_errors as $error): ?>
                            <li><?php echo safe($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($license_uploaded): ?>
                <div class="upload-success">
                    ✓ License photo uploaded successfully! You can now proceed with self-drive rental.
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="file-upload-requirements">
                    <strong>Requirements:</strong>
                    <ul>
                        <li>Formats: JPG, PNG, GIF, or WebP</li>
                        <li>Maximum file size: 5MB</li>
                        <li>Clear photo of your license (front side)</li>
                        <li>Valid and not expired</li>
                    </ul>
                </div>

                <div class="file-upload-wrapper">
                    <label for="licensePhoto" class="file-input-label <?php echo $license_uploaded ? 'has-file' : ''; ?>" id="fileInputLabel">
                        <span class="upload-icon">📸</span>
                        <div class="upload-text"><?php echo $license_uploaded ? '✓ License Photo Uploaded' : 'Click to Upload License Photo'; ?></div>
                        <div class="upload-hint"><?php echo $license_uploaded ? $license_filename : 'or drag and drop'; ?></div>
                    </label>
                    <input type="file" id="licensePhoto" name="license_photo" accept="image/*" required onchange="handleFileSelect(event)">
                </div>

                <div class="license-preview" id="licensePreview">
                    <img id="previewImage" src="" alt="License preview">
                    <div class="license-preview-info">
                        <div id="fileName"></div>
                    </div>
                </div>
            </form>
        </div>

        <div class="user-greeting">
            <p>Select Your Rental Experience Below</p>
        </div>

        <div class="selection-container">
            <form method="POST" enctype="multipart/form-data" id="selectionForm">
                <!-- With Driver Option -->
                <div class="option-card with-driver">
                    <span class="option-icon">👨‍💼</span>
                    <div class="option-title">With Driver</div>
                    <div class="option-description">
                        Let our professional drivers handle the wheel while you relax and enjoy the ride.
                    </div>
                    <ul class="option-features">
                        <li>Professional drivers</li>
                        <li>All vehicle types available</li>
                        <li>Premium experience</li>
                        <li>Driver insurance included</li>
                    </ul>
                    <button type="button" class="select-button" onclick="submitSelection('with_driver')">
                        Select This Option
                    </button>
                </div>

                <!-- Without Driver Option -->
                <div class="option-card without-driver">
                    <span class="option-icon">🏎️</span>
                    <div class="option-title">Without Driver</div>
                    <div class="option-description">
                        Take full control of your journey with our self-drive rental options.
                    </div>
                    <ul class="option-features">
                        <li>Self-drive freedom</li>
                        <li>Flexible schedules</li>
                        <li>Economy pricing</li>
                        <li>Wide vehicle selection</li>
                        <li style="font-style: italic; color: #dc3545;">⚠️ License required</li>
                    </ul>
                    <button type="button" class="select-button" onclick="submitSelection('without_driver')" <?php echo !$license_uploaded ? 'disabled title="Upload your license first"' : ''; ?>>
                        <?php echo $license_uploaded ? 'Select This Option' : 'Upload License First'; ?>
                    </button>
                </div>

                <input type="hidden" name="mode" id="selectedMode" value="">
            </form>
        </div>

        <div class="footer">
            <p>📸 Want to see our vehicle collection?</p>
            <div class="footer-links">
                <a href="images_gallery.php">View Gallery</a>
                <a href="logout.php" class="logout-link">Log Out</a>
            </div>
        </div>
    </div>

    <script>
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            const label = document.getElementById('fileInputLabel');
            const preview = document.getElementById('licensePreview');
            const previewImage = document.getElementById('previewImage');
            const fileName = document.getElementById('fileName');

            // Validate file
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Please upload JPG, PNG, GIF, or WebP image.');
                event.target.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert('File size exceeds 5MB limit.');
                event.target.value = '';
                return;
            }

            reader.onload = function(e) {
                previewImage.src = e.target.result;
                fileName.textContent = 'File: ' + file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
                label.classList.add('has-file');
                preview.classList.add('show');
            };

            reader.readAsDataURL(file);

            // Submit the form to upload the license
            setTimeout(() => {
                const form = document.querySelector('.license-upload-section form');
                if (form) {
                    form.submit();
                }
            }, 500);
        }

        function submitSelection(mode) {
            const form = document.getElementById('selectionForm');
            const selectedModeInput = document.getElementById('selectedMode');
            selectedModeInput.value = mode;
            form.submit();
        }

        // Drag and drop support
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('licensePhoto');
            const label = document.querySelector('.file-input-label');

            if (label) {
                label.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    label.style.background = '#eef2ff';
                    label.style.borderColor = '#764ba2';
                });

                label.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    label.style.background = '';
                    label.style.borderColor = '';
                });

                label.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    label.style.background = '';
                    label.style.borderColor = '';

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        const event = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(event);
                    }
                });
            }
        });
    </script>
</body>
</html>
