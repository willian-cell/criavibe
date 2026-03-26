<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();

$galeria_id = (int)($_POST['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Verificar dono
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

// Opção 1: upload de arquivo MP3
$file = $_FILES['musica'] ?? null;
if ($file && $file['error'] === UPLOAD_ERR_OK) {
    $allowed = ['audio/mpeg','audio/mp3','audio/ogg','audio/wav','audio/x-m4a'];
    if (!in_array($file['type'], $allowed))
        json_out(['status'=>'erro','mensagem'=>'Formato não suportado.'], 400);

    $dir = __DIR__.'/../../uploads/musicas/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('mus_', true).'.'.$ext;
    $dest     = $dir.$filename;
    move_uploaded_file($file['tmp_name'], $dest);

    $ord = db()->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM musicas WHERE galeria_id=?");
    $ord->execute([$galeria_id]);
    $ordem = (int)$ord->fetchColumn();

    $nome_exibicao = pathinfo($file['name'], PATHINFO_FILENAME);
    $stmt = db()->prepare("INSERT INTO musicas (galeria_id,nome_arquivo,nome_exibicao,caminho_arquivo,ordem) VALUES (?,?,?,?,?)");
    $stmt->execute([$galeria_id, $file['name'], $nome_exibicao, 'uploads/musicas/'.$filename, $ordem]);
    json_out(['status'=>'ok','mensagem'=>'Música adicionada.']);
}

// Opção 2: URL YouTube
$yt_url   = trim($_POST['yt_url'] ?? '');
$yt_nome  = trim($_POST['yt_nome'] ?? 'YouTube');
if ($yt_url) {
    if (!preg_match('/youtube\.com|youtu\.be/', $yt_url))
        json_out(['status'=>'erro','mensagem'=>'URL inválida. Use YouTube.'], 400);

    $ord = db()->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM musicas WHERE galeria_id=?");
    $ord->execute([$galeria_id]);
    $ordem = (int)$ord->fetchColumn();

    $stmt = db()->prepare("INSERT INTO musicas (galeria_id,nome_arquivo,nome_exibicao,caminho_arquivo,ordem) VALUES (?,?,?,?,?)");
    $stmt->execute([$galeria_id, 'youtube', $yt_nome, $yt_url, $ordem]);
    json_out(['status'=>'ok','mensagem'=>'YouTube adicionado.']);
}

json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo ou URL fornecido.'], 400);
