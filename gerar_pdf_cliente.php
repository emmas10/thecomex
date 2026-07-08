<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';

require 'vendor/autoload.php';

use Dompdf\Dompdf;

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$cliente_id = intval($_GET['cliente_id'] ?? 0);

$stmtCliente = $conn->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
$stmtCliente->bind_param("i", $cliente_id);
$stmtCliente->execute();
$resultadoCliente = $stmtCliente->get_result();
$cliente = $resultadoCliente->fetch_assoc();

if (!$cliente) {
    echo "Cliente nao encontrado.";
    exit;
}

$produtoBaseExiste = colunaProdutoBaseExiste($conn);
$produtoGrupo = $produtoBaseExiste
    ? "TRIM(LOWER(COALESCE(NULLIF(TRIM(produto_base), ''), produto)))"
    : "TRIM(LOWER(produto))";
$produtoGrupoCotacao = $produtoBaseExiste
    ? "TRIM(LOWER(COALESCE(NULLIF(TRIM(c.produto_base), ''), c.produto)))"
    : "TRIM(LOWER(c.produto))";

$sql = "
    SELECT
        c.*,
        {$produtoGrupoCotacao} AS produto_grupo,
        menores.menor_preco,
        CASE WHEN compras_ativas.cotacao_id IS NULL THEN 'Nao comprado' ELSE 'Comprado' END AS status_compra
    FROM cotacoes c
    INNER JOIN (
        SELECT {$produtoGrupo} AS produto_grupo, MIN(preco) AS menor_preco
        FROM cotacoes
        WHERE cliente_id = ?
        GROUP BY {$produtoGrupo}
    ) menores
        ON menores.produto_grupo = {$produtoGrupoCotacao}
    LEFT JOIN (
        SELECT cotacao_id
        FROM compras
        WHERE status = 'ativa'
        GROUP BY cotacao_id
    ) compras_ativas
        ON compras_ativas.cotacao_id = c.id
    WHERE c.cliente_id = ?
    ORDER BY produto_grupo ASC, c.preco ASC, c.produto ASC, c.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cliente_id, $cliente_id);
$stmt->execute();
$resultado = $stmt->get_result();

$nomeEmpresa = htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8');

$html = "
<h1>Relatorio de Cotacoes - TheComex</h1>
<h2>Empresa: {$nomeEmpresa}</h2>
<p>Gerado em: " . date('d/m/Y H:i') . "</p>

<table border='1' width='100%' cellspacing='0' cellpadding='6'>
<tr>
    <th>Produto</th>
    <th>Preco</th>
    <th>Fornecedor</th>
    <th>Origem</th>
    <th>Pagamento</th>
    <th>Data</th>
    <th>Status</th>
</tr>
";

while ($linha = $resultado->fetch_assoc()) {
    $preco = formatarMoeda($linha['preco'], $linha['preco_casas_decimais'] ?? null);
    $cor = ((float) $linha['preco'] === (float) $linha['menor_preco']) ? "#7CFC00" : "#FFFFFF";
    $produto = htmlspecialchars($linha['produto'], ENT_QUOTES, 'UTF-8');
    $fornecedor = htmlspecialchars($linha['fornecedor'], ENT_QUOTES, 'UTF-8');
    $origem = htmlspecialchars($linha['origem'], ENT_QUOTES, 'UTF-8');
    $pagamento = htmlspecialchars($linha['pagamento'], ENT_QUOTES, 'UTF-8');
    $dataCotacao = htmlspecialchars($linha['data_cotacao'], ENT_QUOTES, 'UTF-8');
    $statusCompra = htmlspecialchars($linha['status_compra'], ENT_QUOTES, 'UTF-8');

    $html .= "
    <tr style='background-color: {$cor};'>
        <td>{$produto}</td>
        <td>{$preco}</td>
        <td>{$fornecedor}</td>
        <td>{$origem}</td>
        <td>{$pagamento}</td>
        <td>{$dataCotacao}</td>
        <td>{$statusCompra}</td>
    </tr>
    ";
}

$html .= "</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nomeArquivo = "relatorio_" . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $cliente['nome_empresa']) . ".pdf";

$dompdf->stream($nomeArquivo, ["Attachment" => false]);
exit;
?>
