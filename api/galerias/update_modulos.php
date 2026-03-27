<?php
require_once __DIR__.'/../config.php';
$u    = require_fotografo();
$body = body();

$id = (int)($body['id'] ?? 0);
if (!$id) json_out(['status'=>'erro','mensagem'=>'ID inválido.'], 400);

// Segurança: verificar que a galeria pertence ao fotógrafo logado
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

$entrega_em_alta = isset($body['entrega_em_alta']) ? (int)(bool)$body['entrega_em_alta'] : 0;
$selecao_ativa   = isset($body['selecao_ativa'])   ? (int)(bool)$body['selecao_ativa']   : 0;
$musicas_ativas  = isset($body['musicas_ativas'])   ? (int)(bool)$body['musicas_ativas']  : 0;

$stmt = db()->prepare("
    UPDATE galerias
    SET entrega_em_alta=?, selecao_ativa=?, musicas_ativas=?
    WHERE id=?
");
$stmt->execute([$entrega_em_alta, $selecao_ativa, $musicas_ativas, $id]);

json_out(['status'=>'ok','mensagem'=>'Módulos atualizados.']);
