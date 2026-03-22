<?php
/**
 * Curaprox Portal Configuration
 * Handles authentication, environment loading, and content parsing
 */

// Load Composer autoloader and import Parsedown at the absolute top
require_once __DIR__ . '/../../vendor/autoload.php';
use Parsedown;

// Prevent multiple loading of the configuration file
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

// Start the session if it hasn't been initialized yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic error handling and environment setup for development
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Application settings for the Curaprox Pro environment
$baseURL = "https://pro.curaproxclub.com/";

// Environment loader function to parse the .env file
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

// Database connection using PDO with the Singleton pattern
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

// Authentication check function to verify active or persistent sessions
function requireAuth() {
    global $pdo, $currentProfile, $currentProfileId;
    
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }
    
    // Check if the user has an active session in the browser
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
    
    // Check for a persistent session via a browser cookie
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
    
    // Redirect to the login page if no valid session is found
    header('Location: account-login.php');
    exit;
}

// Define classes and brand color
require_once __DIR__ . '/classes.php';
$selectedColor = $currentProfile['brand_color'] ?? 'orange';
$theme = new CuraproxTheme($selectedColor);

// Initialize user profile variables and set the default theme
$currentProfileId = null;
$currentProfile = null;
$userTheme = 'dark';

// Define public pages that do not require authentication
$publicPages = ['account-login.php', 'account-register.php', 'account-verify.php'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Enforce authentication unless the current page is in the public list
if (!in_array($currentPage, $publicPages)) {
    requireAuth();
}

// Apply the user's preferred theme if they are logged in
if ($currentProfile) {
    $userTheme = $currentProfile['theme'] ?? 'dark';
}

// Load secondary helper functions and initialize the Markdown parser
require_once __DIR__ . '/../api/functions.php';
$parsedown = new Parsedown();

// Fetch page-specific content from the database based on the current URL
$currentPageData = getPageData();

// Extract display data with fallbacks to prevent undefined variable warnings
$pageHeader           = $currentPageData['header'] ?? '';
$pageName             = $currentPageData['name'] ?? '';
$pageIcon             = $currentPageData['icon'] ?? '';
$pageDescriptionShort = $currentPageData['description_short'] ?? ''; 
$pageDescription      = $currentPageData['description_html'] ?? ''; 
$pageRawContent       = $currentPageData['description_raw'] ?? '';

// Initialize user identity variables for the user interface
$currentUserName = '';
$currentUserAvatar = '';
$currentUserInitials = '';

// Populate user details if a profile is successfully loaded
if (isset($currentProfile) && !empty($currentProfile)) {
    $firstName = $currentProfile['first_name'] ?? '';
    $lastName  = $currentProfile['last_name'] ?? '';
    
    $currentUserName = htmlspecialchars($firstName . ' ' . $lastName);
    
    // Generate initials to be used as a fallback for the avatar image
    $currentUserInitials = strtoupper(
        substr($firstName, 0, 1) . substr($lastName, 0, 1)
    );
    
    $currentUserAvatar = !empty($currentProfile['avatar']) 
        ? 'uploads/avatars/' . htmlspecialchars($currentProfile['avatar']) 
        : '';
}