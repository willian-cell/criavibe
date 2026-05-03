<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/R2Storage.php';

// Ativar erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Diagnóstico R2</h1>";

$r2 = new R2Storage(R2_ACCESS_KEY, R2_SECRET_KEY, R2_BUCKET, R2_ENDPOINT);

echo "<p>Endpoint: " . R2_ENDPOINT . "</p>";
echo "<p>Bucket: " . R2_BUCKET . "</p>";

$testContent = "Teste de conexão " . date('Y-m-d H:i:s');
$testPath = "diagnostico/teste_" . time() . ".txt";

echo "<p>Tentando upload de arquivo de teste: $testPath</p>";

// Simular um arquivo temporário
$tmpFile = tempnam(sys_get_temp_dir(), 'r2test');
file_put_contents($tmpFile, $testContent);

$success = $r2->upload($tmpFile, $testPath, 'text/plain');

if ($success) {
    echo "<h2 style='color:green'>Sucesso! O upload funcionou.</h2>";
    echo "<p>URL pública: " . R2_PUBLIC_URL . "/" . $testPath . "</p>";
} else {
    echo "<h2 style='color:red'>Falha no upload!</h2>";
    echo "<p>Verifique as credenciais no arquivo .env e as permissões do bucket no Cloudflare.</p>";
}

unlink($tmpFile);
