<?php
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
