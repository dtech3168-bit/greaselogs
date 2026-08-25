<?php
require_once __DIR__ . '/../includes/config.php';

/* Destroy all session data */
$_SESSION = [];

/* Destroy session completely */
session_destroy();

/* Optional: destroy cookie */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

/* Redirect to login */
header("Location: login.php");
exit; 
