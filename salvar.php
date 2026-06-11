<?php
include 'conexao.php';
$cotacao = $_POST['cotacao'];
$produto = $_POST['produto'];
$fornecedor = $_POST['fornecedor'];
$preco = $_POST['preco'];
$origem = $_POST['origem'];
$pagamento = $_POST['pagamento'];
$quantidade = $_POST['quantidade'];
$data_cotacao = $_POST['data_cotacao'];

$sql = "INSERT INTO cotacoes 
(cotacao, produto, fornecedor, preco, origem, pagamento, quantidade, data_cotacao)
VALUES 
('$cotacao', '$produto', '$fornecedor', '$preco', '$origem', '$pagamento', '$quantidade', '$data_cotacao')";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
} else {
    echo "Erro: " . $conn->error;
}
?>