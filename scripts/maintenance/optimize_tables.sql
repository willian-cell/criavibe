-- Script de manutenção: reindex e otimização (MySQL/InnoDB)
ANALYZE TABLE imagens;
OPTIMIZE TABLE imagens;

-- Recriar índices caso necessário (exemplo)
-- ALTER TABLE imagens DROP INDEX idx_imagens_tamanho;
-- ALTER TABLE imagens ADD INDEX idx_imagens_tamanho (tamanho_bytes);
