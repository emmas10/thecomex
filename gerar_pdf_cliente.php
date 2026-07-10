<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_pdf_relatorio.php';

require 'vendor/autoload.php';

use Dompdf\Dompdf;

$cliente_id = intval($_GET['cliente_id'] ?? 0);

if (!usuarioPodeAcessarClienteRelatorio($cliente_id)) {
    echo "Acesso negado.";
    exit;
}

$cliente = buscarClienteRelatorio($conn, $cliente_id);

if (!$cliente) {
    echo "Cliente nao encontrado ou desativado.";
    exit;
}

$cotacoes = buscarCotacoesRelatorio($conn, $cliente_id);
$html = montarHtmlCabecalhoPdfRelatorio($cliente);
$html .= montarHtmlTabelaCotacoesRelatorio($cotacoes, true);

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nomeArquivo = nomeArquivoRelatorio('relatorio', [$cliente['nome_empresa']]);

$dompdf->stream($nomeArquivo, ["Attachment" => false]);
exit;
?>
