<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();
$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

$chk = db()->prepare("DELETE FROM clientes WHERE id=? AND fotografo_email=?");
$chk->execute([$id, $u['email']]);
json_out(['status'=>'ok']);
