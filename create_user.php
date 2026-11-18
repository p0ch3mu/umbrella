<?php
// Использовать ТОЛЬКО из терминала. Не размещать в DocumentRoot.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if ($argc !== 3 || !in_array($argv[2], ['viewer','editor'], true) || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $argv[1])) {
    fwrite(STDERR, "Использование: php create_user.php LOGIN viewer|editor\n"); exit(1);
}
require_once '/var/www/techuchet/config.php';
$username = $argv[1];
$role = $argv[2];
fwrite(STDOUT, "Пароль для $username (ввод будет виден): ");
$password = trim((string)fgets(STDIN));
if (strlen($password) < 12) { fwrite(STDERR, "Нужно не менее 12 символов.\n"); exit(1); }
$q = db()->prepare('INSERT INTO app_users(username,password_hash,role) VALUES(?,?,?) ON CONFLICT(username) DO UPDATE SET password_hash=EXCLUDED.password_hash,role=EXCLUDED.role,active=TRUE');
$q->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
echo "Пользователь сохранён.\n";
