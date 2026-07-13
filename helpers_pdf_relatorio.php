<?php
require_once 'helpers_preco.php';

function usuarioPodeAcessarClienteRelatorio($clienteId)
{
    if (($_SESSION['usuario_tipo'] ?? '') === 'admin') {
        return true;
    }

    $clienteSessao = intval($_SESSION['cliente_id'] ?? 0);

    return $clienteSessao > 0 && $clienteSessao === intval($clienteId);
}

function buscarClienteRelatorio($conn, $clienteId)
{
    $stmtCliente = $conn->prepare("SELECT * FROM clientes WHERE id = ? AND ativo = 1 LIMIT 1");
    $stmtCliente->bind_param("i", $clienteId);
    $stmtCliente->execute();
    $resultadoCliente = $stmtCliente->get_result();

    return $resultadoCliente ? $resultadoCliente->fetch_assoc() : null;
}

function buscarCotacoesRelatorio($conn, $clienteId)
{
    $sql = "
        SELECT
            c.*,
            CASE WHEN compras_ativas.cotacao_id IS NULL THEN 'Nao comprado' ELSE 'Comprado' END AS status_compra
        FROM cotacoes c
        LEFT JOIN (
            SELECT cotacao_id
            FROM compras
            WHERE status = 'ativa'
            GROUP BY cotacao_id
        ) compras_ativas
            ON compras_ativas.cotacao_id = c.id
        WHERE c.cliente_id = ?
        ORDER BY c.id ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clienteId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $cotacoes = [];

    while ($linha = $resultado->fetch_assoc()) {
        $cotacoes[] = $linha;
    }

    $cotacoes = prepararCotacoesRelatorio($cotacoes);
    $referencias = buscarReferenciasProdutoRelatorio($conn, $clienteId);

    return aplicarReferenciasProdutoRelatorio($cotacoes, $referencias);
}

function buscarProdutosRelatorio($conn, $clienteId)
{
    return obterProdutosRelatorio(buscarCotacoesRelatorio($conn, $clienteId));
}

function tabelaProdutoReferenciasExiste($conn)
{
    static $existe = null;

    if ($existe !== null) {
        return $existe;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?"
    );
    $tabela = 'produto_referencias';
    $stmt->bind_param("s", $tabela);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $linha = $resultado ? $resultado->fetch_assoc() : null;

    $existe = ($linha && intval($linha['total']) > 0);

    return $existe;
}

function buscarReferenciasProdutoRelatorio($conn, $clienteId)
{
    if (!tabelaProdutoReferenciasExiste($conn)) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT produto_base, valor_ultima_compra, data_ultima_compra
         FROM produto_referencias
         WHERE cliente_id = ?"
    );
    $stmt->bind_param("i", $clienteId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $referencias = [];

    while ($linha = $resultado->fetch_assoc()) {
        $produtoBase = normalizarProdutoBase($linha['produto_base'] ?? '');

        if ($produtoBase !== '') {
            $referencias[$produtoBase] = $linha;
        }
    }

    return $referencias;
}

function aplicarReferenciasProdutoRelatorio($cotacoes, $referencias)
{
    foreach ($cotacoes as &$cotacao) {
        $produtoGrupo = $cotacao['produto_grupo_relatorio'] ?? '';
        $referencia = $referencias[$produtoGrupo] ?? null;

        $cotacao['valor_ultima_compra_referencia'] = $referencia['valor_ultima_compra'] ?? null;
        $cotacao['data_ultima_compra_referencia'] = $referencia['data_ultima_compra'] ?? null;
    }
    unset($cotacao);

    return $cotacoes;
}

function prepararCotacoesRelatorio($cotacoes)
{
    $preparadas = [];

    foreach ($cotacoes as $linha) {
        $produtoGrupo = obterProdutoGrupoCotacao($linha);

        $linha['produto_grupo_relatorio'] = $produtoGrupo;
        $preparadas[] = $linha;
    }

    usort($preparadas, function ($a, $b) {
        $comparacaoGrupo = strcmp($a['produto_grupo_relatorio'], $b['produto_grupo_relatorio']);

        if ($comparacaoGrupo !== 0) {
            return $comparacaoGrupo;
        }

        $comparacaoPreco = (float) $a['preco'] <=> (float) $b['preco'];

        if ($comparacaoPreco !== 0) {
            return $comparacaoPreco;
        }

        $comparacaoProduto = strcmp((string) $a['produto'], (string) $b['produto']);

        if ($comparacaoProduto !== 0) {
            return $comparacaoProduto;
        }

        return intval($a['id']) <=> intval($b['id']);
    });

    return $preparadas;
}

function filtrarCotacoesPorProdutoRelatorio($cotacoes, $produtoGrupo)
{
    $produtoGrupo = normalizarProdutoBase($produtoGrupo);
    $filtradas = [];

    foreach ($cotacoes as $cotacao) {
        if (($cotacao['produto_grupo_relatorio'] ?? '') === $produtoGrupo) {
            $filtradas[] = $cotacao;
        }
    }

    usort($filtradas, function ($a, $b) {
        $comparacaoPreco = (float) $a['preco'] <=> (float) $b['preco'];

        if ($comparacaoPreco !== 0) {
            return $comparacaoPreco;
        }

        $comparacaoProduto = strcmp((string) $a['produto'], (string) $b['produto']);

        if ($comparacaoProduto !== 0) {
            return $comparacaoProduto;
        }

        return intval($a['id']) <=> intval($b['id']);
    });

    return $filtradas;
}

function obterProdutosRelatorio($cotacoes)
{
    $produtos = [];

    foreach ($cotacoes as $cotacao) {
        $grupo = $cotacao['produto_grupo_relatorio'] ?? '';

        if ($grupo === '') {
            continue;
        }

        if (!isset($produtos[$grupo])) {
            $produtos[$grupo] = $cotacao['produto'] ?? $grupo;
        }
    }

    asort($produtos, SORT_NATURAL | SORT_FLAG_CASE);

    return $produtos;
}

function montarHtmlDiferencaReferenciaRelatorio($precoCotado, $valorUltimaCompra)
{
    if ($valorUltimaCompra === null || $valorUltimaCompra === '' || (float) $valorUltimaCompra == 0.0) {
        return [
            'texto' => 'Sem referencia',
            'cor' => '#555',
        ];
    }

    $precoCotado = (float) $precoCotado;
    $valorUltimaCompra = (float) $valorUltimaCompra;
    $valorDiferenca = $precoCotado - $valorUltimaCompra;

    if ($precoCotado == 0.0) {
        return [
            'texto' => 'Sem referencia',
            'cor' => '#555',
        ];
    }

    if ($valorDiferenca < 0) {
        $percentual = ($valorDiferenca / $precoCotado) * 100;

        return [
            'texto' => formatarNumeroDecimal($percentual, 2) . '% - Economia',
            'cor' => 'green',
        ];
    }

    if ($valorDiferenca > 0) {
        $percentual = ($valorDiferenca / $valorUltimaCompra) * 100;

        return [
            'texto' => '+' . formatarNumeroDecimal($percentual, 2) . '% - Aumento',
            'cor' => 'red',
        ];
    }

    return [
        'texto' => '0,00% - Mesmo preco',
        'cor' => '#222',
    ];
}

function formatarDataRelatorio($data)
{
    if ($data === null || $data === '') {
        return '';
    }

    $partes = explode('-', (string) $data);

    if (count($partes) === 3) {
        return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
    }

    return (string) $data;
}

function montarHtmlCabecalhoPdfRelatorio($cliente, $subtitulo = '')
{
    $nomeEmpresa = htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8');
    $logoPath = __DIR__ . "/img/logo-lachemicals.jpg";
    $logoHtml = "";

    if (is_readable($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        $logoHtml = "<img src='data:image/jpeg;base64,{$logoBase64}' alt='Logo' class='logo-pdf'>";
    }

    $subtituloHtml = $subtitulo !== ''
        ? "<h2>" . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . "</h2>"
        : "";

    return "
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1 {
            margin: 0;
        }

        .cabecalho-pdf {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .cabecalho-pdf td {
            border: none !important;
            padding: 0 !important;
        }

        .logo-pdf {
            width: 210px;
        }

        .logo-coluna {
            text-align: right;
            vertical-align: top;
        }

        h2 {
            margin: 0 0 6px 0;
        }

        .produto-bloco {
            margin-top: 22px;
        }

        .produto-titulo {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 6px;
        }

        th {
            background-color: #eeeeee;
            text-align: left;
        }
    </style>

    <table class='cabecalho-pdf'>
        <tr>
            <td><h1>Relatorio de Cotacoes - Latina America Chemicals</h1></td>
            <td class='logo-coluna'>{$logoHtml}</td>
        </tr>
    </table>
    <h2>Empresa: {$nomeEmpresa}</h2>
    {$subtituloHtml}
    <p>Gerado em: " . date('d/m/Y H:i') . "</p>
    ";
}

function montarHtmlTabelaCotacoesRelatorio($cotacoes, $agruparPorProduto = true)
{
    $html = "";
    $produtoAtual = null;
    $tabelaAberta = false;
    $primeiraLinhaDoBloco = false;

    foreach ($cotacoes as $linha) {
        $grupoLinha = $agruparPorProduto ? ($linha['produto_grupo_relatorio'] ?? '') : '__produto_unico__';

        if ($produtoAtual !== $grupoLinha) {
            if ($tabelaAberta) {
                $html .= "</table></div>";
            }

            $produtoAtual = $grupoLinha;
            $produtoTitulo = htmlspecialchars($linha['produto'] ?? '', ENT_QUOTES, 'UTF-8');

            $html .= "
            <div class='produto-bloco'>
                <div class='produto-titulo'>Produto: {$produtoTitulo}</div>
                <table>
                    <tr>
                        <th>Preco cotado</th>
                        <th>Ultima compra</th>
                        <th>Diferença ultima compra</th>
                        <th>Fornecedor</th>
                        <th>Origem</th>
                        <th>Pagamento</th>
                        <th>Data</th>
                        <th>Status</th>
                    </tr>
            ";

            $tabelaAberta = true;
            $primeiraLinhaDoBloco = true;
        }

        $preco = formatarMoeda($linha['preco'], $linha['preco_casas_decimais'] ?? null);
        $valorUltimaCompra = $linha['valor_ultima_compra_referencia'] ?? null;
        $dataUltimaCompra = formatarDataRelatorio($linha['data_ultima_compra_referencia'] ?? null);
        $ultimaCompra = 'Sem referencia';

        if ($valorUltimaCompra !== null && $valorUltimaCompra !== '' && (float) $valorUltimaCompra > 0) {
            $ultimaCompra = formatarMoeda($valorUltimaCompra);

            if ($dataUltimaCompra !== '') {
                $ultimaCompra .= "<br><small>{$dataUltimaCompra}</small>";
            }
        }

        $diferenca = montarHtmlDiferencaReferenciaRelatorio($linha['preco'] ?? null, $valorUltimaCompra);
        $cor = $primeiraLinhaDoBloco ? "#6cc531" : "#FFFFFF";
        $fornecedor = htmlspecialchars($linha['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8');
        $origem = htmlspecialchars($linha['origem'] ?? '', ENT_QUOTES, 'UTF-8');
        $pagamento = htmlspecialchars($linha['pagamento'] ?? '', ENT_QUOTES, 'UTF-8');
        $dataCotacao = htmlspecialchars($linha['data_cotacao'] ?? '', ENT_QUOTES, 'UTF-8');
        $statusCompra = htmlspecialchars($linha['status_compra'] ?? '', ENT_QUOTES, 'UTF-8');

        $html .= "
        <tr style='background-color: {$cor};'>
            <td>{$preco}</td>
            <td>{$ultimaCompra}</td>
            <td style='color: {$diferenca['cor']}; font-weight: bold;'>{$diferenca['texto']}</td>
            <td>{$fornecedor}</td>
            <td>{$origem}</td>
            <td>{$pagamento}</td>
            <td>{$dataCotacao}</td>
            <td>{$statusCompra}</td>
        </tr>
        ";

        $primeiraLinhaDoBloco = false;
    }

    if ($tabelaAberta) {
        $html .= "</table></div>";
    }

    if ($html === '') {
        $html = "<p>Nenhuma cotacao encontrada.</p>";
    }

    return $html;
}

function nomeArquivoRelatorio($prefixo, $partes)
{
    $nome = $prefixo . '_' . implode('_', $partes);

    return preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nome) . ".pdf";
}
?>
