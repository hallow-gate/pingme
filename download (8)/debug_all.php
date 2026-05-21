<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';

echo "<h1>PingMe Debug Information</h1>";

// Session info
echo "<h2>Session Info</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "CSRF Token in Session: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>";

// Database connection
echo "<h2>Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Database connected. Users count: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Public users count
echo "<h2>Public Users</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_private = 0");
$result = $stmt->fetch();
echo "Public users count: " . $result['count'] . "<br>";

// Test CSRF with actual POST
echo "<h2>CSRF Test</h2>";
echo '<form method="POST" action="">';
echo csrf_field();
echo '<button type="submit">Test CSRF Form</button>';
echo '</form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    echo "<span style='color:green'>✅ CSRF validation passed via form!</span><br>";
}

// Test JSON CSRF
echo "<h2>JSON CSRF Test</h2>";
echo '<button onclick="testJsonCsrf()">Test JSON CSRF</button><br>';
echo '<div id="jsonResult"></div>';
?>
<script>
function testJsonCsrf() {
    const token = '<?= csrf_token() ?>';
    fetch('test_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: token, action: 'test' })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('jsonResult').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(err => {
        document.getElementById('jsonResult').innerHTML = 'Error: ' + err;
    });
}
</script>