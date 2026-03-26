<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();
$body = body();
$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

// Buscar e deletar arquivos físicos
$imgs = db()->prepare("SELECT caminho_arquivo FROM imagens WHERE galeria_id=?");
$imgs->execute([$id]);
foreach ($imgs->fetchAll() as $img) {
    $path = __DIR__.'/../../'.$img['caminho_arquivo'];
    if (file_exists($path)) unlink($path);
}

$mus = db()->prepare("SELECT caminho_arquivo FROM musicas WHERE galeria_id=?");
$mus->execute([$id]);
foreach ($mus->fetchAll() as $m) {
    $path = __DIR__.'/../../'.$m['caminho_arquivo'];
    if (file_exists($path)) unlink($path);
}

db()->prepare("DELETE FROM imagens WHERE galeria_id=?")->execute([$id]);
db()->prepare("DELETE FROM musicas WHERE galeria_id=?")->execute([$id]);
db()->prepare("DELETE FROM galerias WHERE id=?")->execute([$id]);

json_out(['status'=>'ok','mensagem'=>'Galeria excluída.']);
