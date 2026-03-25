-- ============================================================
-- CriaVibe — Schema FINAL e DEFINITIVO v3
-- Banco: u276112142_criavibe (Hostinger)
-- Versão: 3.0 — Completo e consolidado
-- ============================================================
-- USE ESTE ARQUIVO para instalações novas (banco vazio).
-- Para bancos com dados, execute migration_v3.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- TABELA: usuarios (fotógrafos e admins)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`        INT          AUTO_INCREMENT PRIMARY KEY,
  `nome`      VARCHAR(100) NOT NULL,
  `email`     VARCHAR(150) NOT NULL UNIQUE,
  `senha`     VARCHAR(255) NOT NULL,
  `tipo`      ENUM('admin','fotografo','comum') NOT NULL DEFAULT 'fotografo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABELA: clientes (cadastrados pelo fotógrafo)
-- Separados de `usuarios` — não fazem login pelo sistema,
-- recebem acesso via senha gerada + link direto por e-mail/WhatsApp
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
  `id`             INT          AUTO_INCREMENT PRIMARY KEY,
  `fotografo_email`VARCHAR(150) NOT NULL              COMMENT 'E-mail do fotógrafo que cadastrou',
  `nome`           VARCHAR(200) NOT NULL,
  `email`          VARCHAR(150) DEFAULT NULL,
  `telefone`       VARCHAR(30)  DEFAULT NULL           COMMENT 'Telefone / WhatsApp',
  `senha_acesso`   VARCHAR(20)  NOT NULL               COMMENT 'Senha curta gerada automaticamente (não hash)',
  `criado_em`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cli_fotografo` (`fotografo_email`),
  INDEX `idx_cli_email`     (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABELA: galerias
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `galerias` (
  `id`                   INT          AUTO_INCREMENT PRIMARY KEY,
  `usuario_email`        VARCHAR(150) NOT NULL         COMMENT 'E-mail do fotógrafo dono',

  -- Informações básicas
  `nome`                 VARCHAR(200) NOT NULL,
  `descricao`            TEXT,
  `privacidade`          ENUM('publica','privada') NOT NULL DEFAULT 'privada',
  `senha`                VARCHAR(255) DEFAULT NULL     COMMENT 'Senha hash (galerias privadas)',

  -- Cliente associado
  `cliente_id`           INT          DEFAULT NULL     COMMENT 'FK -> clientes.id',
  `cliente_nome`         VARCHAR(200) DEFAULT NULL     COMMENT 'Cache: nome do cliente',
  `cliente_email`        VARCHAR(150) DEFAULT NULL     COMMENT 'Cache: e-mail do cliente',

  -- Acesso por link direto (sem login)
  `link_token`           VARCHAR(64)  DEFAULT NULL UNIQUE COMMENT 'Token UUID p/ link direto',
  `selecao_sem_senha`    TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Permite seleção sem senha',

  -- Capa da galeria
  `capa_url`             VARCHAR(500) DEFAULT NULL,
  `capa_orientacao`      ENUM('horizontal','vertical') NOT NULL DEFAULT 'horizontal',

  -- Módulo: Entrega em Alta
  `entrega_em_alta`      TINYINT(1)   NOT NULL DEFAULT 0,
  `download_individual`  TINYINT(1)   NOT NULL DEFAULT 0,
  `download_all`         TINYINT(1)   NOT NULL DEFAULT 0,

  -- Módulo: Seleção de Fotos
  `selecao_ativa`        TINYINT(1)   NOT NULL DEFAULT 0,
  `selecao_fotos`        TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Alias legacy (igual a selecao_ativa)',
  `selecao_publica`      TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Seleção visível publicamente',
  `max_selecao`          INT          DEFAULT NULL        COMMENT 'Máx. fotos selecionáveis (null=ilimitado)',
  `selecao_data_limite`  DATETIME     DEFAULT NULL        COMMENT 'Data/hora limite para seleção',

  -- Módulo: Venda de fotos extras
  `vender_fotos`         TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Cobrar por fotos além do pacote',
  `forma_cobrar`         ENUM('todas','adicionais') DEFAULT NULL COMMENT 'Modelo de cobrança',
  `valor_por_foto`       DECIMAL(10,2) DEFAULT NULL       COMMENT 'Preço por foto adicional (R$)',

  -- Módulo: Músicas (player)
  `musicas_ativas`       TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Habilita player na galeria do cliente',

  -- Visibilidade pública no site
  `fotos_destaque_count` TINYINT      NOT NULL DEFAULT 0  COMMENT 'Quantas fotos marcadas como públicas (0-3)',

  -- Metadados
  `criado_em`            DATETIME     DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_usuario_email`  (`usuario_email`),
  INDEX `idx_link_token`     (`link_token`),
  INDEX `idx_cliente_email`  (`cliente_email`),
  INDEX `idx_cliente_id`     (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABELA: imagens
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `imagens` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`      INT          NOT NULL,
  `nome_arquivo`    VARCHAR(255) NOT NULL,
  `titulo`          VARCHAR(200) DEFAULT '',
  `descricao`       TEXT,
  `caminho_arquivo` VARCHAR(500) NOT NULL    COMMENT 'URL pública (R2) ou caminho local',
  `r2_key`          VARCHAR(500) DEFAULT NULL,

  -- Ordenação e estado
  `ordem`           INT          NOT NULL DEFAULT 0,
  `selecionada`     TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Selecionada pelo cliente',
  `eh_publica`      TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'Exibida no site público (máx 3/galeria)',

  -- Metadados
  `tamanho_bytes`   BIGINT       DEFAULT NULL,
  `enviado_em`      DATETIME     DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_galeria_id`    (`galeria_id`),
  INDEX `idx_imagens_ordem` (`galeria_id`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABELA: musicas (player de fundo da galeria do cliente)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `musicas` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`      INT          NOT NULL,
  `nome_arquivo`    VARCHAR(255) NOT NULL,
  `nome_exibicao`   VARCHAR(255) DEFAULT NULL  COMMENT 'Nome amigável no player',
  `caminho_arquivo` VARCHAR(500) NOT NULL,
  `r2_key`          VARCHAR(500) DEFAULT NULL,
  `duracao_seg`     INT          DEFAULT NULL,
  `ordem`           INT          NOT NULL DEFAULT 0,
  `enviado_em`      DATETIME     DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_musicas_galeria` (`galeria_id`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABELA: notificacoes_envio (log de e-mails/WhatsApp enviados)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notificacoes_envio` (
  `id`          INT          AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`  INT          NOT NULL,
  `cliente_id`  INT          DEFAULT NULL,
  `canal`       ENUM('email','whatsapp') NOT NULL,
  `destinatario`VARCHAR(200) NOT NULL,
  `assunto`     VARCHAR(300) DEFAULT NULL,
  `mensagem`    TEXT,
  `enviado_em`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_galeria` (`galeria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- TABELA: visualizacoes (analytics básico)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `visualizacoes` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `galeria_id` INT          NOT NULL,
  `ip_hash`    VARCHAR(64)  DEFAULT NULL  COMMENT 'Hash do IP (privacidade)',
  `visualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_viz_galeria` (`galeria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICAÇÃO (descomente para confirmar):
-- SHOW TABLES;
-- DESCRIBE usuarios;
-- DESCRIBE clientes;
-- DESCRIBE galerias;
-- DESCRIBE imagens;
-- DESCRIBE musicas;
-- DESCRIBE notificacoes_envio;
-- DESCRIBE visualizacoes;
-- ============================================================
