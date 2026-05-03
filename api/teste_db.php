<?php
require_once __DIR__ . '/config.php';

try {
    $stmt = db()->query("SELECT 1");
    echo "<h1>Conexão com Banco de Dados: SUCESSO!</h1>";
} catch (Exception $e) {
    echo "<h1>Erro na Conexão com Banco:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
