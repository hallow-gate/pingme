<?php
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'CSRF token missing']);
        exit;
    }
    
    if (empty($_SESSION['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Session expired']);
        exit;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    
    return true;
}

function verify_csrf_json() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    $token = isset($input['csrf_token']) ? $input['csrf_token'] : '';
    
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'CSRF token missing in JSON']);
        exit;
    }
    
    if (empty($_SESSION['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Session expired']);
        exit;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    
    return $input;
}
?>