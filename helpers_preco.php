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
        return mb_strtolower($produto, 'UTF-8');
    }

    return strtolower($produto);
}

function colunaProdutoBaseExiste($conn)
{
    static $existe = null;

    if ($existe !== null) {
        return $existe;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?
         AND COLUMN_NAME = ?"
    );
    $tabela = 'cotacoes';
    $campo = 'produto_base';
    $stmt->bind_param("ss", $tabela, $campo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $linha = $resultado ? $resultado->fetch_assoc() : null;

    $existe = ($linha && intval($linha['total']) > 0);

    return $existe;
}
?>
