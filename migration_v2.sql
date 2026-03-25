-- ============================================================
-- CriaVibe — Migração v2: Plataforma de Galerias Profissional
-- Execute no phpMyAdmin da Hostinger (banco: u276112142_criavibe)
-- Adiciona novos campos SEM apagar dados existentes
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Tabela: galerias — novas colunas funcionais
-- ------------------------------------------------------------
ALTER TABLE `galerias`
  ADD COLUMN IF NOT EXISTS `entrega_em_alta`    TINYINT(1) NOT NULL DEFAULT 0
      COMMENT 'Ativa módulo de entrega de fotos originais',
  ADD COLUMN IF NOT EXISTS `selecao_fotos`       TINYINT(1) NOT NULL DEFAULT 0
      COMMENT 'Ativa módulo de seleção de fotos pelo cliente',
  ADD COLUMN IF NOT EXISTS `download_individual` TINYINT(1) NOT NULL DEFAULT 0
      COMMENT 'Permite download de fotos individuais pelo cliente',
  ADD COLUMN IF NOT EXISTS `download_all`        TINYINT(1) NOT NULL DEFAULT 0
      COMMENT 'Permite download de todas as fotos em ZIP',
  ADD COLUMN IF NOT EXISTS `capa_url`            VARCHAR(500) DEFAULT NULL
      COMMENT 'URL da foto de capa da galeria';

-- ------------------------------------------------------------
-- Tabela: imagens — novas colunas funcionais
-- ------------------------------------------------------------
ALTER TABLE `imagens`
  ADD COLUMN IF NOT EXISTS `ordem`       INT NOT NULL DEFAULT 0
      COMMENT 'Ordem de exibição da foto na galeria',
  ADD COLUMN IF NOT EXISTS `selecionada` TINYINT(1) NOT NULL DEFAULT 0
      COMMENT 'Foto selecionada/favorita pelo cliente',
  ADD COLUMN IF NOT EXISTS `tamanho_bytes` BIGINT DEFAULT NULL
      COMMENT 'Tamanho do arquivo em bytes';

-- Índice de performance para ordenação
CREATE INDEX IF NOT EXISTS `idx_imagens_ordem` ON `imagens` (`galeria_id`, `ordem`);

-- ============================================================
-- Verificação: confirme as colunas criadas
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'galerias' AND TABLE_SCHEMA = 'u276112142_criavibe';
-- ============================================================
