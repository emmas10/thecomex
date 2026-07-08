<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$cliente_id = intval($_POST['cliente_id']);
$usuario_id = intval($_SESSION['usuario_id']);
$cotacao = trim($_POST['cotacao']);
$produto = trim($_POST['produto']);
$produto_base = trim($_POST['produto_base'] ?? '');
if ($produto_base === '') {
    $produto_base = normalizarProdutoBase($produto);
}
$fornecedor = trim($_POST['fornecedor']);
$precoEntrada = parsePrecoEntrada($_POST['preco']);
if ($precoEntrada === false) {
    echo "Preço inválido. Use até 6 casas decimais.";
    exit;
}

$preco = $precoEntrada['valor'];
$preco_casas_decimais = $precoEntrada['casas'];
$origem = trim($_POST['origem']);
$pagamento = $_POST['pagamento'];
$quantidade = trim($_POST['quantidade']);
$data_cotacao = $_POST['data_cotacao'];

if (colunaProdutoBaseExiste($conn)) {
    $stmt = $conn->prepare(
        "INSERT INTO cotacoes
        (cliente_id, usuario_id, cotacao, produto, produto_base, fornecedor, preco, preco_casas_decimais, origem, pagamento, quantidade, data_cotacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iisssssissss", $cliente_id, $usuario_id, $cotacao, $produto, $produto_base, $fornecedor, $preco, $preco_casas_decimais, $origem, $pagamento, $quantidade, $data_cotacao);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO cotacoes
        (cliente_id, usuario_id, cotacao, produto, fornecedor, preco, preco_casas_decimais, origem, pagamento, quantidade, data_cotacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iissssissss", $cliente_id, $usuario_id, $cotacao, $produto, $fornecedor, $preco, $preco_casas_decimais, $origem, $pagamento, $quantidade, $data_cotacao);
}

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Erro ao salvar cotação.";
?>
