<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();

$galeria_id = (int)($_POST['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Verificar dono
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

$files = $_FILES['fotos'] ?? null;
if (!$files) json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo enviado.'], 400);

$uploadDir = __DIR__.'/../../uploads/fotos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
$enviadas = 0;
$erros = [];

// Suporte a multiple files
$total = is_array($files['name']) ? count($files['name']) : 1;
for ($i = 0; $i < $total; $i++) {
    $name  = is_array($files['name'])  ? $files['name'][$i]  : $files['name'];
    $tmp   = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $type  = is_array($files['type'])  ? $files['type'][$i]  : $files['type'];
    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
    $size  = is_array($files['size'])  ? $files['size'][$i]  : $files['size'];

    if ($error !== UPLOAD_ERR_OK) { $erros[] = $name; continue; }
    if (!in_array($type, $allowed))  { $erros[] = $name; continue; }

    $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $filename = uniqid('foto_', true).'.'.$ext;
    $dest     = $uploadDir.$filename;
    $caminho  = 'uploads/fotos/'.$filename;

    if (!move_uploaded_file($tmp, $dest)) { $erros[] = $name; continue; }

    // ordenação: próximo número
    $ord = db()->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM imagens WHERE galeria_id=?");
    $ord->execute([$galeria_id]);
    $ordem = (int)$ord->fetchColumn();

    $stmt = db()->prepare("INSERT INTO imagens (galeria_id,nome_arquivo,caminho_arquivo,tamanho_bytes,ordem) VALUES (?,?,?,?,?)");
    $stmt->execute([$galeria_id, $name, $caminho, $size, $ordem]);
    $enviadas++;
}

json_out(['status'=>'ok','enviadas'=>$enviadas,'erros'=>$erros]);
