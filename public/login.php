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
            header('Location: selection.php');
            exit;
        }

        $errors[] = 'Invalid username or password.';
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
    <title>Login | Vehicle Rental</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.16), transparent 28%),
                        radial-gradient(circle at bottom right, rgba(255,255,255,0.08), transparent 24%),
                        linear-gradient(135deg, #667eea 0%, #764ba2 48%, #3b4cc0 100%);
            background-size: 220% 220%;
            animation: gradientBG 14s ease infinite;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .login-card {
            background: rgba(255,255,255,0.96);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.18);
            border: 1px solid rgba(255,255,255,0.25);
            animation: fadeInUp 0.9s ease both;
        }

        h1 {
            margin-bottom: 10px;
            color: #333;
            font-size: 2rem;
        }

        p {
            color: #555;
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            width: 92%;
            padding: 14px 16px;
            margin-bottom: 18px;
            border: 1px solid #d8d8d8;
            border-radius: 10px;
            font-size: 1rem;
        }

        input[type="submit"] {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 10px;
            background: #667eea;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.25s ease;
        }

        input[type="submit"]:hover {
            background: #5b67d2;
        }

        .message {
            margin-bottom: 18px;
            padding: 16px;
            border-radius: 10px;
        }

        .error {
            background: #fde2e2;
            border: 1px solid #f5c2c7;
            color: #842029;
        }

        .help-text {
            font-size: 0.95rem;
            color: #666;
            margin-top: 16px;
        }

        .help-text a {
            color: #667eea;
            text-decoration: none;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 32px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-content h2 {
            color: #333;
            margin-top: 0;
        }

        .modal-content p {
            color: #555;
            line-height: 1.6;
        }

        .modal-btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.25s ease;
        }

        .modal-btn:hover {
            background: #5b67d2;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h1>Vehicle Rental Login</h1>
        <p>Sign in to manage bookings and rent vehicles.</p>

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
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo safe($username); ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password">

            <input type="submit" value="Log In">
        </form>

        <div class="help-text">
            Use <strong>admin / rental123</strong> or <strong>customer / rentme456</strong> to sign in.
            <br>
            Don't have an account? <a href="signup.php">Create one here</a>.
        </div>
    </div>

    <div id="cookieModal" class="modal">
        <div class="modal-content">
            <h2>Cookie Created</h2>
            <p>A cookie has been set to remember your login. This helps keep you signed in during your session.</p>
            <button class="modal-btn" onclick="closeCookieModal()">Got It</button>
        </div>
    </div>

    <script>
        function closeCookieModal() {
            document.getElementById('cookieModal').style.display = 'none';
            window.location.href = 'vehicle_rental.php';
        }

        <?php if (isset($_SESSION['show_cookie_popup']) && $_SESSION['show_cookie_popup']): ?>
            document.getElementById('cookieModal').style.display = 'block';
            <?php unset($_SESSION['show_cookie_popup']); ?>
        <?php endif; ?>
    </script>
</body>

</html>