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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Mode | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 3rem 1rem; }
        
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 2.5rem; color: #064e3b; margin-bottom: 0.5rem; font-weight: 800; }
        .header p { color: #64748b; font-size: 1.1rem; }
        
        .container { width: 100%; max-width: 900px; display: flex; flex-direction: column; gap: 2rem; }
        
        .card { background: white; border-radius: 1rem; padding: 2.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; }
        .card-header h2 { font-size: 1.5rem; color: #0f172a; }
        
        .grid-options { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 640px) { .grid-options { grid-template-columns: 1fr; } }
        
        .option-card { border: 2px solid #e2e8f0; border-radius: 1rem; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8fafc; display: flex; flex-direction: column; height: 100%; }
        .option-card:hover { border-color: #10b981; transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1); background: white; }
        .option-icon { font-size: 3.5rem; margin-bottom: 1rem; }
        .option-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a; }
        .option-desc { color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem; flex-grow: 1; }
        
        .btn { width: 100%; padding: 0.85rem; border-radius: 0.5rem; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; font-size: 1rem; }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .btn-primary:disabled { background: #cbd5e1; cursor: not-allowed; }
        
        .upload-area { border: 2px dashed #10b981; border-radius: 1rem; padding: 3rem 2rem; text-align: center; background: #ecfdf5; transition: 0.3s; cursor: pointer; }
        .upload-area:hover { background: #d1fae5; }
        .upload-area.success { border-color: #059669; background: #d1fae5; border-style: solid; }
        .upload-icon { font-size: 2.5rem; color: #10b981; margin-bottom: 1rem; }
        .upload-text { font-weight: 600; color: #064e3b; margin-bottom: 0.5rem; }
        
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.95rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        
        .nav-links { margin-top: 2rem; display: flex; justify-content: center; gap: 2rem; }
        .nav-links a { color: #64748b; text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: #10b981; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo safe($current_user['name'] ?? 'Guest'); ?>!</h1>
        <p>Choose your preferred rental experience.</p>
    </div>

    <div class="container">
        
        <div class="card">
            <div class="card-header">
                <h2>1. Driver's License Verification</h2>
                <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">Only required if you plan to drive the vehicle yourself.</p>
            </div>
            
            <?php if (!empty($upload_errors)): ?>
                <div class="alert alert-error"><ul><?php foreach ($upload_errors as $error): ?><li><?php echo safe($error); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if ($license_uploaded): ?>
                <div class="alert alert-success">✓ License verified! You are cleared for self-drive options.</div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <label for="licensePhoto" class="upload-area <?php echo $license_uploaded ? 'success' : ''; ?>" id="dropZone">
                    <div class="upload-icon">📸</div>
                    <div class="upload-text" id="uploadText"><?php echo $license_uploaded ? 'License Uploaded: ' . safe($license_filename) : 'Click or drag photo to upload'; ?></div>
                    <div style="color: #64748b; font-size: 0.85rem;">JPG, PNG, WebP up to 5MB</div>
                </label>
                <input type="file" id="licensePhoto" name="license_photo" style="display: none;" accept="image/*" onchange="document.getElementById('uploadForm').submit()">
            </form>
        </div>

        <form method="POST" id="selectionForm" class="grid-options">
            <input type="hidden" name="mode" id="selectedMode">
            
            <div class="option-card" onclick="document.getElementById('selectedMode').value='with_driver'; document.getElementById('selectionForm').submit();">
                <div class="option-icon">🧑‍✈️</div>
                <h3 class="option-title">Chauffeur Service</h3>
                <p class="option-desc">Relax while our highly-rated, professional eco-drivers handle the navigation and traffic.</p>
                <button type="button" class="btn btn-primary">Select Chauffeur</button>
            </div>

            <div class="option-card" onclick="<?php echo $license_uploaded ? "document.getElementById('selectedMode').value='without_driver'; document.getElementById('selectionForm').submit();" : "alert('Please upload your license first.');"; ?>">
                <div class="option-icon">🔑</div>
                <h3 class="option-title">Self-Drive</h3>
                <p class="option-desc">Take the wheel and enjoy full flexibility on your schedule with our eco-friendly fleet.</p>
                <button type="button" class="btn btn-primary" <?php echo !$license_uploaded ? 'disabled' : ''; ?>>
                    <?php echo $license_uploaded ? 'Select Self-Drive' : 'Upload License Required'; ?>
                </button>
            </div>
        </form>
    </div>

    <div class="nav-links">
        <a href="images_gallery.php">View Fleet Gallery</a>
        <a href="logout.php">Log Out</a>
    </div>
</body>
</html>