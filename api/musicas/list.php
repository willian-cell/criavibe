<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();

$galeria_id = (int)($_GET['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

$stmt = db()->prepare("SELECT * FROM musicas WHERE galeria_id=? ORDER BY ordem ASC");
$stmt->execute([$galeria_id]);
json_out(['status'=>'ok','musicas'=>$stmt->fetchAll()]);
