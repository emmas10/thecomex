<?php
include 'conexao.php';

$produto = $_POST['produto'];
$fornecedor = $_POST['fornecedor'];
$preco_pago = $_POST['preco_pago'];
$quantidade = $_POST['quantidade'];
$data_compra = $_POST['data_compra'];
$observacoes = $_POST['observacoes'];

$sql = "INSERT INTO compras 
(produto, fornecedor, preco_pago, quantidade, data_compra, observacoes)
VALUES 
('$produto', '$fornecedor', '$preco_pago', '$quantidade', '$data_compra', '$observacoes')";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
} else {
    echo "Erro: " . $conn->error;
}
?>