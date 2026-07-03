<?php
include 'verifica_login.php';
include 'conexao.php';

require 'vendor/autoload.php';

use Dompdf\Dompdf;

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$cliente_id = intval($_GET['cliente_id']);

$sqlCliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$resultadoCliente = $conn->query($sqlCliente);
$cliente = $resultadoCliente->fetch_assoc();

$sql = "SELECT * FROM cotacoes 
        WHERE cliente_id = $cliente_id
        ORDER BY produto ASC, preco ASC";

$resultado = $conn->query($sql);

$html = "
<h1>Relatório de Cotações - TheComex</h1>
<h2>Empresa: {$cliente['nome_empresa']}</h2>
<p>Gerado em: " . date('d/m/Y H:i') . "</p>

<table border='1' width='100%' cellspacing='0' cellpadding='6'>
<tr>
    <th>Produto</th>
    <th>Preço</th>
    <th>Fornecedor</th>
    <th>Origem</th>
    <th>Pagamento</th>
    <th>Data</th>
    <th>Status</th>
</tr>
";

$produtoAnterior = "";

while ($linha = $resultado->fetch_assoc()) {

    $preco = "R$ " . number_format($linha['preco'], 2, ',', '.');

    if ($linha['produto'] != $produtoAnterior) {
        $cor = "#7CFC00";
        $produtoAnterior = $linha['produto'];
    } else {
        $cor = "#FFFFFF";
    }

    $idCotacao = $linha['id'];

    $sqlCompra = "SELECT * FROM compras 
                  WHERE cotacao_id = $idCotacao 
                  AND status = 'ativa' 
                  LIMIT 1";

    $resultadoCompra = $conn->query($sqlCompra);

    if ($resultadoCompra->num_rows > 0) {
        $statusCompra = "Comprado";
    } else {
        $statusCompra = "Não comprado";
    }

    $html .= "
    <tr style='background-color: {$cor};'>
        <td>{$linha['produto']}</td>
        <td>{$preco}</td>
        <td>{$linha['fornecedor']}</td>
        <td>{$linha['origem']}</td>
        <td>{$linha['pagamento']}</td>
        <td>{$linha['data_cotacao']}</td>
        <td>{$statusCompra}</td>
    </tr>
    ";
}

$html .= "</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nomeArquivo = "relatorio_" . $cliente['nome_empresa'] . ".pdf";

$dompdf->stream($nomeArquivo, ["Attachment" => false]);
exit;
?>