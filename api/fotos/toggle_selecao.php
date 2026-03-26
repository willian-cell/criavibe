<?php
require_once __DIR__.'/../config.php';
require_auth();
$body = body();
$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

$stmt = db()->prepare("UPDATE imagens SET selecionada = NOT selecionada WHERE id=?");
$stmt->execute([$id]);

$sel = db()->prepare("SELECT selecionada FROM imagens WHERE id=?");
$sel->execute([$id]);
json_out(['status'=>'ok','selecionada'=>(bool)$sel->fetchColumn()]);
