<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: text/plain');
header('Cache-Control: no-cache');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    echo '0';
    exit;
}

try {
    // Check database connection
    if (!$pdo) {
        error_log("Database connection failed in update_activity.php");
        echo '0';
        exit;
    }
    
    // Update with verification
    $updateStmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
    $updateResult = $updateStmt->execute([$_SESSION['user_id']]);
    
    if ($updateResult && $updateStmt->rowCount() > 0) {
        echo '1';
    } else {
        // Try alternative update function
        if (function_exists('updateLastActive')) {
            $result = updateLastActive($pdo, $_SESSION['user_id']);
            echo $result ? '1' : '0';
        } else {
            echo '0';
        }
    }
    
} catch (Exception $e) {
    error_log("update_activity.php Exception: " . $e->getMessage());
    echo '0';
}
?>