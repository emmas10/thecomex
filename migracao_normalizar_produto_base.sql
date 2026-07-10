UPDATE cotacoes
SET produto_base = TRIM(LOWER(produto))
WHERE produto_base IS NULL
OR TRIM(produto_base) = '';

UPDATE cotacoes
SET produto_base = REGEXP_REPLACE(TRIM(LOWER(produto_base)), '[[:space:]]+', ' ')
WHERE produto_base IS NOT NULL
AND TRIM(produto_base) <> '';

UPDATE cotacoes
SET produto_base = 'propylene glycol'
WHERE produto_base IS NOT NULL
AND TRIM(LOWER(produto_base)) IN (
  'propylene glycol',
  'propylene glycol usp',
  'propilenoglicol usp'
);

UPDATE cotacoes
SET produto_base = 'lauryl 70'
WHERE produto_base IS NOT NULL
AND TRIM(LOWER(produto_base)) IN (
  'lauryl 70',
  'sles 70',
  'sles 70%'
);

UPDATE cotacoes
SET produto_base = 'monosodium glutamate mesh 80'
WHERE produto_base IS NOT NULL
AND TRIM(LOWER(produto_base)) IN (
  'glutamate - msg-foodgrade',
  'monosodium glutamate mesh 80',
  'msg 80mesh'
);
