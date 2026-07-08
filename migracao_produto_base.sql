ALTER TABLE cotacoes
  ADD COLUMN produto_base varchar(255) DEFAULT NULL AFTER produto;

UPDATE cotacoes
SET produto_base = TRIM(LOWER(produto))
WHERE produto_base IS NULL OR TRIM(produto_base) = '';

CREATE INDEX idx_cotacoes_produto_base ON cotacoes (produto_base);
