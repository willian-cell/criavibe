-- ============================================================
-- CriaVibe — Migração v3 (para bancos que já rodaram v1 e v2)
-- SEGURO: usa ADD COLUMN IF NOT EXISTS / CREATE TABLE IF NOT EXISTS
-- Não apaga dados existentes.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────
-- NOVA TABELA: clientes
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `fotografo_email` VARCHAR(150) NOT NULL,
  `nome`            VARCHAR(200) NOT NULL,
  `email`           VARCHAR(150) DEFAULT NULL,
  `telefone`        VARCHAR(30)  DEFAULT NULL,
  `senha_acesso`    VARCHAR(20)  NOT NULL,
  `criado_em`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cli_fotografo` (`fotografo_email`),
  INDEX `idx_cli_email`     (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- NOVA TABELA: musicas
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `musicas` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`      INT          NOT NULL,
  `nome_arquivo`    VARCHAR(255) NOT NULL,
  `nome_exibicao`   VARCHAR(255) DEFAULT NULL,
  `caminho_arquivo` VARCHAR(500) NOT NULL,
  `r2_key`          VARCHAR(500) DEFAULT NULL,
  `youtube_url`     VARCHAR(500) DEFAULT NULL   COMMENT 'URL do YouTube (alternativa ao upload)',
  `duracao_seg`     INT          DEFAULT NULL,
  `ordem`           INT          NOT NULL DEFAULT 0,
  `enviado_em`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_musicas_galeria` (`galeria_id`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- NOVA TABELA: notificacoes_envio
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notificacoes_envio` (
  `id`           INT          AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`   INT          NOT NULL,
  `cliente_id`   INT          DEFAULT NULL,
  `canal`        ENUM('email','whatsapp') NOT NULL,
  `destinatario` VARCHAR(200) NOT NULL,
  `assunto`      VARCHAR(300) DEFAULT NULL,
  `mensagem`     TEXT,
  `enviado_em`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_galeria` (`galeria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- NOVA TABELA: visualizacoes
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `visualizacoes` (
  `id`             INT         AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`     INT         NOT NULL,
  `ip_hash`        VARCHAR(64) DEFAULT NULL,
  `visualizado_em` DATETIME    DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_viz_galeria` (`galeria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- galerias — novas colunas (Fase 2 + 3)
-- ─────────────────────────────────────────────
ALTER TABLE `galerias`
  ADD COLUMN IF NOT EXISTS `cliente_nome`         VARCHAR(200) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cliente_email`        VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cliente_id`           INT          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `link_token`           VARCHAR(64)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `selecao_sem_senha`    TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `capa_orientacao`      ENUM('horizontal','vertical') NOT NULL DEFAULT 'horizontal',
  ADD COLUMN IF NOT EXISTS `selecao_ativa`        TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `selecao_publica`      TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `max_selecao`          INT          DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `selecao_data_limite`  DATETIME     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `vender_fotos`         TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `forma_cobrar`         ENUM('todas','adicionais') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `valor_por_foto`       DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `musicas_ativas`       TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `fotos_destaque_count` TINYINT      NOT NULL DEFAULT 0;

-- Índices criados APÓS as colunas existirem
CREATE INDEX IF NOT EXISTS `idx_link_token`    ON `galerias` (`link_token`);
CREATE INDEX IF NOT EXISTS `idx_cliente_email` ON `galerias` (`cliente_email`);
CREATE INDEX IF NOT EXISTS `idx_cliente_id`    ON `galerias` (`cliente_id`);

-- ─────────────────────────────────────────────
-- imagens — nova coluna eh_publica
-- ─────────────────────────────────────────────
ALTER TABLE `imagens`
  ADD COLUMN IF NOT EXISTS `eh_publica` TINYINT(1) NOT NULL DEFAULT 0;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verificação após execução:
-- SHOW TABLES;
-- DESCRIBE galerias;
-- ============================================================
