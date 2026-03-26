<?php
require_once __DIR__.'/../config.php';

$foto_id    = (int)($_GET['foto_id'] ?? 0);
$galeria_id = (int)($_GET['galeria_id'] ?? 0);

if (!$foto_id) json_out(['status'=>'erro','mensagem'=>'foto_id obrigatório.'], 400);

// Busca a foto
$stmt = db()->prepare("SELECT * FROM imagens WHERE id = ? LIMIT 1");
$stmt->execute([$foto_id]);
$foto = $stmt->fetch();
if (!$foto) json_out(['status'=>'erro','mensagem'=>'Foto não encontrada.'], 404);

$gid = $foto['galeria_id'];

// Verifica se galeria permite download individual
$gal = db()->prepare("SELECT download_individual, entrega_em_alta, max_selecao FROM galerias WHERE id = ? LIMIT 1");
$gal->execute([$gid]);
$g = $gal->fetch();
if (!$g) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

if (!$g['download_individual'] && !$g['entrega_em_alta'])
    json_out(['status'=>'erro','mensagem'=>'Downloads não habilitados nesta galeria.'], 403);

// Verifica limite de downloads
$max = (int)$g['max_selecao'];
if ($max > 0) {
    $dl_count = $_SESSION['dl_count'][$gid] ?? 0;
    if ($dl_count >= $max)
        json_out(['status'=>'erro','mensagem'=>"Limite de $max downloads atingido."], 403);
    $_SESSION['dl_count'][$gid] = $dl_count + 1;
}

// Registra visualização como download
$viz = db()->prepare("INSERT INTO visualizacoes (galeria_id, ip_hash) VALUES (?,?)");
$viz->execute([$gid, hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '')]);

// Serve o arquivo
$path = __DIR__.'/../../'.$foto['caminho_arquivo'];
if (!file_exists($path)) json_out(['status'=>'erro','mensagem'=>'Arquivo não encontrado no servidor.'], 404);

$nome = $foto['nome_arquivo'] ?: basename($path);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$nome.'"');
header('Content-Length: '.filesize($path));
header('X-Downloads-Used: '.($_SESSION['dl_count'][$gid] ?? 0));
header('X-Downloads-Max: '.$max);
readfile($path);
exit;
