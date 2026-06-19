<?php
include 'verifica_login.php';
include 'conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ranking de Fornecedores - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>Ranking de Fornecedores por Produto</h1>

    <a href="index.php" class="botao-exportar">Voltar para Cotações</a>

    <form method="GET" action="ranking.php" class="form-busca">
        <input 
            type="text" 
            name="produto" 
            placeholder="Digite o produto. Ex: Madeira, Ácido Fosfórico..."
            value="<?php echo isset($_GET['produto']) ? $_GET['produto'] : ''; ?>"
        >
        <button type="submit">Analisar</button>
    </form>

    <?php
    $produtoBusca = isset($_GET['produto']) ? trim($_GET['produto']) : '';

    if ($produtoBusca != '') {

        echo "<h2>Produto analisado: " . $produtoBusca . "</h2>";

        if ($_SESSION['usuario_tipo'] == 'admin') {
            $filtroClienteCotacoes = "";
            $filtroClienteCompras = "AND status = 'ativa'";
        } else {
            $cliente_id = $_SESSION['cliente_id'];
            $filtroClienteCotacoes = "AND cliente_id = '$cliente_id'";
            $filtroClienteCompras = "AND status = 'ativa' AND cliente_id = '$cliente_id'";
        }

        $sqlRanking = "
            SELECT 
                fornecedor,
                MIN(menor_preco_cotado) AS menor_preco_cotado,
                MIN(menor_preco_comprado) AS menor_preco_comprado,
                SUM(qtd_cotacoes) AS qtd_cotacoes,
                SUM(qtd_compras) AS qtd_compras
            FROM (
                SELECT 
                    fornecedor,
                    MIN(preco) AS menor_preco_cotado,
                    NULL AS menor_preco_comprado,
                    COUNT(*) AS qtd_cotacoes,
                    0 AS qtd_compras
                FROM cotacoes
                WHERE TRIM(LOWER(produto)) = TRIM(LOWER('$produtoBusca'))
                $filtroClienteCotacoes
                GROUP BY fornecedor

                UNION ALL

                SELECT 
                    fornecedor,
                    NULL AS menor_preco_cotado,
                    MIN(preco_pago) AS menor_preco_comprado,
                    0 AS qtd_cotacoes,
                    COUNT(*) AS qtd_compras
                FROM compras
                WHERE TRIM(LOWER(produto)) = TRIM(LOWER('$produtoBusca'))
                $filtroClienteCompras
                GROUP BY fornecedor
            ) base
            GROUP BY fornecedor
            ORDER BY COALESCE(menor_preco_comprado, menor_preco_cotado) ASC
        ";

        $resultadoRanking = $conn->query($sqlRanking);

        if ($resultadoRanking->num_rows > 0) {
            echo "<table>";
            echo "<tr>";
            echo "<th>Fornecedor</th>";
            echo "<th>Menor Preço Cotado</th>";
            echo "<th>Menor Preço Comprado</th>";
            echo "<th>Qtd. Cotações</th>";
            echo "<th>Qtd. Compras</th>";
            echo "</tr>";

            while ($linha = $resultadoRanking->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $linha['fornecedor'] . "</td>";

                if ($linha['menor_preco_cotado'] !== null) {
                    echo "<td>R$ " . number_format($linha['menor_preco_cotado'], 2, ',', '.') . "</td>";
                } else {
                    echo "<td>-</td>";
                }

                if ($linha['menor_preco_comprado'] !== null) {
                    echo "<td>R$ " . number_format($linha['menor_preco_comprado'], 2, ',', '.') . "</td>";
                } else {
                    echo "<td>-</td>";
                }

                echo "<td>" . $linha['qtd_cotacoes'] . "</td>";
                echo "<td>" . $linha['qtd_compras'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p>Nenhum dado encontrado para esse produto.</p>";
        }

    } else {
        echo "<p>Digite um produto para ver o ranking de fornecedores.</p>";
    }
    ?>

</div>

</body>
</html>