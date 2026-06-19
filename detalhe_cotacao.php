<?php
include 'verifica_login.php';
include 'conexao.php';

$id = intval($_GET['id']);

if ($_SESSION['usuario_tipo'] == 'admin') {
    $sql = "SELECT cotacoes.*, clientes.nome_empresa, usuarios.nome AS usuario_nome
            FROM cotacoes
            LEFT JOIN clientes ON cotacoes.cliente_id = clientes.id
            LEFT JOIN usuarios ON cotacoes.usuario_id = usuarios.id
            WHERE cotacoes.id = $id";
} else {
    $cliente_id = $_SESSION['cliente_id'];

    $sql = "SELECT cotacoes.*, clientes.nome_empresa, usuarios.nome AS usuario_nome
            FROM cotacoes
            LEFT JOIN clientes ON cotacoes.cliente_id = clientes.id
            LEFT JOIN usuarios ON cotacoes.usuario_id = usuarios.id
            WHERE cotacoes.id = $id
            AND cotacoes.cliente_id = '$cliente_id'";
}

$resultado = $conn->query($sql);

if ($resultado->num_rows == 0) {
    echo "Cotação não encontrada ou acesso negado.";
    exit;
}

$cotacao = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes da Cotação</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Detalhes da Cotação</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<table>
    <tr><th>Campo</th><th>Informação</th></tr>

    <tr><td>Empresa</td><td><?php echo $cotacao['nome_empresa']; ?></td></tr>
    <tr><td>Quem Cotou</td><td><?php echo $cotacao['usuario_nome'] ?? 'Não informado'; ?></td></tr>
    <tr><td>Cotação</td><td><?php echo $cotacao['cotacao']; ?></td></tr>
    <tr><td>Produto</td><td><?php echo $cotacao['produto']; ?></td></tr>
    <tr><td>Fornecedor</td><td><?php echo $cotacao['fornecedor']; ?></td></tr>
    <tr><td>Preço</td><td>R$ <?php echo number_format($cotacao['preco'], 2, ',', '.'); ?></td></tr>
    <tr><td>Origem</td><td><?php echo $cotacao['origem']; ?></td></tr>
    <tr><td>Pagamento</td><td><?php echo $cotacao['pagamento']; ?></td></tr>
    <tr><td>Quantidade</td><td><?php echo $cotacao['quantidade']; ?></td></tr>
    <tr><td>Data da Cotação</td><td><?php echo $cotacao['data_cotacao']; ?></td></tr>
</table>

</div>

</body>
</html>