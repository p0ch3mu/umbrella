<?php
require_once __DIR__ . '/config.php';
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('Некорректный ID'); }
$s=db()->prepare('SELECT * FROM invoices WHERE id=?'); $s->execute([$id]); $inv=$s->fetch();
if (!$inv) { http_response_code(404); exit('Накладная не найдена'); }
$s=db()->prepare('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id'); $s->execute([$id]); $items=$s->fetchAll();
$date=date('d.m.Y',strtotime($inv['invoice_date']));
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Накладная №<?=h($inv['invoice_number'])?></title><style>
@page{size:A4 landscape;margin:12mm}body{font:12pt 'Times New Roman',serif;color:#000;margin:12px}h2{text-align:center;font-size:16pt;margin:8px 0 4px}.subtitle{text-align:center;margin-bottom:18px}table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{border:1px solid black;padding:5px;vertical-align:top;overflow-wrap:anywhere}th{font-size:10pt}td{font-size:11pt}.section{font-weight:bold;margin:18px 0 8px}.lines{white-space:pre-line;min-height:65px}.signature{display:flex;justify-content:space-between;gap:24px;margin-top:26px}.signature>div{width:50%}.line{border-bottom:1px solid black;display:inline-block;width:50%;height:18px}.toolbar{font:14px Arial;margin-bottom:15px}.toolbar button{padding:9px 15px}tr{break-inside:avoid}@media print{.toolbar{display:none}body{margin:0}}
</style></head><body><div class="toolbar"><a href="invoices.php">← Накладные</a> &nbsp; <button onclick="window.print()">Печать / PDF</button></div>
<h2>Накладная № <?=h($inv['invoice_number'])?> от <?=h($date)?></h2><div class="subtitle">на внутреннее перемещение средств вычислительной техники</div>
<table><thead><tr><th style="width:10%">Тип перемещения</th><th style="width:10%">Откуда (цех/отд)</th><th style="width:10%">Куда (цех/отд)</th><th style="width:12%">Тип оборудования</th><th style="width:27%">Состав / Модель оборудования</th><th style="width:21%">Инв. / серийный номер оборудования</th><th style="width:10%">Имя учётной записи (логин)</th></tr></thead><tbody>
<?php foreach($items as $i): ?><tr><td><?=h($inv['operation'])?></td><td><?=h($inv['from_department'])?></td><td><?=h($inv['to_department'])?></td><td><?=h($i['equipment_type'])?></td><td><?=h($i['model'])?></td><td><?=h($i['inventory_number'])?><?php if($i['serial_number']): ?><div><?=h($i['serial_number'])?></div><?php endif; ?></td><td><?=h($inv['login_name'])?></td></tr><?php endforeach; ?>
</tbody></table>
<div class="section">Состав комплектации:</div><div class="lines"><?=h($inv['kit_details'])?></div>
<div class="section">Примечание:</div><div class="lines"><?=h($inv['note'])?></div>
<div class="signature"><div>Ответственное лицо: <?=h($inv['recipient'])?><p>Подпись: <span class="line"></span></p></div><div>Представитель подразделения: <span class="line"></span><p>Подпись: <span class="line"></span></p></div></div>
</body></html>
