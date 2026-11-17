<?php
require_once __DIR__ . '/config.php';
$pdo = db();
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function token(): string { if (empty($_SESSION['invoice_token'])) $_SESSION['invoice_token'] = bin2hex(random_bytes(24)); return $_SESSION['invoice_token']; }
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 try {
  if (!hash_equals(token(), (string)($_POST['token'] ?? ''))) throw new RuntimeException('Неверный токен формы. Обновите страницу.');
  $number = trim((string)($_POST['number'] ?? ''));
  $date = (string)($_POST['date'] ?? '');
  $operation = (string)($_POST['operation'] ?? '');
  $from = trim((string)($_POST['from_department'] ?? ''));
  $to = trim((string)($_POST['to_department'] ?? ''));
  $recipient = trim((string)($_POST['recipient'] ?? ''));
  $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['equipment'] ?? [])), fn($n) => $n > 0)));
  if (!$number || strlen($number)>40 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($operation,['Выдача','Возврат','Перемещение'],true) || !$from || !$to || !$recipient || !$ids) throw new RuntimeException('Заполните номер, дату, операцию, отделы, получателя и выберите оборудование.');
  if (count($ids)>100) throw new RuntimeException('Не более 100 единиц оборудования.');
  $pdo->beginTransaction();
  $stmt = $pdo->prepare('INSERT INTO invoices(invoice_number,invoice_date,operation,from_department,to_department,recipient,login_name,kit_details,note) VALUES(?,?,?,?,?,?,?,?,?) RETURNING id');
  $stmt->execute([$number,$date,$operation,$from,$to,$recipient,trim((string)($_POST['login'] ?? '')),trim((string)($_POST['kit_details'] ?? '')),trim((string)($_POST['note'] ?? ''))]);
  $invoiceId = (int)$stmt->fetchColumn();
  $get = $pdo->prepare('SELECT e.id,e.inventory_number,e.brand_model,e.serial_number,t.name AS type_name FROM equipment e JOIN equipment_types t ON t.id=e.type_id WHERE e.id=?');
  $put = $pdo->prepare('INSERT INTO invoice_items(invoice_id,equipment_id,inventory_number,equipment_type,model,serial_number) VALUES(?,?,?,?,?,?)');
  foreach ($ids as $id) {
   $get->execute([$id]); $item = $get->fetch();
   if (!$item) throw new RuntimeException('Оборудование с ID '.$id.' не найдено.');
   $put->execute([$invoiceId,$id,$item['inventory_number'],$item['type_name'],$item['brand_model'],$item['serial_number']]);
  }
  $pdo->commit();
  header('Location: invoice_print.php?id='.$invoiceId); exit;
 } catch (Throwable $ex) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('Invoice create: '.$ex->getMessage());
  $error = $ex instanceof PDOException ? 'Ошибка сохранения. Возможно, номер накладной уже занят; проверьте журнал Apache.' : $ex->getMessage();
 }
}
$equipment = $pdo->query('SELECT e.id,e.inventory_number,e.brand_model,t.name AS type_name FROM equipment e JOIN equipment_types t ON t.id=e.type_id ORDER BY e.inventory_number')->fetchAll();
$departments = $pdo->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$employees = $pdo->query('SELECT full_name FROM employees ORDER BY full_name')->fetchAll(PDO::FETCH_COLUMN);
$invoices = $pdo->query('SELECT id,invoice_number,invoice_date,recipient FROM invoices ORDER BY id DESC LIMIT 100')->fetchAll();
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Накладные — ТехУчёт</title><style>
body{font:16px Arial,sans-serif;background:#f3f5f9;color:#202735;margin:0}main{max-width:1050px;margin:28px auto;padding:0 16px}section{background:white;border-radius:12px;padding:22px;margin:18px 0;box-shadow:0 1px 6px #0001}h1{margin:0 0 12px}label{display:block;font-weight:600;margin:12px 0 5px}input,select,textarea{box-sizing:border-box;width:100%;padding:10px;border:1px solid #cbd1da;border-radius:6px;font:inherit}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}.items{max-height:260px;overflow:auto;border:1px solid #ddd;border-radius:6px;padding:8px}.items label{font-weight:400;margin:4px;padding:6px}.items input{width:auto;margin-right:8px}button{background:#235bce;color:white;padding:12px 18px;border:0;border-radius:7px;cursor:pointer}table{border-collapse:collapse;width:100%}td,th{padding:9px;border-bottom:1px solid #ddd;text-align:left}.error{color:#9d1717;background:#fff1f1;padding:12px}a{color:#235bce}@media(max-width:650px){.grid{grid-template-columns:1fr}}
</style></head><body><main><p><a href="index.php">← В ТехУчёт</a></p><h1>Накладные</h1>
<section><h2>Создать накладную</h2><?php if ($error): ?><p class="error"><?=h($error)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="token" value="<?=h(token())?>"><div class="grid">
<div><label>Номер накладной</label><input name="number" required maxlength="40" placeholder="1198"></div>
<div><label>Дата</label><input name="date" type="date" required value="<?=date('Y-m-d')?>"></div>
<div><label>Тип операции</label><select name="operation"><option>Выдача</option><option>Возврат</option><option>Перемещение</option></select></div>
<div><label>Получатель (ФИО)</label><input name="recipient" list="employees" required><datalist id="employees"><?php foreach($employees as $n): ?><option value="<?=h($n)?>"><?php endforeach; ?></datalist></div>
<div><label>Откуда (цех/отдел)</label><input name="from_department" list="departments" required placeholder="371/СКЛ"></div>
<div><label>Куда (цех/отдел)</label><input name="to_department" list="departments" required placeholder="371/РЗВ"></div>
</div><datalist id="departments"><?php foreach($departments as $d): ?><option value="<?=h($d)?>"><?php endforeach; ?></datalist>
<label>Оборудование (можно выбрать несколько)</label><div class="items"><?php foreach($equipment as $e): ?><label><input type="checkbox" name="equipment[]" value="<?= (int)$e['id']?>"><?=h($e['inventory_number'].' — '.$e['type_name'].' — '.$e['brand_model'])?></label><?php endforeach; ?></div>
<label>Имя учётной записи (необязательно)</label><input name="login" maxlength="100">
<label>Состав комплектации (по одной позиции в строке)</label><textarea name="kit_details" rows="5" placeholder="Системный блок — 1 шт.&#10;Монитор — 1 шт.&#10;Клавиатура — 1 шт.&#10;Мышь — 1 шт."></textarea>
<label>Примечание</label><textarea name="note" rows="3"></textarea><p><button type="submit">Сохранить и открыть печать</button></p></form></section>
<section><h2>Последние накладные</h2><table><thead><tr><th>№</th><th>Дата</th><th>Получатель</th><th>Печать</th></tr></thead><tbody><?php foreach($invoices as $i): ?><tr><td><?=h($i['invoice_number'])?></td><td><?=h($i['invoice_date'])?></td><td><?=h($i['recipient'])?></td><td><a href="invoice_print.php?id=<?=(int)$i['id']?>">Открыть</a></td></tr><?php endforeach; ?></tbody></table></section>
</main></body></html>
