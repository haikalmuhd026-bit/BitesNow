<?php
session_start();

// 1. Clear session array
$_SESSION = [];

// 2. Invalidate session cookie
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

// 3. Destroy session on server
session_destroy();

// 4. Instant redirect back to index.php
header("Location: index.php?status=loggedout");
exit;