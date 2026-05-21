<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
logout();
header('Location: index.php');
exit;
?>