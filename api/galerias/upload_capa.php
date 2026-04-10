<?php
require_once __DIR__.'/../config.php';
$u = require_fotografo();

$galeria_id = (int)($_POST['galeria_id'] ?? 0);
if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatório.'], 400);

// Verificar dono
$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria não encontrada.'], 404);

$file = $_FILES['capa'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    json_out(['status'=>'erro','mensagem'=>'Erro no upload do arquivo (tente uma imagem menor).'], 400);
}

$uploadDir = __DIR__.'/../../uploads/capas/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($file['type'], $allowed)) {
    json_out(['status'=>'erro','mensagem'=>'Tipo de arquivo não permitido. Aceito: JPEG, PNG, WEBP.'], 400);
}

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid('capa_', true).'.'.$ext;
$dest     = $uploadDir.$filename;
$caminho  = 'uploads/capas/'.$filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_out(['status'=>'erro','mensagem'=>'Falha ao salvar a imagem no servidor.'], 500);
}

// Atualizar o banco de dados
$stmt = db()->prepare("UPDATE galerias SET capa_apresentacao = ? WHERE id = ?");
$stmt->execute([$caminho, $galeria_id]);

json_out(['status'=>'ok', 'caminho' => $caminho, 'mensagem'=>'Capa de apresentação enviada com sucesso!']);
