-- Script para adicionar suporte ao Cloudflare R2 na tabela imagens
-- Execute este script no seu banco de dados MySQL (criavibe)
-- Data: 19/03/2026

-- Adicionar coluna r2_key para armazenar a chave do objeto no R2
ALTER TABLE imagens 
ADD COLUMN r2_key VARCHAR(500) NULL COMMENT 'Chave do objeto no Cloudflare R2 (ex: galerias/1/uuid.jpg)' 
AFTER caminho_arquivo;

-- (Opcional) Ver estrutura atualizada da tabela
-- DESCRIBE imagens;
