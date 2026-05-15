# Particionamento e Arquivamento para `imagens`

Objetivo: suportar tabelas com dezenas de milhões de linhas sem degradar consultas críticas.

Opções recomendadas:

- Particionamento por faixa de `criado_em` (mensal/trimestral).
- Rotação: manter 12 meses online e compactar/arquivar partições antigas para outra tabela ou bucket.
- Índices locais por partição: `galeria_id`, `ordem`.
- Monitorar tamanho de partição e mover partições grandes para Cold Storage.

Exemplo (MySQL 8.0, RANGE COLUMNS):

```
ALTER TABLE imagens
PARTITION BY RANGE COLUMNS(criado_em) (
  PARTITION p2025_01 VALUES LESS THAN ('2025-02-01'),
  PARTITION p2025_02 VALUES LESS THAN ('2025-03-01'),
  PARTITION pmax VALUES LESS THAN (MAXVALUE)
);
```

Estratégia de arquivamento:
- Exportar partições antigas para CSV/Parquet e armazenar no R2 em um prefixo `archive/`.
- Remover dados da tabela de produção após verificação.
