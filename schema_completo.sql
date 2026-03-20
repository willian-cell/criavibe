-- ============================================================
-- CRIAVIBE — Schema Completo do Banco de Dados
-- Execute no phpMyAdmin da Hostinger (banco: u276112142_criavibe)
-- Data: 19/03/2026
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabela: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `nome`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `senha`      VARCHAR(255) NOT NULL,
  `tipo`       ENUM('admin','fotografo','comum') NOT NULL DEFAULT 'comum',
  `criado_em`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: galerias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `galerias` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_email`  VARCHAR(150) NOT NULL,
  `nome`           VARCHAR(200) NOT NULL,
  `descricao`      TEXT,
  `privacidade`    ENUM('publica','privada') NOT NULL DEFAULT 'publica',
  `senha`          VARCHAR(255) DEFAULT NULL COMMENT 'Senha de acesso para galerias privadas',
  `criado_em`      DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_usuario_email` (`usuario_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: imagens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `imagens` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `galeria_id`      INT NOT NULL,
  `nome_arquivo`    VARCHAR(255) NOT NULL,
  `titulo`          VARCHAR(200) DEFAULT '',
  `descricao`       TEXT,
  `caminho_arquivo` VARCHAR(500) NOT NULL COMMENT 'URL pública (R2) ou caminho local',
  `r2_key`          VARCHAR(500) DEFAULT NULL COMMENT 'Chave do objeto no Cloudflare R2',
  `enviado_em`      DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_galeria_id` (`galeria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- (Opcional) Criar usuário admin inicial:
-- Senha "admin123" gerada com Werkzeug — troque após o primeiro login!
-- ============================================================
-- INSERT INTO usuarios (nome, email, senha, tipo)
-- VALUES ('Admin', 'admin@criavibe.com',
--   'scrypt:32768:8:1$salt$hash', 'admin');
