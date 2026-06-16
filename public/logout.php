<?php
session_start();
unset($_SESSION['authenticated'], $_SESSION['user']);
session_destroy();
setcookie('vehicle_rental_user', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .logout-card { background: white; border-radius: 1.5rem; padding: 3rem; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 440px; width: 100%; margin: 1rem; border: 1px solid #e2e8f0; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { color: #0f172a; margin-bottom: 0.5rem; font-size: 1.75rem; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 2rem; font-size: 1rem; }
        .button { display: inline-block; padding: 0.85rem 2rem; border-radius: 0.75rem; background: #10b981; color: white; text-decoration: none; font-weight: 600; transition: background 0.25s; width: 100%; }
        .button:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="icon">👋</div>
        <h1>Successfully Logged Out</h1>
        <p>Thank you for using EcoTrack. Your session has ended securely. Have a green day!</p>
        <a class="button" href="login.php">Return to Login</a>
    </div>
</body>
</html>