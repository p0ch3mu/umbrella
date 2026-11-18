<?php
// Apache auto_prepend_file: серверная проверка на каждом PHP-запросе.
if (PHP_SAPI === 'cli') return;
require_once __DIR__ . '/auth.php';
$script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
if ($script === 'login.php' || $script === 'logout.php') return;
$user = auth_require_login();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET' && $method !== 'HEAD') auth_require_editor();
// Не разрешаем зрителю открывать формы изменения, даже через прямую ссылку.
if ($user['role'] === 'viewer' && in_array($script, ['equipment_form.php','movement_form.php'], true)) auth_require_editor();
// Справочник: просмотр разрешён, форма редактирования запрещена.
if ($user['role'] === 'viewer' && $script === 'directory.php' && isset($_GET['edit'])) auth_require_editor();
