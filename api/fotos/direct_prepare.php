<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/R2Presigner.php';

$u = require_fotografo();
$body = body();
$galeria_id = (int)($body['galeria_id'] ?? 0);
$files = $body['files'] ?? [];

if (!$galeria_id) json_out(['status'=>'erro','mensagem'=>'galeria_id obrigatorio.'], 400);
if (!is_array($files) || !$files) json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo informado.'], 400);
if (count($files) > 250) json_out(['status'=>'erro','mensagem'=>'Envie no maximo 250 arquivos por preparacao.'], 400);

// Rate limiting: evitar abuse de preparacao (ex: 10 prepares por minuto)
try {
    require_once __DIR__ . '/../lib/RateLimiter.php';
    $rl = new RateLimiter();
    $key = 'prepare_'.$u['email'];
    if (!$rl->allow($key, 10, 60)) {
        json_out(['status'=>'erro','mensagem'=>'Limite de preparacao atingido. Tente novamente mais tarde.'], 429);
    }
} catch (Throwable $e) {
    // Se RateLimiter falhar, não bloqueia o usuário (falta de Redis, etc.)
}

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

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'image/heic' => 'heic',
    'image/heif' => 'heif',
    'image/avif' => 'avif',
    'image/svg+xml' => 'svg',
    'image/tiff' => 'tiff',
    'image/x-tiff' => 'tiff',
    'image/bmp' => 'bmp',
    'image/x-icon' => 'ico',
    'application/octet-stream' => 'bin',
];
$extensionMap = [
    'heic' => 'image/heic',
    'heif' => 'image/heif',
    'avif' => 'image/avif',
    'svg' => 'image/svg+xml',
    'tiff' => 'image/tiff',
    'tif' => 'image/tiff',
    'bmp' => 'image/bmp',
    'ico' => 'image/x-icon',
    'psd' => 'application/octet-stream',
    'raw' => 'application/octet-stream',
    'cr2' => 'application/octet-stream',
    'nef' => 'application/octet-stream',
    'arw' => 'application/octet-stream',
    'dng' => 'application/octet-stream',
];

$presigner = new R2Presigner(R2_ACCESS_KEY, R2_SECRET_KEY, R2_BUCKET, R2_ENDPOINT);
$uploads = [];

foreach ($files as $idx => $file) {
    $name = trim((string)($file['name'] ?? ''));
    $type = strtolower(trim((string)($file['type'] ?? '')));
    $size = (int)($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ((!$type || !isset($allowed[$type])) && $ext && isset($extensionMap[$ext])) {
        $type = $extensionMap[$ext];
    }

    if (!$name || $size <= 0 || !isset($allowed[$type])) {
        continue;
    }

    if (!$ext || strlen($ext) > 12 || !preg_match('/^[a-z0-9]+$/', $ext)) {
        $ext = $allowed[$type];
    }

    $filename = uniqid('foto_', true).'.'.$ext;
    $r2Path = "galerias/{$galeria_id}/{$filename}";

    $uploads[] = [
        'client_id' => (string)$idx,
        'original_name' => $name,
        'mime_type' => $type,
        'size' => $size,
        'r2_path' => $r2Path,
        'public_url' => R2_PUBLIC_URL . '/' . $r2Path,
        'upload_url' => $presigner->signedPutUrl($r2Path, 900, $type),
    ];
}

if (!$uploads) json_out(['status'=>'erro','mensagem'=>'Nenhum arquivo valido para upload.'], 400);

json_out(['status'=>'ok','uploads'=>$uploads]);
