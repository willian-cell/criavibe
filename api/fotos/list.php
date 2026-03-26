<?php
require_once __DIR__.'/../config.php';
// Fotos são acessíveis publicamente se você tem o galeria_id (via token da galeria)
// Sem exigir login para permitir que clientes vejam as fotos

$galeria_id = (int)($_GET['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

$ordem = $_GET['ordem'] ?? 'ordem';
$col   = $ordem === 'data' ? 'enviado_em' : 'ordem';

$stmt = db()->prepare("SELECT * FROM imagens WHERE galeria_id=? ORDER BY $col ASC");
$stmt->execute([$galeria_id]);
json_out(['status'=>'ok','fotos'=>$stmt->fetchAll()]);
