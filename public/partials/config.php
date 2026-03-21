<?php
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic error handling and environment setup
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Application settings
$baseURL = "https://pro.curaproxclub.com/";

// Environment loader
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found: ' . $path);
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }
}

// Database connection
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $envPath = dirname(__DIR__) . '/../.env';
        loadEnv($envPath);
        
        $host = $_ENV['DB_HOST'] ?? '';
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    
    return $pdo;
}

// Authentication check function
function requireAuth() {
    global $pdo, $currentProfile, $currentProfileId;
    
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }
    
    // Check if user has active session
    if (isset($_SESSION['profile_id']) && isset($_SESSION['session_token'])) {
        $stmt = $pdo->prepare('SELECT * FROM profile WHERE id = ? AND session_token = ? AND session_expires > NOW()');
        $stmt->execute([$_SESSION['profile_id'], $_SESSION['session_token']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $currentProfile = $profile;
            $currentProfileId = $profile['id'];
            return true;
        }
    }
    
    // Check cookie for persistent session
    if (isset($_COOKIE['session_token'])) {
        $stmt = $pdo->prepare('SELECT * FROM profile WHERE session_token = ? AND session_expires > NOW()');
        $stmt->execute([$_COOKIE['session_token']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $_SESSION['profile_id'] = $profile['id'];
            $_SESSION['session_token'] = $profile['session_token'];
            $_SESSION['email'] = $profile['email'];
            $_SESSION['first_name'] = $profile['first_name'];
            $_SESSION['last_name'] = $profile['last_name'];
            
            $currentProfile = $profile;
            $currentProfileId = $profile['id'];
            return true;
        }
    }
    
    // No valid session found
    header('Location: account-login.php');
    exit;
}

// Load current user profile (with authentication)
$currentProfileId = null;
$currentProfile = null;
$userTheme = 'dark';

// Only require auth if not on public pages
$publicPages = ['account-login.php', 'account-register.php', 'account-verify.php'];
$currentPage = basename($_SERVER['PHP_SELF']);

if (!in_array($currentPage, $publicPages)) {
    requireAuth();
}

// Set theme from profile if available
if ($currentProfile) {
    $userTheme = $currentProfile['theme'] ?? 'dark';
}

// Helper variables for templates
$currentUserName = '';
$currentUserAvatar = '';
$currentUserInitials = '';

if ($currentProfile) {
    $currentUserName = htmlspecialchars(($currentProfile['first_name'] ?? '') . ' ' . ($currentProfile['last_name'] ?? ''));
    $currentUserInitials = strtoupper(substr($currentProfile['first_name'] ?? '', 0, 1) . substr($currentProfile['last_name'] ?? '', 0, 1));
    $currentUserAvatar = $currentProfile['avatar'] ? 'uploads/avatars/' . htmlspecialchars($currentProfile['avatar']) : '';
}
