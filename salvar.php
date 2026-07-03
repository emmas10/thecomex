<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$cliente_id = intval($_POST['cliente_id']);
$usuario_id = intval($_SESSION['usuario_id']);
$cotacao = trim($_POST['cotacao']);
$produto = trim($_POST['produto']);
$fornecedor = trim($_POST['fornecedor']);
$preco = floatval($_POST['preco']);
$origem = trim($_POST['origem']);
$pagamento = $_POST['pagamento'];
$quantidade = trim($_POST['quantidade']);
$data_cotacao = $_POST['data_cotacao'];

$stmt = $conn->prepare(
    "INSERT INTO cotacoes
    (cliente_id, usuario_id, cotacao, produto, fornecedor, preco, origem, pagamento, quantidade, data_cotacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("iisssdssss", $cliente_id, $usuario_id, $cotacao, $produto, $fornecedor, $preco, $origem, $pagamento, $quantidade, $data_cotacao);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Erro ao salvar cotação.";
?>
