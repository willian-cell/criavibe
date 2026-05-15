<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/Queue.php';

$galeria = $argv[1] ?? null;
$r2path = $argv[2] ?? null;
if (!$galeria || !$r2path) {
    echo "Usage: php enqueue_test_job.php <galeria_id> <r2_path>\nExample: php enqueue_test_job.php 12 'galerias/12/foto_test.jpg'\n";
    exit(1);
}

$q = new Queue();
$job = [
    'type' => 'generate_derivatives',
    'galeria_id' => (int)$galeria,
    'r2_path' => $r2path,
    'public_url' => rtrim(R2_PUBLIC_URL, '/') . '/' . ltrim($r2path, '/'),
    'original_name' => basename($r2path),
    'sizes' => ['small'=>200,'medium'=>800,'large'=>1600]
];

if ($q->push(WORKER_QUEUE_NAME, $job)) {
    echo "Job enfileirado com sucesso.\n";
} else {
    echo "Falha ao enfileirar job.\n";
}
