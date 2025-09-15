<?php
// Logout aman via POST
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // kosongkan session
    $_SESSION = [];

    // hapus cookie session kalau ada
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    // hancurkan session
    session_destroy();
}

// setelah logout, arahkan ke login
header("Location: login.php");
exit;
