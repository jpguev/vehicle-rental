<?php
session_start();
require_once '../src/db.php'; // Adjust path if db.php is in a different directory

$authenticated = $_SESSION['authenticated'] ?? false;
$current_user = $_SESSION['user'] ?? null;

if (!$authenticated) {
    header('Location: login.php');
    exit;
}

// CRITICAL REDIRECT: If the user is admin, immediately bypass this page and go to admin_bookings
if (isset($current_user['username']) && $current_user['username'] === 'admin') {
    $_SESSION['rental_mode'] = 'with_driver'; // Provide a default safe fallback session state
    header('Location: admin_bookings.php');
    exit;
}

// Store the selection preference for standard clients
$selected_mode = $_SESSION['rental_mode'] ?? null;
$license_uploaded = $_SESSION['license_uploaded'] ?? false;
$license_filename = $_SESSION['license_filename'] ?? null;
$upload_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Handle license upload first if a file was submitted
    if (isset($_FILES['license_photo']) && $_FILES['license_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['license_photo'];
        $filename = $file['name'];
        $tmp_name = $file['tmp_name'];
        $file_size = $file['size'];
        
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
            $upload_dir = 'uploads/licenses/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

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

    // 2. Handle mode selection and final redirect for standard users
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
            $upload_errors[] = 'A valid driver\'s license photo is required to proceed with self-drive.';
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Mode | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; }
        
        .header { text-align: center; margin-bottom: 2.5rem; width: 100%; max-width: 600px; }
        .header h1 { font-size: 2.5rem; color: #064e3b; margin-bottom: 0.5rem; font-weight: 800; }
        .header p { color: #64748b; font-size: 1.1rem; }
        
        .container { width: 100%; max-width: 720px; display: flex; flex-direction: column; }
        
        .card { background: white; border-radius: 1.25rem; padding: 2.5rem; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); width: 100%; }
        
        .step { display: none; width: 100%; animation: slideIn 0.35s ease forwards; }
        .step.active { display: flex; flex-direction: column; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .grid-options { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; width: 100%; }
        @media (max-width: 640px) { .grid-options { grid-template-columns: 1fr; } }
        
        .option-card { border: 2px solid #e2e8f0; border-radius: 1rem; padding: 2.5rem 1.5rem; text-align: center; cursor: pointer; transition: all 0.25s ease; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; }
        .option-card:hover { border-color: #10b981; transform: translateY(-4px); box-shadow: 0 12px 24px -5px rgba(16, 185, 129, 0.12); background: white; }
        .option-icon { font-size: 3.5rem; margin-bottom: 12px; line-height: 1; }
        .option-title { font-size: 1.35rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a; }
        .option-desc { color: #64748b; font-size: 0.95rem; line-height: 1.5; }
        
        .back-btn-container { width: 100%; display: flex; justify-content: flex-start; margin-bottom: 1.5rem; }
        .back-btn { background: none; border: none; color: #64748b; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: color 0.2s; padding: 0.5rem 0; }
        .back-btn:hover { color: #0f172a; }
        
        .upload-header { margin-bottom: 2rem; text-align: center; width: 100%; }
        .upload-header h2 { font-size: 1.75rem; color: #0f172a; margin-bottom: 0.5rem; font-weight: 700; }
        .upload-header p { color: #64748b; font-size: 0.95rem; }

        .upload-area { border: 2px dashed #10b981; border-radius: 1rem; padding: 3.5rem 2rem; text-align: center; background: #ecfdf5; transition: all 0.25s; cursor: pointer; margin-bottom: 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; }
        .upload-area:hover { background: #d1fae5; border-color: #059669; }
        .upload-area.success { border-color: #059669; background: #d1fae5; border-style: solid; }
        .upload-icon { font-size: 3rem; color: #10b981; margin-bottom: 1rem; line-height: 1; }
        .upload-text { font-weight: 700; color: #064e3b; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .upload-hint { color: #047857; font-size: 0.85rem; }

        .btn-submit { width: 100%; padding: 1rem; border-radius: 0.75rem; font-weight: 700; font-size: 1.05rem; border: none; cursor: pointer; transition: all 0.2s ease; background: #10b981; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); text-align: center; }
        .btn-submit:hover:not(:disabled) { background: #059669; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(16, 185, 129, 0.3); }
        .btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; box-shadow: none; color: #94a3b8; transform: none; }

        .alert { padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-size: 0.95rem; font-weight: 500; width: 100%; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert ul { margin-left: 1.25rem; margin-top: 0.5rem; }

        .nav-links { margin-top: 2.5rem; display: flex; justify-content: center; gap: 2rem; width: 100%; }
        .nav-links a { color: #64748b; text-decoration: none; font-weight: 600; transition: color 0.2s; font-size: 0.95rem; }
        .nav-links a:hover { color: #10b981; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo safe($current_user['name'] ?? 'Guest'); ?>!</h1>
        <p>How would you like to travel today?</p>
    </div>

    <div class="container">
        <div class="card">
            
            <?php if (!empty($upload_errors)): ?>
                <div class="alert alert-error">
                    <strong>Validation Error:</strong>
                    <ul>
                        <?php foreach ($upload_errors as $error): ?>
                            <li><?php echo safe($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="selectionForm">
                <input type="hidden" name="mode" id="selectedMode" value="">

                <div id="step1" class="step active">
                    <div class="grid-options">
                        <div class="option-card" onclick="selectMode('with_driver')">
                            <div class="option-icon">🧑‍✈️</div>
                            <h3 class="option-title">Chauffeur Service</h3>
                            <p class="option-desc">Sit back and relax while our professional eco-drivers navigate the route for you.</p>
                        </div>

                        <div class="option-card" onclick="selectMode('without_driver')">
                            <div class="option-icon">🔑</div>
                            <h3 class="option-title">Self-Drive</h3>
                            <p class="option-desc">Take the wheel yourself and enjoy complete freedom on your own schedule.</p>
                        </div>
                    </div>
                </div>

                <div id="step2" class="step">
                    <div class="back-btn-container">
                        <button type="button" class="back-btn" onclick="goBack()">← Back to Selection</button>
                    </div>
                    
                    <div class="upload-header">
                        <h2>Driver's License Verification</h2>
                        <p>We need to verify your license before you can drive our vehicles.</p>
                    </div>

                    <?php if ($license_uploaded): ?>
                        <div class="alert alert-success">
                            ✓ Your license has already been uploaded and verified (<?php echo safe($license_filename); ?>). You may proceed.
                        </div>
                        <button type="submit" class="btn-submit">Proceed to Dashboard</button>
                    <?php else: ?>
                        <label for="licensePhoto" class="upload-area" id="dropZone">
                            <div class="upload-icon">📸</div>
                            <div class="upload-text" id="uploadText">Click or drag photo to upload</div>
                            <div class="upload-hint">Format: JPG, PNG, or WebP (Max 5MB)</div>
                        </label>
                        <input type="file" id="licensePhoto" name="license_photo" style="display: none;" accept="image/*" onchange="handleFileSelect(event)">
                        
                        <button type="submit" id="submitBtn" class="btn-submit" disabled>Upload & Proceed to Dashboard</button>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>

    <div class="nav-links">
        <a href="images_gallery.php">View Fleet Gallery</a>
        <a href="logout.php">Sign Out</a>
    </div>

    <script>
        function selectMode(mode) {
            document.getElementById('selectedMode').value = mode;
            if (mode === 'with_driver') {
                document.getElementById('selectionForm').submit();
            } else if (mode === 'without_driver') {
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
            }
        }

        function goBack() {
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step1').classList.add('active');
            document.getElementById('selectedMode').value = '';
            
            const fileInput = document.getElementById('licensePhoto');
            if(fileInput) fileInput.value = '';
            
            const submitBtn = document.getElementById('submitBtn');
            if(submitBtn) submitBtn.disabled = true;

            const uploadText = document.getElementById('uploadText');
            if(uploadText) uploadText.innerText = 'Click or drag photo to upload';
            
            const dropZone = document.getElementById('dropZone');
            if(dropZone) dropZone.classList.remove('success');
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;

            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.');
                event.target.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert('File size exceeds the 5MB limit.');
                event.target.value = '';
                return;
            }

            document.getElementById('uploadText').innerText = 'File Selected: ' + file.name;
            document.getElementById('dropZone').classList.add('success');
            document.getElementById('submitBtn').disabled = false;
        }

        const dropZone = document.getElementById('dropZone');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#059669';
                dropZone.style.background = '#d1fae5';
            });
            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '';
                dropZone.style.background = '';
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '';
                dropZone.style.background = '';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById('licensePhoto').files = files;
                    handleFileSelect({target: {files: files}});
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const hasErrors = <?php echo !empty($upload_errors) ? 'true' : 'false'; ?>;
            const attemptedMode = '<?php echo safe($_POST['mode'] ?? ''); ?>';
            if (hasErrors || attemptedMode === 'without_driver') {
                selectMode('without_driver');
            }
        });
    </script>
</body>
</html>