-- Executar uma vez na implantacao. O script pode ser repetido com seguranca.

ALTER TABLE compras
  ADD COLUMN IF NOT EXISTS produto_base varchar(255) DEFAULT NULL AFTER produto;

CREATE INDEX IF NOT EXISTS idx_compras_produto_base ON compras (produto_base);

UPDATE cotacoes
SET produto_base = REGEXP_REPLACE(TRIM(LOWER(produto)), '[[:space:]]+', ' ')
WHERE produto_base IS NULL OR TRIM(produto_base) = '';

UPDATE cotacoes
SET produto_base = REGEXP_REPLACE(TRIM(LOWER(produto_base)), '[[:space:]]+', ' ')
WHERE produto_base IS NOT NULL AND TRIM(produto_base) <> '';

DROP TEMPORARY TABLE IF EXISTS tmp_renomeacoes_produto;

CREATE TEMPORARY TABLE tmp_renomeacoes_produto AS
SELECT
    candidatos.cliente_id,
    candidatos.produto_base_antigo,
    MIN(candidatos.produto_base_novo) AS produto_base_novo,
    MIN(candidatos.produto_nome_novo) AS produto_nome_novo
FROM (
    SELECT
        c.cliente_id,
        REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' ') AS produto_base_antigo,
        REGEXP_REPLACE(TRIM(LOWER(c.produto)), '[[:space:]]+', ' ') AS produto_base_novo,
        TRIM(c.produto) AS produto_nome_novo
    FROM cotacoes c
    INNER JOIN compras cp ON cp.cotacao_id = c.id
    WHERE c.produto_base IS NOT NULL
      AND TRIM(c.produto_base) <> ''
      AND REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' ')
          = REGEXP_REPLACE(TRIM(LOWER(cp.produto)), '[[:space:]]+', ' ')
      AND REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' ')
          <> REGEXP_REPLACE(TRIM(LOWER(c.produto)), '[[:space:]]+', ' ')
      AND NOT (
          REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' ') = 'propylene glycol'
          AND REGEXP_REPLACE(TRIM(LOWER(c.produto)), '[[:space:]]+', ' ') IN ('propylene glycol usp', 'propilenoglicol usp')
      )
      AND NOT (
          REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' ') = 'lauryl 70'
          AND REGEXP_REPLACE(TRIM(LOWER(c.produto)), '[[:space:]]+', ' ') IN ('sles 70', 'sles 70%')
      )
      AND NOT (
          REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' ') = 'monosodium glutamate mesh 80'
          AND REGEXP_REPLACE(TRIM(LOWER(c.produto)), '[[:space:]]+', ' ') IN ('glutamate - msg-foodgrade', 'msg 80mesh')
      )
) candidatos
GROUP BY candidatos.cliente_id, candidatos.produto_base_antigo
HAVING COUNT(DISTINCT candidatos.produto_base_novo) = 1;

INSERT INTO produto_referencias
    (cliente_id, produto_base, valor_ultima_compra, data_ultima_compra)
SELECT
    pr.cliente_id,
    m.produto_base_novo,
    pr.valor_ultima_compra,
    pr.data_ultima_compra
FROM produto_referencias pr
INNER JOIN tmp_renomeacoes_produto m
    ON m.cliente_id = pr.cliente_id
   AND m.produto_base_antigo = pr.produto_base
ON DUPLICATE KEY UPDATE
    valor_ultima_compra = COALESCE(produto_referencias.valor_ultima_compra, VALUES(valor_ultima_compra)),
    data_ultima_compra = COALESCE(produto_referencias.data_ultima_compra, VALUES(data_ultima_compra));

DELETE pr
FROM produto_referencias pr
INNER JOIN tmp_renomeacoes_produto m
    ON m.cliente_id = pr.cliente_id
   AND m.produto_base_antigo = pr.produto_base;

UPDATE compras cp
LEFT JOIN cotacoes c ON c.id = cp.cotacao_id
INNER JOIN tmp_renomeacoes_produto m
    ON m.cliente_id = cp.cliente_id
   AND COALESCE(
        NULLIF(REGEXP_REPLACE(TRIM(LOWER(cp.produto_base)), '[[:space:]]+', ' '), ''),
        NULLIF(REGEXP_REPLACE(TRIM(LOWER(c.produto_base)), '[[:space:]]+', ' '), ''),
        REGEXP_REPLACE(TRIM(LOWER(cp.produto)), '[[:space:]]+', ' ')
   ) = m.produto_base_antigo
SET cp.produto_base = m.produto_base_novo;

UPDATE cotacoes c
INNER JOIN tmp_renomeacoes_produto m
    ON m.cliente_id = c.cliente_id
   AND c.produto_base = m.produto_base_antigo
SET c.produto = m.produto_nome_novo,
    c.produto_base = m.produto_base_novo;

UPDATE compras cp
INNER JOIN cotacoes c ON c.id = cp.cotacao_id
SET cp.produto_base = c.produto_base
WHERE c.produto_base IS NOT NULL AND TRIM(c.produto_base) <> '';

UPDATE compras cp
LEFT JOIN cotacoes c ON c.id = cp.cotacao_id
SET cp.produto_base = COALESCE(
    NULLIF(TRIM(c.produto_base), ''),
    REGEXP_REPLACE(TRIM(LOWER(cp.produto)), '[[:space:]]+', ' ')
)
WHERE cp.produto_base IS NULL OR TRIM(cp.produto_base) = '';

UPDATE compras
SET produto_base = REGEXP_REPLACE(TRIM(LOWER(produto_base)), '[[:space:]]+', ' ')
WHERE produto_base IS NOT NULL AND TRIM(produto_base) <> '';

DROP TEMPORARY TABLE IF EXISTS tmp_renomeacoes_produto;
