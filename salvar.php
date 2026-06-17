<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}
$cliente_id = $_SESSION['cliente_id'];
$cotacao = $_POST['cotacao'];
$produto = $_POST['produto'];
$fornecedor = $_POST['fornecedor'];
$preco = $_POST['preco'];
$origem = $_POST['origem'];
$data_pagamento = $_POST['data_pagamento'];
$quantidade = $_POST['quantidade'];
$data_cotacao = $_POST['data_cotacao'];

$sql = "INSERT INTO cotacoes 
(cliente_id, cotacao, produto, fornecedor, preco, origem, pagamento, quantidade, data_cotacao,)
VALUES 
('$cliente_id', '$cotacao', '$produto', '$fornecedor', '$preco', '$origem', '$data_pagamento', '$quantidade', '$data_cotacao')";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
} else {
    echo "Erro: " . $conn->error;
}
?>