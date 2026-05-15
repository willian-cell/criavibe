<?php
require_once __DIR__.'/../config.php';

$u = require_fotografo();
$body = body();
$galeria_id = (int)($body['galeria_id'] ?? 0);
$items = $body['items'] ?? [];

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatorio.'], 400);
if (!is_array($items) || !$items) json_out(['status'=>'erro','mensagem'=>'Nenhum upload confirmado.'], 400);
if (count($items) > 200) json_out(['status'=>'erro','mensagem'=>'Confirme no maximo 200 fotos por chamada.'], 400);

$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria nao encontrada.'], 404);

$allowedPrefix = "galerias/{$galeria_id}/";
try {
    $db = db();
    $db->beginTransaction();

    $ord = $db->prepare("SELECT COALESCE(MAX(ordem),0) FROM imagens WHERE galeria_id=?");
    $ord->execute([$galeria_id]);
    $ordem = (int)$ord->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO imagens (galeria_id,nome_arquivo,caminho_arquivo,tamanho_bytes,ordem)
        VALUES (?,?,?,?,?)
    ");

    $registradas = 0;
    foreach ($items as $item) {
        $r2Path = trim((string)($item['r2_path'] ?? ''));
        $name = trim((string)($item['original_name'] ?? ''));
        $size = (int)($item['size'] ?? 0);

        if (!$r2Path || strpos($r2Path, $allowedPrefix) !== 0 || !$name) {
            continue;
        }

        $publicUrl = rtrim(R2_PUBLIC_URL, '/') . '/' . ltrim($r2Path, '/');
        $ordem++;
        $stmt->execute([$galeria_id, $name, $publicUrl, max(0, $size), $ordem]);
        $registradas++;
    }

    $db->commit();
    json_out(['status'=>'ok','registradas'=>$registradas]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Erro ao confirmar uploads diretos: ' . $e->getMessage());
    json_out([
        'status'=>'erro',
        'mensagem'=>'Erro ao registrar fotos enviadas: '.$e->getMessage()
    ], 500);
}
