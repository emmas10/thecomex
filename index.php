<?php include 'conexao.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>TheComex - Cotações</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<?php
$totalCotacoes = $conn->query("SELECT COUNT(*) as total FROM cotacoes")->fetch_assoc()['total'];
$totalProdutos = $conn->query("SELECT COUNT(DISTINCT produto) as total FROM cotacoes")->fetch_assoc()['total'];
$totalCompras = $conn->query("SELECT COUNT(*) as total FROM compras")->fetch_assoc()['total'];
?>

<h1>TheComex - Cadastro de Cotações</h1>

<div class="dashboard">
    <div class="card">
        <h3>Total de Cotações</h3>
        <p><?php echo $totalCotacoes; ?></p>
    </div>

    <div class="card">
        <h3>Produtos Monitorados</h3>
        <p><?php echo $totalProdutos; ?></p>
    </div>

    <div class="card">
        <h3>Compras Registradas</h3>
        <p><?php echo $totalCompras; ?></p>
    </div>
</div>

<h2>Menor Preço por Produto</h2>

<table>
    <tr>
        <th>Produto</th>
        <th>Melhor Fornecedor Cotado</th>
        <th>Menor Preço Cotado</th>
        <th>Melhor Fornecedor Comprado</th>
        <th>Menor Preço Comprado</th>
    </tr>

    <?php
    $sqlProdutos = "
        SELECT 
            produtos.produto_base,

            cot.fornecedor AS fornecedor_cotado,
            cot.preco AS menor_preco_cotado,

            comp.fornecedor AS fornecedor_comprado,
            comp.preco_pago AS menor_preco_comprado

        FROM (
            SELECT TRIM(LOWER(produto)) AS produto_base FROM cotacoes
            UNION
            SELECT TRIM(LOWER(produto)) AS produto_base FROM compras
        ) produtos

        LEFT JOIN cotacoes cot
        ON TRIM(LOWER(cot.produto)) = produtos.produto_base
        AND cot.preco = (
            SELECT MIN(c2.preco)
            FROM cotacoes c2
            WHERE TRIM(LOWER(c2.produto)) = produtos.produto_base
        )

        LEFT JOIN compras comp
        ON TRIM(LOWER(comp.produto)) = produtos.produto_base
        AND comp.preco_pago = (
            SELECT MIN(c3.preco_pago)
            FROM compras c3
            WHERE TRIM(LOWER(c3.produto)) = produtos.produto_base
        )

        GROUP BY produtos.produto_base
        ORDER BY produtos.produto_base ASC
    ";

    $resultadoProdutos = $conn->query($sqlProdutos);

    while ($produto = $resultadoProdutos->fetch_assoc()) {
        echo "<tr>";

        echo "<td>" . ucfirst($produto['produto_base']) . "</td>";

        echo "<td>" . ($produto['fornecedor_cotado'] ?? 'Sem cotação') . "</td>";

        if ($produto['menor_preco_cotado'] !== null) {
            echo "<td>USD " . number_format($produto['menor_preco_cotado'], 2, ',', '.') . "</td>";
        } else {
            echo "<td>-</td>";
        }

        echo "<td>" . ($produto['fornecedor_comprado'] ?? 'Sem compra') . "</td>";

        if ($produto['menor_preco_comprado'] !== null) {
            echo "<td>USD " . number_format($produto['menor_preco_comprado'], 2, ',', '.') . "</td>";
        } else {
            echo "<td>-</td>";
        }

        echo "</tr>";
    }
    ?>
</table>

<h2>Nova Cotação</h2>

<form action="salvar.php" method="POST">
    <input type="text" name="cotacao" placeholder="Nome/Nº da Cotação" required>
    <input type="text" name="produto" placeholder="Produto" required>
    <input type="text" name="fornecedor" placeholder="Fornecedor" required>
    <input type="number" step="0.01" name="preco" placeholder="Preço USD" required>
    <input type="text" name="origem" placeholder="Origem">
    <input type="text" name="pagamento" placeholder="Pagamento">
    <input type="text" name="quantidade" placeholder="Quantidade">
    <input type="date" name="data_cotacao" required>

    <button type="submit">Salvar Cotação</button>
</form>

<h2>Registrar Compra Realizada</h2>

<form action="salvar_compra.php" method="POST">
    <input type="text" name="produto" placeholder="Produto comprado" required>
    <input type="text" name="fornecedor" placeholder="Fornecedor" required>
    <input type="number" step="0.01" name="preco_pago" placeholder="Preço pago USD" required>
    <input type="text" name="quantidade" placeholder="Quantidade">
    <input type="date" name="data_compra" required>
    <input type="text" name="observacoes" placeholder="Observações">

    <button type="submit">Salvar Compra</button>
</form>

<h2>Últimas Cotações</h2>

<form method="GET" action="index.php" class="form-busca">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por produto, fornecedor ou cotação"
        value="<?php echo isset($_GET['busca']) ? $_GET['busca'] : ''; ?>"
    >
    <button type="submit">Buscar</button>
</form>

<table>
    <tr>
        <th>Cotação</th>
        <th>Produto</th>
        <th>Fornecedor</th>
        <th>Preço Cotado</th>
        <th>Última Compra</th>
        <th>Diferença</th>
        <th>Origem</th>
        <th>Pagamento</th>
        <th>Data</th>
    </tr>

    <?php
    $busca = isset($_GET['busca']) ? $_GET['busca'] : '';

    if ($busca != '') {
        $sql = "SELECT * FROM cotacoes 
                WHERE produto LIKE '%$busca%' 
                OR fornecedor LIKE '%$busca%'
                OR cotacao LIKE '%$busca%'
                ORDER BY criado_em DESC";
    } else {
        $sql = "SELECT * FROM cotacoes ORDER BY criado_em DESC";
    }

    $resultado = $conn->query($sql);

    while ($linha = $resultado->fetch_assoc()) {
        $produtoAtual = $linha['produto'];
        $dataCotacaoAtual = $linha['data_cotacao'];

        $sqlMenor = "SELECT MIN(preco) AS menor_preco FROM cotacoes WHERE produto = '$produtoAtual'";
        $resultadoMenor = $conn->query($sqlMenor);
        $menor = $resultadoMenor->fetch_assoc()['menor_preco'];

        if ($linha['preco'] == $menor) {
            echo "<tr style='background-color: #d4edda; font-weight: bold;'>";
        } else {
            echo "<tr>";
        }

        echo "<td>" . $linha['cotacao'] . "</td>";
        echo "<td>" . $linha['produto'] . "</td>";
        echo "<td>" . $linha['fornecedor'] . "</td>";
        echo "<td>USD " . number_format($linha['preco'], 2, ',', '.') . "</td>";

        $sqlCompra = "SELECT * FROM compras 
                      WHERE produto = '$produtoAtual'
                      AND data_compra <= '$dataCotacaoAtual'
                      ORDER BY data_compra DESC 
                      LIMIT 1";

        $resultadoCompra = $conn->query($sqlCompra);

        if ($resultadoCompra->num_rows > 0) {
            $compra = $resultadoCompra->fetch_assoc();
            $precoPago = $compra['preco_pago'];

            $valorDiferenca = $linha['preco'] - $precoPago;
            $diferenca = ($valorDiferenca / $precoPago) * 100;

            echo "<td>USD " . number_format($precoPago, 2, ',', '.') . "</td>";

            if ($valorDiferenca < 0) {
                echo "<td style='color:green;font-weight:bold;'>
                        🟢 Economia<br>
                        " . number_format(abs($diferenca), 2, ',', '.') . "%<br>
                        USD " . number_format(abs($valorDiferenca), 2, ',', '.') . "
                      </td>";
            } elseif ($valorDiferenca > 0) {
                echo "<td style='color:red;font-weight:bold;'>
                        🔴 Aumento<br>
                        " . number_format($diferenca, 2, ',', '.') . "%<br>
                        USD " . number_format($valorDiferenca, 2, ',', '.') . "
                      </td>";
            } else {
                echo "<td style='font-weight:bold;'>➖ Mesmo preço</td>";
            }
        } else {
            echo "<td>Sem histórico</td>";
            echo "<td>-</td>";
        }

        echo "<td>" . $linha['origem'] . "</td>";
        echo "<td>" . $linha['pagamento'] . "</td>";
        echo "<td>" . $linha['data_cotacao'] . "</td>";
        echo "</tr>";
    }
    ?>
</table>

</div>

</body>
</html>