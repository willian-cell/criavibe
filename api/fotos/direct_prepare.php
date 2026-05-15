<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/R2Presigner.php';

$u = require_fotografo();
$body = body();
$galeria_id = (int)($body['galeria_id'] ?? 0);
$files = $body['files'] ?? [];

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatorio.'], 400);
if (!is_array($files) || !$files) json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo informado.'], 400);
if (count($files) > 100) json_out(['status'=>'erro','mensagem'=>'Envie no maximo 100 arquivos por preparacao.'], 400);

$chk = db()->prepare("SELECT id FROM galerias WHERE id=? AND usuario_email=? LIMIT 1");
$chk->execute([$galeria_id, $u['email']]);
if (!$chk->fetch()) json_out(['status'=>'erro','mensagem'=>'Galeria nao encontrada.'], 404);

$missing = [];
if (!R2_ACCESS_KEY) $missing[] = 'R2_ACCESS_KEY_ID';
if (!R2_SECRET_KEY) $missing[] = 'R2_SECRET_KEY';
if (!R2_BUCKET) $missing[] = 'R2_BUCKET_NAME';
if (!R2_ENDPOINT) $missing[] = 'R2_ACCOUNT_ID';
if (!R2_PUBLIC_URL) $missing[] = 'R2_PUBLIC_URL';
if ($missing) {
    json_out([
        'status'=>'erro',
        'mensagem'=>'Configuracao R2 incompleta: '.implode(', ', $missing).'.'
    ], 500);
}

$allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif'];
$presigner = new R2Presigner(R2_ACCESS_KEY, R2_SECRET_KEY, R2_BUCKET, R2_ENDPOINT);
$uploads = [];

foreach ($files as $idx => $file) {
    $name = trim((string)($file['name'] ?? ''));
    $type = strtolower(trim((string)($file['type'] ?? '')));
    $size = (int)($file['size'] ?? 0);

    if (!$name || !isset($allowed[$type]) || $size <= 0) {
        continue;
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!$ext || strlen($ext) > 5) $ext = $allowed[$type];

    $filename = uniqid('foto_', true).'.'.$ext;
    $r2Path = "galerias/{$galeria_id}/{$filename}";

    $uploads[] = [
        'client_id' => (string)$idx,
        'original_name' => $name,
        'mime_type' => $type,
        'size' => $size,
        'r2_path' => $r2Path,
        'public_url' => R2_PUBLIC_URL . '/' . $r2Path,
        'upload_url' => $presigner->signedPutUrl($r2Path, 900),
    ];
}

if (!$uploads) json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo valido para upload.'], 400);

json_out(['status'=>'ok','uploads'=>$uploads]);
