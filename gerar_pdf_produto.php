<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_pdf_relatorio.php';

require 'vendor/autoload.php';

use Dompdf\Dompdf;

$cliente_id = intval($_GET['cliente_id'] ?? 0);
$produtoGrupo = normalizarProdutoBase($_GET['produto_grupo'] ?? '');

if (!usuarioPodeAcessarClienteRelatorio($cliente_id)) {
    echo "Acesso negado.";
    exit;
}

if ($produtoGrupo === '') {
    echo "Produto nao informado.";
    exit;
}

$cliente = buscarClienteRelatorio($conn, $cliente_id);

if (!$cliente) {
    echo "Cliente nao encontrado ou desativado.";
    exit;
}

$cotacoes = buscarCotacoesRelatorio($conn, $cliente_id);
$cotacoesProduto = filtrarCotacoesPorProdutoRelatorio($cotacoes, $produtoGrupo);

if (count($cotacoesProduto) === 0) {
    echo "Nenhuma cotacao encontrada para este produto nesta empresa.";
    exit;
}

$produtoTitulo = $cotacoesProduto[0]['produto'] ?? $produtoGrupo;
$html = montarHtmlCabecalhoPdfRelatorio($cliente, "Produto: {$produtoTitulo}");
$html .= montarHtmlTabelaCotacoesRelatorio($cotacoesProduto, false);

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nomeArquivo = nomeArquivoRelatorio('relatorio_produto', [$cliente['nome_empresa'], $produtoTitulo]);

$dompdf->stream($nomeArquivo, ["Attachment" => false]);
exit;
?>
