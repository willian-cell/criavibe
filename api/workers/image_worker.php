<?php
// Worker simples para processar jobs de imagem da fila Redis
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/R2Storage.php';

function logmsg($m) { error_log('[image_worker] '.$m); }

try {
    require_once __DIR__ . '/../lib/Queue.php';
    $q = new Queue();
} catch (Throwable $e) {
    logmsg('Erro iniciando fila: '.$e->getMessage());
    exit(1);
}

$r2 = new R2Storage(R2_ACCESS_KEY, R2_SECRET_KEY, R2_BUCKET, R2_ENDPOINT);

logmsg('Worker iniciado, esperando jobs...');
while (true) {
    $job = $q->pop(WORKER_QUEUE_NAME, WORKER_POLL_TIMEOUT);
    if (!$job) continue; // timeout, loop

    if (!isset($job['type']) || $job['type'] !== 'generate_derivatives') {
        logmsg('Job desconhecido, pulando.');
        continue;
    }

    $galeria_id = $job['galeria_id'] ?? null;
    $r2_path = $job['r2_path'] ?? null;
    $public_url = $job['public_url'] ?? null;
    $orig_name = $job['original_name'] ?? '';
    $sizes = $job['sizes'] ?? ['small'=>200,'medium'=>800,'large'=>1600];

    if (!$r2_path && $public_url) {
        // tentar derivar r2_path a partir da public_url
        $r2_path = preg_replace('#^'.preg_quote(rtrim(R2_PUBLIC_URL,'/'), '#').'/?#', '', $public_url);
    }

    if (!$r2_path) { logmsg('Sem r2_path; job ignorado'); continue; }

    try {
        // Baixar o arquivo original via public_url
        $tmp = tempnam(sys_get_temp_dir(), 'cv_img_');
        $content = @file_get_contents($public_url);
        if ($content === false) { logmsg('Falha ao baixar: '.$public_url); continue; }
        file_put_contents($tmp, $content);

        // Gerar cada derivado
        foreach ($sizes as $label => $w) {
            $ext = pathinfo($r2_path, PATHINFO_EXTENSION) ?: 'jpg';
            $base = pathinfo($r2_path, PATHINFO_BASENAME);
            $dir = pathinfo($r2_path, PATHINFO_DIRNAME);
            $derPath = $dir . '/derivados/' . $label . '_' . $base;

            // Tentar usar Imagick
            $outTmp = tempnam(sys_get_temp_dir(), 'cv_der_');
            if (class_exists('Imagick')) {
                $img = new Imagick($tmp);
                $img->setImageColorspace(Imagick::COLORSPACE_RGB);
                $img->thumbnailImage($w, 0);
                $img->setImageFormat('jpeg');
                $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality(82);
                $img->stripImage();
                $img->writeImage($outTmp);
                $img->clear();
                $img->destroy();
            } else {
                // Fallback GD
                $src = imagecreatefromstring($content);
                if ($src === false) { logmsg('GD falhou ao criar imagem'); continue; }
                $sw = imagesx($src);
                $sh = imagesy($src);
                $nw = $w;
                $nh = intval($sh * ($nw / $sw));
                $dst = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($dst, $src, 0,0,0,0,$nw,$nh,$sw,$sh);
                imagejpeg($dst, $outTmp, 82);
                imagedestroy($dst);
                imagedestroy($src);
            }

            // Upload para R2
            $mtype = 'image/jpeg';
            $ok = $r2->upload($outTmp, $derPath, $mtype);
            if ($ok) {
                logmsg('Derivado enviado: '.$derPath);
                // Opcional: atualizar DB com caminho derivado
                try {
                    $db = db();
                    $public = rtrim(R2_PUBLIC_URL, '/') . '/' . ltrim($derPath, '/');
                    $col = 'caminho_thumb_'.$label;
                    // Se coluna não existir, criar dinamicamente (só na primeira execução)
                    $stmt = $db->prepare("SHOW COLUMNS FROM imagens LIKE ?");
                    $stmt->execute([$col]);
                    if (!$stmt->fetch()) {
                        $db->exec("ALTER TABLE imagens ADD COLUMN `$col` VARCHAR(1024) DEFAULT NULL");
                    }
                    $upd = $db->prepare("UPDATE imagens SET `$col` = ? WHERE caminho_arquivo = ? AND galeria_id = ?");
                    $upd->execute([$public, rtrim(R2_PUBLIC_URL, '/') . '/' . ltrim($r2_path, '/'), $galeria_id]);
                } catch (Throwable $e) {
                    logmsg('Erro ao atualizar DB: '.$e->getMessage());
                }
            } else {
                logmsg('Falha no upload do derivado: '.$derPath);
            }

            @unlink($outTmp);
        }

        @unlink($tmp);
    } catch (Throwable $e) {
        logmsg('Erro processando job: '.$e->getMessage());
        continue;
    }
}
