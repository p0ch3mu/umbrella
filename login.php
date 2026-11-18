<?php
require_once __DIR__ . '/auth.php';
if (auth_user()) { header('Location: /index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(auth_csrf(), (string)($_POST['csrf'] ?? ''))) {
        $error = 'Обновите страницу и повторите попытку.';
    } elseif (($_SESSION['login_block_until'] ?? 0) > time()) {
        $error = 'Слишком много попыток. Повторите позже.';
    } else {
        $name = trim((string)($_POST['username'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        require_once __DIR__ . '/config.php';
        $q = db()->prepare('SELECT id, password_hash FROM app_users WHERE username = ? AND active = TRUE');
        $q->execute([$name]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($pass, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['auth_id'] = (int)$row['id'];
            unset($_SESSION['login_failures'], $_SESSION['login_block_until']);
            header('Location: /index.php'); exit;
        }
        $_SESSION['login_failures'] = (int)($_SESSION['login_failures'] ?? 0) + 1;
        if ($_SESSION['login_failures'] >= 5) {
            $_SESSION['login_block_until'] = time() + 300;
            $_SESSION['login_failures'] = 0;
        }
        $error = 'Неверный логин или пароль.';
    }
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Вход — ТехУчёт</title><style>body{font:16px system-ui,sans-serif;background:#eef2f6;display:grid;place-items:center;min-height:90vh}.panel{background:white;padding:32px;border-radius:12px;box-shadow:0 4px 20px #0002;width:min(360px,90vw)}label{display:block;margin:16px 0 6px}input{box-sizing:border-box;width:100%;padding:11px;border:1px solid #aaa;border-radius:6px}button{width:100%;padding:12px;margin-top:20px;background:#173b67;color:white;border:0;border-radius:6px;cursor:pointer}.error{color:#ad1823}</style></head><body><main class="panel"><h1>ТехУчёт</h1><p>Вход в систему</p><?php if($error): ?><p class="error"><?=h($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=h(auth_csrf())?>"><label for="u">Логин</label><input id="u" name="username" autocomplete="username" required><label for="p">Пароль</label><input id="p" name="password" type="password" autocomplete="current-password" required><button type="submit">Войти</button></form></main></body></html>
