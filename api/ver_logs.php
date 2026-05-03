<?php
$logFile = __DIR__ . '/error.log';

echo "<h1>Logs de Erro do Sistema</h1>";

if (!file_exists($logFile)) {
    echo "<p>Nenhum log encontrado em: $logFile</p>";
} else {
    echo "<pre style='background:#000; color:#0f0; padding:15px; border-radius:5px;'>";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>";
}
