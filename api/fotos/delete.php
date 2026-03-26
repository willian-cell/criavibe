<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();
$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

$stmt = db()->prepare("SELECT i.*, g.usuario_email FROM imagens i JOIN galerias g ON g.id=i.galeria_id WHERE i.id=? LIMIT 1");
$stmt->execute([$id]);
$img = $stmt->fetch();
if (!$img || $img['usuario_email'] !== $u['email'])
    json_out(['status'=>'erro','mensagem'=>'Imagem não encontrada.'], 404);

$path = __DIR__.'/../../'.$img['caminho_arquivo'];
if (file_exists($path)) unlink($path);
db()->prepare("DELETE FROM imagens WHERE id=?")->execute([$id]);

json_out(['status'=>'ok']);
