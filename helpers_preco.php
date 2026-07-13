<?php
function parsePrecoEntrada($valor)
{
    $valor = trim((string) $valor);
    $valor = str_replace(',', '.', $valor);

    if (!preg_match('/^\d+(?:\.\d{1,6})?$/', $valor)) {
        return false;
    }

    $partes = explode('.', $valor, 2);
    $casas = isset($partes[1]) ? strlen($partes[1]) : 0;

    return [
        'valor' => $valor,
        'casas' => $casas,
    ];
}

function formatarNumeroDecimal($valor, $casas = null)
{
    if ($valor === null || $valor === '') {
        return '';
    }

    $valor = trim(str_replace(',', '.', (string) $valor));
    $negativo = str_starts_with($valor, '-');
    if ($negativo) {
        $valor = substr($valor, 1);
    }

    if ($casas !== null && $casas !== '') {
        $casas = max(0, min(6, intval($casas)));
        $partes = explode('.', $valor, 2);
        $inteiro = $partes[0] !== '' ? $partes[0] : '0';
        $decimal = isset($partes[1]) ? substr(str_pad($partes[1], $casas, '0'), 0, $casas) : str_repeat('0', $casas);
        $numero = $casas > 0 ? $inteiro . '.' . $decimal : $inteiro;
    } else {
        $numero = number_format((float) $valor, 6, '.', '');
        $numero = rtrim(rtrim($numero, '0'), '.');
    }

    $numero = ltrim($numero, '0');
    if ($numero === '' || str_starts_with($numero, '.')) {
        $numero = '0' . $numero;
    }

    if ($numero === '0') {
        $negativo = false;
    }

    if ($numero === '-0') {
        $numero = '0';
    }

    if ($negativo) {
        $numero = '-' . $numero;
    }

    return str_replace('.', ',', $numero);
}

function formatarMoeda($valor, $casas = null)
{
    return 'US$ ' . formatarNumeroDecimal($valor, $casas);
}

function normalizarProdutoBase($produto)
{
    $produto = trim((string) $produto);
    $produto = preg_replace('/\s+/', ' ', $produto);

    if (function_exists('mb_strtolower')) {
        $produto = mb_strtolower($produto, 'UTF-8');
    } else {
        $produto = strtolower($produto);
    }

    if (function_exists('iconv')) {
        $semAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $produto);

        if ($semAcentos !== false) {
            $produto = $semAcentos;
        }
    }

    $produto = strtr($produto, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c',
    ]);

    $produto = preg_replace('/\s+/', ' ', $produto);
    $produto = trim($produto);

    $aliases = [
        'glutamate - msg-foodgrade' => 'monosodium glutamate mesh 80',
        'lauryl 70' => 'lauryl 70',
        'monosodium glutamate mesh 80' => 'monosodium glutamate mesh 80',
        'msg 80mesh' => 'monosodium glutamate mesh 80',
        'propilenoglicol usp' => 'propylene glycol',
        'propylene glycol usp' => 'propylene glycol',
        'propylene glycol' => 'propylene glycol',
        'sles 70' => 'lauryl 70',
        'sles 70%' => 'lauryl 70',
    ];

    return $aliases[$produto] ?? $produto;
}

function obterProdutoGrupoCotacao($cotacao)
{
    $camposGrupo = ['produto_grupo', 'produto_base', 'produto_padronizado'];

    foreach ($camposGrupo as $campo) {
        if (isset($cotacao[$campo]) && trim((string) $cotacao[$campo]) !== '') {
            return normalizarProdutoBase($cotacao[$campo]);
        }
    }

    return normalizarProdutoBase($cotacao['produto'] ?? '');
}

function colunaProdutoBaseExiste($conn)
{
    return colunaExiste($conn, 'cotacoes', 'produto_base');
}

function colunaProdutoBaseComprasExiste($conn)
{
    return colunaExiste($conn, 'compras', 'produto_base');
}

function colunaExiste($conn, $tabela, $campo)
{
    static $cache = [];
    $chave = $tabela . '.' . $campo;

    if (array_key_exists($chave, $cache)) {
        return $cache[$chave];
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?
         AND COLUMN_NAME = ?"
    );
    $stmt->bind_param("ss", $tabela, $campo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $linha = $resultado ? $resultado->fetch_assoc() : null;

    $cache[$chave] = ($linha && intval($linha['total']) > 0);

    return $cache[$chave];
}

function tabelaExiste($conn, $tabela)
{
    static $cache = [];

    if (array_key_exists($tabela, $cache)) {
        return $cache[$tabela];
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?"
    );
    $stmt->bind_param("s", $tabela);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $linha = $resultado ? $resultado->fetch_assoc() : null;
    $cache[$tabela] = ($linha && intval($linha['total']) > 0);

    return $cache[$tabela];
}

function prefixoAliasSql($alias)
{
    if ($alias === '') {
        return '';
    }

    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias)) {
        throw new InvalidArgumentException('Alias SQL invalido.');
    }

    return $alias . '.';
}

function expressaoProdutoGrupoCotacaoSql($alias = '')
{
    $prefixo = prefixoAliasSql($alias);

    return "COALESCE(NULLIF(TRIM({$prefixo}produto_base), ''), TRIM(LOWER({$prefixo}produto)))";
}

function expressaoProdutoGrupoCompraSql($aliasCompra = '', $aliasCotacao = '')
{
    $compra = prefixoAliasSql($aliasCompra);
    $partes = [];

    if ($aliasCotacao !== '') {
        $cotacao = prefixoAliasSql($aliasCotacao);
        $partes[] = "NULLIF(TRIM({$cotacao}produto_base), '')";
    }

    $partes[] = "NULLIF(TRIM({$compra}produto_base), '')";
    $partes[] = "TRIM(LOWER({$compra}produto))";

    return 'COALESCE(' . implode(', ', $partes) . ')';
}
?>
