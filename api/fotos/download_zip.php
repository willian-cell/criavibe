<?php
require_once __DIR__.'/../config.php';

$body       = body();
$galeria_id = (int)($body['galeria_id'] ?? 0);
$foto_ids   = $body['foto_ids'] ?? [];
$token      = $body['token'] ?? '';

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Verifica acesso (sessão OU token)
$acesso = !empty($_SESSION['galeria_access'][$galeria_id]);
if (!$acesso && $token) {
    $chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND link_token=? LIMIT 1");
    $chk->execute([$galeria_id, $token]);
    if ($chk->fetch()) { $acesso = true; $_SESSION['galeria_access'][$galeria_id] = true; }
}
if (!$acesso) json_out(['status'=>'erro','mensagem'=>'Sem acesso à galeria.'], 403);

// Busca galeria — usa entrega_em_alta (campo real do banco) e separa os limites
$gal = db()->prepare("SELECT entrega_em_alta, max_downloads, dl_count, nome FROM galerias WHERE id = ? LIMIT 1");
$gal->execute([$galeria_id]);
$g = $gal->fetch();
if (!$g || !$g['entrega_em_alta'])
    json_out(['status'=>'erro','mensagem'=>'Download em ZIP não habilitado nesta galeria.'], 403);

// Busca fotos (todas ou as selecionadas)
if (!empty($foto_ids)) {
    $in = implode(',', array_map('intval', $foto_ids));
    $stmt = db()->query("SELECT * FROM imagens WHERE galeria_id=$galeria_id AND id IN ($in) ORDER BY ordem ASC");
} else {
    $stmt = db()->prepare("SELECT * FROM imagens WHERE galeria_id=? ORDER BY ordem ASC");
    $stmt->execute([$galeria_id]);
}
$fotos = $stmt->fetchAll();
if (!$fotos) json_out(['status'=>'erro','mensagem'=>'Nenhuma foto para baixar.'], 400);

// Verifica limite de downloads (persistente no banco) usando max_downloads
$max      = (int)($g['max_downloads'] ?? 0);
$dl_count = (int)($g['dl_count'] ?? 0);
if ($max > 0 && ($dl_count >= $max))
    json_out(['status'=>'erro','mensagem'=>"Limite de $max downloads atingido para esta galeria."], 403);

// Verifica se a quantidade solicitada agora extrapola o limite
$futuro_dl = $dl_count + 1; // ZIP conta como 1 "sessão de download" ou contabiliza por fotos?
// O sistema parece usar dl_count + count($fotos) na linha 47. Vamos respeitar isso.
if ($max > 0 && ($dl_count + count($fotos) > $max)) {
    json_out(['status'=>'erro','mensagem'=>"Este download excede seu limite restante."], 403);
}

// Incrementa contador no banco pelo número real de fotos baixadas
$qtd_fotos = count($fotos);
db()->prepare("UPDATE galerias SET dl_count = dl_count + ? WHERE id = ?")->execute([$qtd_fotos, $galeria_id]);

// Cria ZIP temporário
$tmpZip = tempnam(sys_get_temp_dir(), 'criavibe_') . '.zip';
$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::CREATE) !== true)
    json_out(['status'=>'erro','mensagem'=>'Erro ao criar ZIP.'], 500);

foreach ($fotos as $f) {
    $path = __DIR__.'/../../'.$f['caminho_arquivo'];
    if (file_exists($path)) {
        $zip->addFile($path, $f['nome_arquivo'] ?: basename($path));
    }
}
$zip->close();

$nome_galeria = preg_replace('/[^a-zA-Z0-9_-]/', '_', $g['nome']);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$nome_galeria.'_fotos.zip"');
header('Content-Length: '.filesize($tmpZip));
header('X-Downloads-Used: '.($dl_count + 1));
header('X-Downloads-Max: '.$max);
readfile($tmpZip);
unlink($tmpZip);
exit;
