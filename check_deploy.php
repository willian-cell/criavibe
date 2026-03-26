<?php
header('Content-Type: text/plain');
echo "Diagnóstico de Deploy - CriaVibe\n";
echo "Data/Hora Servidor: " . date('Y-m-d H:i:s') . "\n";
echo "Caminho: " . __FILE__ . "\n";

$files = [
    'index.html',
    'api/galerias/public.php',
    'api/fotos/list.php'
];

foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        echo "\n--- $f ---\n";
        echo "Modificado em: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
        echo "Tamanho: " . filesize($path) . " bytes\n";
        // Mostra as primeiras 3 linhas para conferir o código
        $content = file_get_contents($path, false, null, 0, 200);
        echo "Início do conteúdo: " . substr($content, 0, 100) . "...\n";
    } else {
        echo "\n--- $f: NÃO ENCONTRADO ---\n";
    }
}
