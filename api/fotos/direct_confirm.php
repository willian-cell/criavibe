<?php
require_once __DIR__.'/../config.php';

$u = require_fotografo();
$body = body();
$galeria_id = (int)($body['galeria_id'] ?? 0);
$items = $body['items'] ?? [];

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatorio.'], 400);
if (!is_array($items) || !$items) json_out(['status'=>'erro','mensagem'=>'Nenhum upload confirmado.'], 400);
// Permitimos confirmações maiores, mas vamos processar em chunks seguros
if (count($items) > 1000) json_out(['status'=>'erro','mensagem'=>'Confirme no maximo 1000 fotos por chamada.'], 400);

$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria nao encontrada.'], 404);

$allowedPrefix = "galerias/{$galeria_id}/";
try {
    $db = db();
    $db->beginTransaction();

    // Pegar a ordem atual uma vez
    $ord = $db->prepare("SELECT COALESCE(MAX(ordem),0) FROM imagens WHERE galeria_id=?");
    $ord->execute([$galeria_id]);
    $ordem = (int)$ord->fetchColumn();

    // Preparar lista de itens válidos e suas URLs públicas
    $toInsert = [];
    $publicUrls = [];
    foreach ($items as $item) {
        $r2Path = trim((string)($item['r2_path'] ?? ''));
        $name = trim((string)($item['original_name'] ?? ''));
        $size = (int)($item['size'] ?? 0);

        if (!$r2Path || strpos($r2Path, $allowedPrefix) !== 0 || !$name) {
            continue;
        }

        $publicUrl = rtrim(R2_PUBLIC_URL, '/') . '/' . ltrim($r2Path, '/');
        $toInsert[] = ['name' => $name, 'r2_path' => $r2Path, 'public_url' => $publicUrl, 'size' => max(0, $size)];
        $publicUrls[] = $publicUrl;
    }

    if (empty($toInsert)) {
        $db->commit();
        json_out(['status'=>'ok','registradas'=>0]);
    }

    // Remover registros já existentes para garantir idempotência
    $existing = [];
    // Consultas parametrizadas em lotes para evitar limites de placeholders
    $chunks = array_chunk($publicUrls, 500);
    foreach ($chunks as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $sel = $db->prepare("SELECT caminho_arquivo FROM imagens WHERE caminho_arquivo IN ($placeholders)");
        $sel->execute($chunk);
        while ($row = $sel->fetch(PDO::FETCH_NUM)) {
            $existing[] = $row[0];
        }
    }

    // Filtrar apenas os novos
    $finalRows = [];
    foreach ($toInsert as $it) {
        if (in_array($it['url'], $existing, true)) continue;
        $ordem++;
        $finalRows[] = [$galeria_id, $it['name'], $it['url'], $it['size'], $ordem];
    }

    $registradas = 0;
    if (!empty($finalRows)) {
        // Inserir em batchs de 500 linhas
        $rowChunks = array_chunk($finalRows, 500);
        foreach ($rowChunks as $rows) {
            $placeholders = [];
            $params = [];
            foreach ($rows as $r) {
                $placeholders[] = '(?,?,?,?,?)';
                foreach ($r as $p) $params[] = $p;
            }
            $sql = 'INSERT INTO imagens (galeria_id,nome_arquivo,caminho_arquivo,tamanho_bytes,ordem) VALUES ' . implode(',', $placeholders);
            $ins = $db->prepare($sql);
            $ins->execute($params);
            $registradas += $ins->rowCount();
        }
    }

    $db->commit();

    // Enfileirar jobs de processamento de imagens (thumbnails/derivados)
    try {
        require_once __DIR__ . '/../lib/Queue.php';
        $q = new Queue();
        // Prepare jobs: use publicUrls paired with finalRows via ordem match
        foreach ($toInsert as $it) {
            // Se foi inserido (não existente previamente)
            $public = $it['public_url'] ?? (rtrim(R2_PUBLIC_URL, '/') . '/' . ltrim($it['r2_path'] ?? '', '/'));
            if (in_array($public, $existing ?? [], true)) continue;
            $job = [
                'type' => 'generate_derivatives',
                'galeria_id' => $galeria_id,
                'r2_path' => $it['r2_path'] ?? null,
                'public_url' => $public,
                'original_name' => $it['name'] ?? '',
                'sizes' => ['small'=>200,'medium'=>800,'large'=>1600]
            ];
            $q->push(WORKER_QUEUE_NAME, $job);
        }
    } catch (Throwable $e) {
        error_log('Falha ao enfileirar jobs: '.$e->getMessage());
    }

    json_out(['status'=>'ok','registradas'=>$registradas]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Erro ao confirmar uploads diretos: ' . $e->getMessage());
    json_out([
        'status'=>'erro',
        'mensagem'=>'Erro ao registrar fotos enviadas: '.$e->getMessage()
    ], 500);
}
