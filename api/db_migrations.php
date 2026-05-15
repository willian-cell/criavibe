<?php
require_once __DIR__.'/config.php';

function table_exists(PDO $db, string $table): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function column_exists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function index_exists(PDO $db, string $table, string $index): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?"
    );
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function add_index_if_missing(PDO $db, string $table, string $indexName, string $definition): void {
    if (!index_exists($db, $table, $indexName)) {
        $db->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($definition)");
    }
}

function add_column_if_missing(PDO $db, string $table, string $column, string $definition): void {
    if (!column_exists($db, $table, $column)) {
        $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

try {
    $db = db();

    $usuariosExiste = table_exists($db, 'usuarios');
    $temUsuarios = false;
    if ($usuariosExiste) {
        $temUsuarios = (int)$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() > 0;
    }

    if ($temUsuarios) {
        $u = me();
        if (!$u || !in_array($u['tipo'], ['admin', 'fotografo'])) {
            json_out(['status' => 'erro', 'mensagem' => 'Acesso negado para migracoes.'], 403);
        }
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(160) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(30) NOT NULL DEFAULT 'fotografo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fotografo_email VARCHAR(190) NOT NULL,
            nome VARCHAR(160) NOT NULL,
            email VARCHAR(190) DEFAULT NULL,
            telefone VARCHAR(40) DEFAULT NULL,
            senha_acesso VARCHAR(40) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_clientes_fotografo (fotografo_email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS galerias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_email VARCHAR(190) NOT NULL,
            cliente_id INT DEFAULT NULL,
            nome VARCHAR(180) NOT NULL,
            descricao TEXT DEFAULT NULL,
            privacidade VARCHAR(20) NOT NULL DEFAULT 'privada',
            senha VARCHAR(255) DEFAULT NULL,
            link_token VARCHAR(128) NOT NULL UNIQUE,
            entrega_em_alta TINYINT(1) NOT NULL DEFAULT 1,
            selecao_ativa TINYINT(1) NOT NULL DEFAULT 1,
            musicas_ativas TINYINT(1) NOT NULL DEFAULT 0,
            max_downloads INT NOT NULL DEFAULT 0,
            max_selecao INT NOT NULL DEFAULT 0,
            dl_count INT NOT NULL DEFAULT 0,
            capa_apresentacao VARCHAR(512) DEFAULT NULL,
            tema VARCHAR(10) NOT NULL DEFAULT 'escuro',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_galerias_usuario (usuario_email),
            INDEX idx_galerias_cliente (cliente_id),
            INDEX idx_galerias_token (link_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS imagens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            galeria_id INT NOT NULL,
            nome_arquivo VARCHAR(255) NOT NULL,
            caminho_arquivo VARCHAR(1024) NOT NULL,
            tamanho_bytes BIGINT DEFAULT 0,
            ordem INT NOT NULL DEFAULT 0,
            selecionada TINYINT(1) NOT NULL DEFAULT 0,
            eh_publica TINYINT(1) NOT NULL DEFAULT 1,
            is_capa TINYINT(1) NOT NULL DEFAULT 0,
            downloads INT NOT NULL DEFAULT 0,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_imagens_galeria (galeria_id),
            INDEX idx_imagens_ordem (ordem)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS musicas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            galeria_id INT NOT NULL,
            nome_arquivo VARCHAR(255) NOT NULL,
            nome_exibicao VARCHAR(255) NOT NULL,
            caminho_arquivo VARCHAR(1024) NOT NULL,
            ordem INT NOT NULL DEFAULT 0,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_musicas_galeria (galeria_id),
            INDEX idx_musicas_ordem (ordem)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    add_column_if_missing($db, 'usuarios', 'criado_em', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    add_column_if_missing($db, 'clientes', 'criado_em', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    add_column_if_missing($db, 'galerias', 'cliente_id', 'INT DEFAULT NULL');
    add_column_if_missing($db, 'galerias', 'entrega_em_alta', 'TINYINT(1) NOT NULL DEFAULT 1');
    add_column_if_missing($db, 'galerias', 'selecao_ativa', 'TINYINT(1) NOT NULL DEFAULT 1');
    add_column_if_missing($db, 'galerias', 'musicas_ativas', 'TINYINT(1) NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'galerias', 'max_downloads', 'INT NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'galerias', 'max_selecao', 'INT NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'galerias', 'dl_count', 'INT NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'galerias', 'capa_apresentacao', 'VARCHAR(512) DEFAULT NULL');
    add_column_if_missing($db, 'galerias', 'tema', "VARCHAR(10) NOT NULL DEFAULT 'escuro'");
    add_column_if_missing($db, 'imagens', 'selecionada', 'TINYINT(1) NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'imagens', 'eh_publica', 'TINYINT(1) NOT NULL DEFAULT 1');
    add_column_if_missing($db, 'imagens', 'is_capa', 'TINYINT(1) NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'imagens', 'downloads', 'INT NOT NULL DEFAULT 0');
    add_column_if_missing($db, 'imagens', 'criado_em', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

    // Adicionar colunas para caminhos de thumbnails
    add_column_if_missing($db, 'imagens', 'caminho_thumb_small', 'VARCHAR(1024) DEFAULT NULL');
    add_column_if_missing($db, 'imagens', 'caminho_thumb_medium', 'VARCHAR(1024) DEFAULT NULL');
    add_column_if_missing($db, 'imagens', 'caminho_thumb_large', 'VARCHAR(1024) DEFAULT NULL');

    // Índice único para evitar duplicatas em caminho_arquivo (apenas se possível)
    try {
        // Tentar criar índice único para idempotência. Se houver duplicatas, a operação falhará e será logada.
        $db->exec("ALTER TABLE imagens ADD UNIQUE INDEX uniq_caminho_arquivo (caminho_arquivo(255))");
    } catch (Throwable $e) {
        error_log('Não foi possível adicionar UNIQUE INDEX uniq_caminho_arquivo: ' . $e->getMessage());
    }

    // Índice para tamanho_bytes para acelerar buscas por tamanho e ordenações
    try {
        add_index_if_missing($db, 'imagens', 'idx_imagens_tamanho', 'tamanho_bytes');
    } catch (Throwable $e) {
        error_log('Não foi possível adicionar índice idx_imagens_tamanho: ' . $e->getMessage());
    }

    json_out(['status' => 'ok', 'mensagem' => 'Banco verificado e schema preparado com sucesso.']);
} catch (Throwable $e) {
    error_log('Erro na migracao: ' . $e->getMessage());
    json_out(['status' => 'erro', 'mensagem' => 'Erro na migracao: ' . $e->getMessage()], 500);
}
