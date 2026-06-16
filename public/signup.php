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

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (isset($users[$username])) {
        $errors[] = 'That username is already taken.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }
    if ($confirm_password === '') {
        $errors[] = 'Please confirm your password.';
    }
    if ($password !== '' && $confirm_password !== '' && $password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        if (get_user_by_username($username) !== null) {
            $errors[] = 'That username is already taken.';
        } elseif (get_user_by_email($email) !== null) {
            $errors[] = 'That email is already registered.';
        } else {
            $userId = create_user($username, password_hash($password, PASSWORD_DEFAULT), $name, $email);

            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = [
                'id' => $userId,
                'username' => $username,
                'name' => $name,
                'email' => $email,
            ];

            header('Location: vehicle_rental.php');
            exit;
        }
    }
}

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Vehicle Rental</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 28%), radial-gradient(circle at bottom right, rgba(255,255,255,0.08), transparent 24%), linear-gradient(135deg, #667eea 0%, #764ba2 48%, #3b4cc0 100%); min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; padding: 20px; overflow-x: hidden; animation: gradientBG 14s ease infinite; }
        .signup-card { background: rgba(255,255,255,0.96); border-radius: 24px; padding: 40px; width: 100%; max-width: 520px; box-shadow: 0 28px 80px rgba(0, 0, 0, 0.18); border: 1px solid rgba(255,255,255,0.25); animation: fadeInUp 0.9s ease both; }
        h1 { margin-bottom: 10px; color: #333; font-size: 2rem; }
        p { color: #555; margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input[type="text"], input[type="email"], input[type="password"] { width: 93%; padding: 14px 16px; margin-bottom: 18px; border: 1px solid #d8d8d8; border-radius: 10px; font-size: 1rem; }
        input[type="submit"] { width: 100%; padding: 14px 16px; border: none; border-radius: 10px; background: #667eea; color: white; font-size: 1rem; cursor: pointer; transition: background 0.25s ease; }
        input[type="submit"]:hover { background: #5b67d2; }
        .message { margin-bottom: 18px; padding: 16px; border-radius: 10px; }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        .error { background: #fde2e2; border: 1px solid #f5c2c7; color: #842029; }
        .help-text { font-size: 0.95rem; color: #666; margin-top: 16px; }
        .help-text a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="signup-card">
        <h1>Create an Account</h1>
        <p>Sign up to start booking vehicles right away.</p>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo safe($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="username">Full Name</label>
            <input type="text" id="username" name="username" value="<?php echo safe($username); ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password">

            <label for="name">User Name</label>
            <input type="text" id="name" name="name" value="<?php echo safe($name); ?>">

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo safe($email); ?>">

            <input type="submit" value="Sign Up">
        </form>

        <div class="help-text">
            Already have an account? <a href="login.php">Log in here</a>.
        </div>
    </div>
</body>
</html>
