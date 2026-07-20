<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Compras - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Compras Realizadas</h1>

    <a href="index.php" class="botao-exportar">Voltar</a>

    <table>
        <tr>
            <th>Produto</th>
            <th>Fornecedor</th>
            <th>Quem Comprou</th>
            <th>Preço Pago</th>
            <th>Quantidade</th>
            <th>Data</th>
            <th>Status</th>
            <th>Ação</th>
        </tr>

        <?php
       if ($_SESSION['usuario_tipo'] == 'admin') {
    $sql = "SELECT compras.*, usuarios.nome AS usuario_nome
            FROM compras
            LEFT JOIN usuarios ON compras.usuario_id = usuarios.id
            ORDER BY compras.data_compra DESC";
} else {
    $cliente_id = $_SESSION['cliente_id'];

    $sql = "SELECT compras.*, usuarios.nome AS usuario_nome
            FROM compras
            LEFT JOIN usuarios ON compras.usuario_id = usuarios.id
            WHERE compras.cliente_id = '$cliente_id'
            ORDER BY compras.data_compra DESC";
}

$resultado = $conn->query($sql);

        while ($linha = $resultado->fetch_assoc()) {
            if ($linha['status'] == 'cancelada') {
                echo "<tr style='background:#f8d7da; color:#842029;'>";
            } else {
                echo "<tr>";
            }
            
            echo "<td>" . $linha['produto'] . "</td>";
            echo "<td>" . $linha['fornecedor'] . "</td>";
            echo "<td>" . ($linha['usuario_nome'] ?? 'Não informado') . "</td>";
            echo "<td>" . formatarMoeda($linha['preco_pago'], $linha['preco_pago_casas_decimais'] ?? null) . "</td>";
            echo "<td>" . $linha['quantidade'] . "</td>";
            echo "<td>" . $linha['data_compra'] . "</td>";
            echo "<td>" . $linha['status'] . "</td>";

            echo "<td>";

            if ($_SESSION['usuario_tipo'] == 'admin' && $linha['status'] != 'cancelada') {
                echo "<form action='cancelar_compra.php' method='POST' style='display:inline;'>";
                echo "<input type='hidden' name='id' value='" . $linha['id'] . "'>";
                echo "<button type='submit' onclick=\"return confirm('Deseja cancelar esta compra?')\">Cancelar Compra</button>";
                echo "</form>";
            } else {
                echo "-";
            }

            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

</body>
</html>
