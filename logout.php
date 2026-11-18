<?php
require_once __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(auth_csrf(), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(405); exit('Method not allowed');
}
$_SESSION = [];
session_regenerate_id(true);
header('Location: /login.php'); exit;
