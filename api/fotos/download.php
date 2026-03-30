<?php
require_once __DIR__.'/../config.php';

$foto_id    = (int)($_GET['foto_id'] ?? 0);
$token      = $_GET['token'] ?? '';

if (!$foto_id) json_out(['status'=>'erro','mensagem'=>'foto_id obrigatório.'], 400);

// Busca a foto
$stmt = db()->prepare("SELECT * FROM imagens WHERE id = ? LIMIT 1");
$stmt->execute([$foto_id]);
$foto = $stmt->fetch();
if (!$foto) json_out(['status'=>'erro','mensagem'=>'Foto não encontrada.'], 404);

$gid = $foto['galeria_id'];

// Busca galeria — usa entrega_em_alta (campo real do banco) e separa os limites
$gal = db()->prepare("SELECT entrega_em_alta, max_downloads, dl_count, nome FROM galerias WHERE id = ? LIMIT 1");
$gal->execute([$gid]);
$g = $gal->fetch();
if (!$g) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

// Verifica se downloads estão habilitados
if (!$g['entrega_em_alta'])
    json_out(['status'=>'erro','mensagem'=>'Downloads não habilitados nesta galeria.'], 403);

// Verifica acesso (sessão OU token)
$acesso = !empty($_SESSION['galeria_access'][$gid]);
if (!$acesso && $token) {
    $chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND link_token=? LIMIT 1");
    $chk->execute([$gid, $token]);
    if ($chk->fetch()) { $acesso = true; $_SESSION['galeria_access'][$gid] = true; }
}
if (!$acesso) json_out(['status'=>'erro','mensagem'=>'Sem acesso à galeria.'], 403);

// Verifica limite de downloads (persistente no banco) usando max_downloads
$max      = (int)($g['max_downloads'] ?? 0);
$dl_count = (int)($g['dl_count'] ?? 0);
if ($max > 0 && $dl_count >= $max)
    json_out(['status'=>'erro','mensagem'=>"Limite de $max downloads atingido para esta galeria."], 403);

// Incrementa contador na imagem específica
db()->prepare("UPDATE imagens SET downloads = downloads + 1 WHERE id = ?")->execute([$foto_id]);

// Incrementa contador no banco (persistente na galeria geral)
db()->prepare("UPDATE galerias SET dl_count = dl_count + 1 WHERE id = ?")->execute([$gid]);

// Serve o arquivo
$path = __DIR__.'/../../'.$foto['caminho_arquivo'];
if (!file_exists($path)) json_out(['status'=>'erro','mensagem'=>'Arquivo não encontrado no servidor.'], 404);

$nome = $foto['nome_arquivo'] ?: basename($path);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$nome.'"');
header('Content-Length: '.filesize($path));
header('X-Downloads-Used: '.($dl_count + 1));
header('X-Downloads-Max: '.$max);
readfile($path);
exit;
