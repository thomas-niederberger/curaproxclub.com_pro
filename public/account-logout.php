<?php
session_start();

require_once __DIR__ . '/../config/config.php';

$pdo = getDbConnection();

// Clear session token from database if user is logged in
if (isset($_SESSION['profile_id'])) {
    $stmt = $pdo->prepare('UPDATE profile SET session_token = NULL, session_expires = NULL WHERE id = ?');
    $stmt->execute([$_SESSION['profile_id']]);
}

// Clear session
session_unset();
session_destroy();

// Clear cookie
setcookie('session_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Redirect to login
header('Location: account-login.php');
exit;
