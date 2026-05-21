<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env file from current directory
function loadEnv($path) {
    if (!file_exists($path)) {
        // Try to find .env in current or parent directory
        $paths = [
            $path,
            __DIR__ . '/../.env',
            __DIR__ . '/.env',
            dirname(__DIR__) . '/.env',
            $_SERVER['DOCUMENT_ROOT'] . '/.env',
            $_SERVER['DOCUMENT_ROOT'] . '/../.env'
        ];
        
        foreach ($paths as $tryPath) {
            if (file_exists($tryPath)) {
                $path = $tryPath;
                break;
            }
        }
        
        if (!file_exists($path)) {
            // Fallback to hardcoded values if .env not found
            define('DB_HOST', 'sql307.infinityfree.com');
            define('DB_NAME', 'if0_41619065_pingme_core9xai');
            define('DB_USER', 'if0_41619065');
            define('DB_PASS', 'YwfR4ast4Y59');
            return false;
        }
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            if (!isset($_ENV[$name])) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    return true;
}

// Load .env file
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

// Database configuration -优先使用环境变量，否则使用常量
$host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'sql307.infinityfree.com');
$dbname = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'if0_41619065_pingme_core9xai');
$username = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'if0_41619065');
$password = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : 'YwfR4ast4Y59');

try {
    // Use utf8mb4 for full emoji support
    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Set charset to utf8mb4 for emoji support
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET character_set_connection=utf8mb4");
    $pdo->exec("SET character_set_results=utf8mb4");
    $pdo->exec("SET character_set_client=utf8mb4");
    
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please check database configuration.");
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set timezone
$timezone = getenv('TIMEZONE') ?: 'UTC';
date_default_timezone_set($timezone);

// Debug mode
if (getenv('DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set UTF-8 header for all responses
header('Content-Type: text/html; charset=utf-8');
?>