<?php
include 'conexao.php';

$id = $_POST['id'];

// Busca a cotação selecionada
$sql = "SELECT * FROM cotacoes WHERE id = $id";
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {

    $cotacao = $resultado->fetch_assoc();

    $produto = $cotacao['produto'];
    $fornecedor = $cotacao['fornecedor'];
    $preco = $cotacao['preco'];
    $quantidade = $cotacao['quantidade'];
    $data = date('Y-m-d');

    // Registra automaticamente na tabela compras
    $sqlCompra = "
        INSERT INTO compras
        (produto, fornecedor, preco_pago, quantidade, data_compra)
        VALUES
        ('$produto', '$fornecedor', '$preco', '$quantidade', '$data')
    ";

    $conn->query($sqlCompra);
}

// Volta para a página principal
header("Location: index.php");
exit;
?>