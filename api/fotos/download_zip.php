<?php
require_once __DIR__.'/../config.php';

$body       = body();
$galeria_id = (int)($body['galeria_id'] ?? 0);
$foto_ids   = $body['foto_ids'] ?? [];

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Verifica se galeria permite download_all
$gal = db()->prepare("SELECT download_all, entrega_em_alta, max_selecao, nome FROM galerias WHERE id = ? LIMIT 1");
$gal->execute([$galeria_id]);
$g = $gal->fetch();
if (!$g || (!$g['download_all'] && !$g['entrega_em_alta']))
    json_out(['status'=>'erro','mensagem'=>'Download em ZIP não habilitado.'], 403);

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

// Verifica limite (conta como 1 download por ZIP)
$max = (int)$g['max_selecao'];
if ($max > 0) {
    $dl_count = $_SESSION['dl_count'][$galeria_id] ?? 0;
    if ($dl_count >= $max)
        json_out(['status'=>'erro','mensagem'=>"Limite de $max downloads atingido."], 403);
    $_SESSION['dl_count'][$galeria_id] = $dl_count + 1;
}

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
readfile($tmpZip);
unlink($tmpZip);
exit;
