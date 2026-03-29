<?php
require_once __DIR__.'/../config.php';
// Fotos são acessíveis publicamente se você tem o galeria_id (via token da galeria)
// Sem exigir login para permitir que clientes vejam as fotos

$galeria_id = (int)($_GET['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Tenta adicionar a coluna is_capa e downloads caso ainda não existam (Lazy migration)
try {
    db()->exec("ALTER TABLE imagens ADD COLUMN is_capa TINYINT(1) DEFAULT 0");
    db()->exec("ALTER TABLE imagens ADD COLUMN downloads INT DEFAULT 0");
} catch (Exception $e) {}

$ordem = $_GET['ordem'] ?? 'ordem';
$col   = $ordem === 'data' ? 'enviado_em' : 'ordem';

$stmt = db()->prepare("SELECT * FROM imagens WHERE galeria_id=? ORDER BY $col ASC");
$stmt->execute([$galeria_id]);
json_out(['status'=>'ok','fotos'=>$stmt->fetchAll()]);
