<?php
session_start();
require_once '../src/db.php';

$errors = [];
$username = '';
$name = '';
$email = '';

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: vehicle_rental.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($username === '') { $errors[] = 'Username is required.'; } elseif (isset($users[$username])) { $errors[] = 'That username is already taken.'; }
    if ($password === '') { $errors[] = 'Password is required.'; }
    if ($confirm_password === '') { $errors[] = 'Please confirm your password.'; }
    if ($password !== '' && $confirm_password !== '' && $password !== $confirm_password) { $errors[] = 'Passwords do not match.'; }
    if ($name === '') { $errors[] = 'Full name is required.'; }
    if ($email === '') { $errors[] = 'Email address is required.'; } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }

    if (empty($errors)) {
        if (get_user_by_username($username) !== null) {
            $errors[] = 'That username is already taken.';
        } elseif (get_user_by_email($email) !== null) {
            $errors[] = 'That email is already registered.';
        } else {
            $userId = create_user($username, password_hash($password, PASSWORD_DEFAULT), $name, $email);
            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = [ 'id' => $userId, 'username' => $username, 'name' => $name, 'email' => $email ];
            header('Location: vehicle_rental.php');
            exit;
        }
    }
}
function safe($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f8fafc; }
        .split-layout { display: flex; width: 100%; flex-direction: row-reverse; }
        
        .hero-side { flex: 1; background: linear-gradient(135deg, #059669 0%, #064e3b 100%); color: white; display: flex; flex-direction: column; justify-content: center; padding: 4rem; position: relative; overflow: hidden; }
        .hero-side::after { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%); animation: spin 25s linear infinite reverse; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .hero-side h1 { font-size: 3rem; font-weight: 700; margin-bottom: 1rem; position: relative; z-index: 1; }
        .hero-side p { font-size: 1.2rem; opacity: 0.9; position: relative; z-index: 1; max-width: 400px; }
        
        .form-side { flex: 1.2; display: flex; align-items: center; justify-content: center; padding: 2rem; background: white; }
        .form-container { width: 100%; max-width: 480px; }
        .form-container h2 { font-size: 2rem; color: #0f172a; margin-bottom: 0.5rem; }
        .form-container p.subtitle { color: #64748b; margin-bottom: 2rem; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .full-width { grid-column: 1 / -1; }
        
        label { display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 0.85rem 1rem; border: 1px solid #cbd5e1; border-radius: 0.75rem; font-size: 1rem; transition: all 0.3s; background: #f8fafc; }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus { outline: none; border-color: #10b981; background: white; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
        
        input[type="submit"] { width: 100%; padding: 1rem; background: #10b981; color: white; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s; margin-top: 1rem; }
        input[type="submit"]:hover { background: #059669; }
        
        .message.error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .message.error ul { margin-left: 1.5rem; margin-top: 0.5rem; }
        
        .help-text { text-align: center; margin-top: 2rem; color: #64748b; font-size: 0.9rem; }
        .help-text a { color: #10b981; text-decoration: none; font-weight: 600; }
        
        @media (max-width: 900px) { .hero-side { display: none; } .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="split-layout">
        <div class="hero-side">
            <h1>Create Account</h1>
            <p>Join the EcoTrack community today!</p>
        </div>
        <div class="form-side">
            <div class="form-container">
                <h2>Join EcoTrack</h2>
                <p class="subtitle">Fill in your details below to get started.</p>

                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        <ul><?php foreach ($errors as $error): ?><li><?php echo safe($error); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo safe($name); ?>" placeholder="Jane Doe">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo safe($username); ?>" placeholder="janedoe99">
                        </div>
                        <div class="form-group full-width">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo safe($email); ?>" placeholder="jane@example.com">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••">
                        </div>
                    </div>
                    <input type="submit" value="Create Account">
                </form>

                <div class="help-text">
                    Already have an account? <a href="login.php">Sign In instead</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>