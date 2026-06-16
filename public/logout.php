<?php
session_start();

// 1. Clear all session variables from memory
$_SESSION = array();

// 2. Destroy the session cookie in the user's browser if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Clear the server session file completely
session_destroy();

// 4. Instantly redirect back to the home index page
header("Location: index.php");
exit;
?>