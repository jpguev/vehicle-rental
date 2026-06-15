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
    <title>Logged Out | Vehicle Rental</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 28%),
                        radial-gradient(circle at bottom right, rgba(255,255,255,0.08), transparent 24%),
                        linear-gradient(135deg, #667eea 0%, #764ba2 48%, #3b4cc0 100%);
            background-size: 220% 220%;
            animation: gradientBG 14s ease infinite;
        }

        .logout-card {
            background: rgba(255,255,255,0.95);
            border-radius: 24px;
            padding: 40px 36px;
            text-align: center;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.18);
            max-width: 480px;
            width: 100%;
            margin: 16px;
            animation: fadeInUp 0.9s ease both;
        }

        h1 {
            color: #333;
            margin-bottom: 14px;
            font-size: 2rem;
        }

        p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .button {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 999px;
            background: #667eea;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(102, 126, 234, 0.35);
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <h1>Logged Out</h1>
        <p>Your session has ended successfully. Redirecting to login page in a moment.</p>
        <a class="button" href="login.php">Go to Login</a>
    </div>
    <script>
        
    </script>
</body>
</html>
