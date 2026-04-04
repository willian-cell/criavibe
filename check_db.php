<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/api/config.php';

try {
    $pdo = db();
    echo "DB CONECTADO COM SUCESSO!";
} catch (PDOException $e) {
    echo "ERRO DE DB: " . $e->getMessage();
} catch (Exception $e) {
    echo "OUTRO ERRO: " . $e->getMessage();
}
?>
