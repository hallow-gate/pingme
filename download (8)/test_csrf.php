<?php
require_once 'includes/config.php';
require_once 'includes/csrf.php';

echo "<h1>CSRF Debug</h1>";
echo "Session ID: " . session_id() . "<br>";
echo "CSRF Token in Session: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>";
echo "CSRF Token from function: " . csrf_token() . "<br>";
echo "<hr>";

echo "<h2>Test POST Request</h2>";
echo '<form method="POST" action="test_csrf.php">';
echo csrf_field();
echo '<button type="submit">Test CSRF</button>';
echo '</form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Result:</h3>";
    verify_csrf();
    echo "✅ CSRF validation passed!";
}
?>