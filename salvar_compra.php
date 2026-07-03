<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$produto = trim($_POST['produto']);
$fornecedor = trim($_POST['fornecedor']);
$preco_pago = floatval($_POST['preco_pago']);
$quantidade = trim($_POST['quantidade']);
$data_compra = $_POST['data_compra'];
$observacoes = trim($_POST['observacoes']);
$cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
$usuario_id = intval($_SESSION['usuario_id']);

if ($cliente_id <= 0) {
    echo "Cliente inválido.";
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO compras
    (produto, fornecedor, preco_pago, quantidade, data_compra, observacoes, cliente_id, usuario_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssdsssii", $produto, $fornecedor, $preco_pago, $quantidade, $data_compra, $observacoes, $cliente_id, $usuario_id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Erro ao salvar compra.";
?>
