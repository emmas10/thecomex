<?php include 'conexao.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Compras - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Histórico de Compras</h1>

    <a href="index.php">Voltar para Cotações</a>

    <form action="salvar_compra.php" method="POST">
        <input type="text" name="produto" placeholder="Produto" required>
        <input type="text" name="fornecedor" placeholder="Fornecedor" required>
        <input type="number" step="0.01" name="preco_pago" placeholder="Preço pago USD" required>
        <input type="text" name="quantidade" placeholder="Quantidade">
        <input type="date" name="data_compra" required>
        <input type="text" name="observacoes" placeholder="Observações">

        <button type="submit">Salvar Compra</button>
    </form>

    <h2>Compras Registradas</h2>

    <table>
        <tr>
            <th>Produto</th>
            <th>Fornecedor</th>
            <th>Preço Pago</th>
            <th>Quantidade</th>
            <th>Data</th>
            <th>Observações</th>
        </tr>

        <?php
        $sql = "SELECT * FROM compras ORDER BY data_compra DESC";
        $resultado = $conn->query($sql);

        while ($linha = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $linha['produto'] . "</td>";
            echo "<td>" . $linha['fornecedor'] . "</td>";
            echo "<td>USD " . $linha['preco_pago'] . "</td>";
            echo "<td>" . $linha['quantidade'] . "</td>";
            echo "<td>" . $linha['data_compra'] . "</td>";
            echo "<td>" . $linha['observacoes'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

</body>
</html>