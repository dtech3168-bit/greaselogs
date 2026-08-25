<?php
require_once __DIR__ . '/../includes/config.php';

/* Clear all session data */
$_SESSION = [];

/* Destroy session */
session_destroy();

/* Optional: destroy cookie */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

/* Redirect user */
header("Location: login.php");
exit; 
