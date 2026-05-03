<?php
require_once __DIR__ . '/config.php';

function mask($str) {
    if (!$str) return "Vazio ❌";
    return substr($str, 0, 4) . "..." . substr($str, -4) . " (OK ✅)";
}

echo "<h1>Diagnóstico de Ambiente</h1>";
echo "<ul>";
echo "<li><b>R2_ACCOUNT_ID:</b> " . mask(getenv('R2_ACCOUNT_ID') ?: ($_ENV['R2_ACCOUNT_ID'] ?? ($_SERVER['R2_ACCOUNT_ID'] ?? ''))) . "</li>";
echo "<li><b>R2_BUCKET_NAME:</b> " . mask(getenv('R2_BUCKET_NAME') ?: ($_ENV['R2_BUCKET_NAME'] ?? ($_SERVER['R2_BUCKET_NAME'] ?? ''))) . "</li>";
echo "<li><b>R2_ACCESS_KEY_ID:</b> " . mask(getenv('R2_ACCESS_KEY_ID') ?: ($_ENV['R2_ACCESS_KEY_ID'] ?? ($_SERVER['R2_ACCESS_KEY_ID'] ?? ''))) . "</li>";
echo "</ul>";

$logFile = __DIR__ . '/error.log';

echo "<h1>Logs de Erro do Sistema</h1>";

if (!file_exists($logFile)) {
    echo "<p>Arquivo não existia. Tentando criar...</p>";
    if (@file_put_contents($logFile, "--- Log Inicializado em " . date('Y-m-d H:i:s') . " ---\n")) {
        echo "<p style='color:green'>Sucesso! Arquivo criado. Atualize a página.</p>";
    } else {
        echo "<p style='color:red'>ERRO: O servidor não tem permissão para escrever em: " . __DIR__ . "</p>";
    }
} else {
    echo "<p>Tentando escrever linha de teste...</p>";
    @file_put_contents($logFile, "Teste de Escrita: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo "<pre style='background:#000; color:#0f0; padding:15px; border-radius:5px;'>";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>";
}
