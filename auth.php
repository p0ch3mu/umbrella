<?php
// PHP 7.4+
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
    session_start();
}
function auth_user(): ?array {
    if (empty($_SESSION['auth_id'])) return null;
    require_once __DIR__ . '/config.php';
    $q = db()->prepare('SELECT id, username, role FROM app_users WHERE id = ? AND active = TRUE');
    $q->execute([$_SESSION['auth_id']]);
    $user = $q->fetch(PDO::FETCH_ASSOC);
    if (!$user) { unset($_SESSION['auth_id']); return null; }
    return $user;
}
function auth_require_login(): array {
    $user = auth_user();
    if (!$user) {
        header('Location: /login.php', true, 302);
        exit;
    }
    return $user;
}
function auth_require_editor(): array {
    $user = auth_require_login();
    if ($user['role'] !== 'editor') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Доступ запрещён: нужны права редактирования.');
    }
    return $user;
}
function auth_csrf(): string {
    if (empty($_SESSION['auth_csrf'])) $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['auth_csrf'];
}
