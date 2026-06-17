<?php
session_start();
require_once '../src/db.php';

$errors = [];
$username = '';

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: vehicle_rental.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $errors[] = 'Please enter your username.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $user = get_user_by_username($username);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];
            setcookie('vehicle_rental_user', $username, time() + 86400, '/');
            $_SESSION['show_cookie_popup'] = true;
        } else {
            $errors[] = 'Invalid username or password.';
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
    <title>Login | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f8fafc; }
        .split-layout { display: flex; width: 100%; }
        
        .hero-side { flex: 1; background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: white; display: flex; flex-direction: column; justify-content: center; padding: 4rem; position: relative; overflow: hidden; }
        .hero-side::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); animation: spin 20s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .hero-side h1 { font-size: 3rem; font-weight: 700; margin-bottom: 1rem; position: relative; z-index: 1; }
        .hero-side p { font-size: 1.2rem; opacity: 0.9; position: relative; z-index: 1; max-width: 400px; }
        
        .form-side { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; background: white; }
        .form-container { width: 100%; max-width: 400px; }
        .form-container h2 { font-size: 2rem; color: #0f172a; margin-bottom: 0.5rem; }
        .form-container p.subtitle { color: #64748b; margin-bottom: 2rem; }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem; }
        input[type="text"], input[type="password"] { width: 100%; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 0.75rem; font-size: 1rem; transition: all 0.3s; background: #f8fafc; }
        input[type="text"]:focus, input[type="password"]:focus { outline: none; border-color: #10b981; background: white; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
        
        input[type="submit"] { width: 100%; padding: 1rem; background: #10b981; color: white; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s; margin-top: 1rem; }
        input[type="submit"]:hover { background: #059669; }
        
        .message.error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .message.error ul { margin-left: 1.5rem; margin-top: 0.5rem; }
        
        .help-text { text-align: center; margin-top: 2rem; color: #64748b; font-size: 0.9rem; }
        .help-text a { color: #10b981; text-decoration: none; font-weight: 600; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .modal-content { background-color: white; margin: 15% auto; padding: 2.5rem; border-radius: 1rem; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-btn { padding: 0.75rem 2rem; background: #10b981; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; margin-top: 1.5rem; }
        
        @media (max-width: 768px) { .hero-side { display: none; } }
    </style>
</head>
<body>
    <div class="split-layout">
        <div class="hero-side">
            <h1>🌿 EcoTrack</h1>
            <p>Welcome back! Sign in to continue your journey towards sustainable and efficient travel.</p>
        </div>
        <div class="form-side">
            <div class="form-container">
                <h2>Sign In</h2>
                <p class="subtitle">Enter your credentials to access your account.</p>

                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        <strong>Sign in failed:</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?php echo safe($error); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo safe($username); ?>" placeholder="e.g. admin">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••">
                    </div>
                    <input type="submit" value="Sign In">
                </form>

                <div class="help-text">
                    <p style="margin-bottom: 0.5rem;">Use <strong>admin / rental123</strong> or <strong>customer / rentme456</strong></p>
                    Don't have an account? <a href="signup.php">Create one now</a>
                </div>
            </div>
        </div>
    </div>

    <div id="cookieModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1rem; color: #0f172a;">Session Restored</h2>
            <p style="color: #64748b;">A cookie has been set to remember your login securely.</p>
            <button class="modal-btn" onclick="closeCookieModal()">Continue to Dashboard</button>
        </div>
    </div>

    <script>
        function closeCookieModal() {
            document.getElementById('cookieModal').style.display = 'none';
            window.location.href = 'selection.php';
        }
        <?php if (isset($_SESSION['show_cookie_popup']) && $_SESSION['show_cookie_popup']): ?>
            document.getElementById('cookieModal').style.display = 'block';
            <?php unset($_SESSION['show_cookie_popup']); ?>
        <?php endif; ?>
    </script>
</body>
</html>