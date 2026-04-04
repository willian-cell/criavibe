<?php
require_once __DIR__.'/config.php';

// Script seguro de migração.
// IMPORTANTE: Deve ser executado apenas pelo Admin via requisição direta se houver autenticação (ou no terminal).
// Por segurança, vamos exigir o cookie de admin se for via web (se existir essa distinção)
// ou simplesmente checar se o tipo é admin (ou fotógrafo se num sistema monousuário).
$u = me();
if (!$u || !in_array($u['tipo'], ['admin', 'fotografo'])) {
    json_out(['status' => 'erro', 'mensagem' => 'Acesso negado para migrações.'], 403);
}

try {
    $db = db();

    // Migrações em Galerias
    $db->exec("ALTER TABLE galerias ADD COLUMN IF NOT EXISTS max_downloads INT DEFAULT 0");
    $db->exec("ALTER TABLE galerias ADD COLUMN IF NOT EXISTS max_selecao INT DEFAULT 0");
    $db->exec("ALTER TABLE galerias ADD COLUMN IF NOT EXISTS dl_count INT DEFAULT 0");

    // Migrações em Imagens
    $db->exec("ALTER TABLE imagens ADD COLUMN IF NOT EXISTS is_capa TINYINT(1) DEFAULT 0");
    $db->exec("ALTER TABLE imagens ADD COLUMN IF NOT EXISTS downloads INT DEFAULT 0");

    json_out(['status' => 'ok', 'mensagem' => 'Migrações verificadas e estruturadas com sucesso.']);
} catch (PDOException $e) {
    // Para versões do MySQL/MariaDB que não suportam IF NOT EXISTS no ADD COLUMN (MariaDB < 10.0.2 e MySQL não tem), 
    // a exceção "Duplicate column name" (1060) é esperada e ignorada:
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1060) {
        json_out(['status' => 'ok', 'mensagem' => 'Migrações ignoradas, as colunas já existem.']);
    } else {
        json_out(['status' => 'erro', 'mensagem' => 'Erro na migração: ' . $e->getMessage()]);
    }
}
